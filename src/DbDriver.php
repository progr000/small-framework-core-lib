<?php

namespace Core;

use Exception;
use PDO;
use Core\Exceptions\DbException;
use PDOStatement;

class DbDriver
{
    /** @var PDO  */
    private $pdo;
    /** @var self */
    private static $instance;
    /** @var array */
    private $errors;
    /** @var string */
    private $table_prefix = "";

    /**
     * @return DbDriver
     */
    public static function getInstance($db_conf = 'db')
    {
        if (!isset(self::$instance[$db_conf])) {
            self::$instance[$db_conf] = new self($db_conf);
        }

        return self::$instance[$db_conf];
    }

    /**
     * @param string $db_conf
     */
    private function __construct($db_conf = 'db')
    {
        /* try to obtain table prefix */
        $conn = App::$config->get($db_conf);
        $this->table_prefix = isset($conn['table_prefix']) ? $conn['table_prefix'] : "";

        /* connect to DB */
        if (is_array($conn) && !empty($conn['dsn']) && !empty($conn['user']) && !empty($conn['password'])) {
            $this->pdo = new PDO(
                $conn['dsn'],
                $conn['user'],
                $conn['password']
            );
            //$this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
            try {
                $this->pdo->exec('SET NAMES UTF8');
            } catch (Exception $e) {}
        }
    }

    /**
     * Return error-stack
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Return all records for query as associative array
     * @param string $sql
     * @param array $params
     * @return array|false
     * @throws DbException
     */
    public function getAll($sql, $params = [])
    {
        $ret = $this->exec($sql, $params);
        if ($ret) {
            return $ret->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    /**
     * Return one record for query as associative array
     * @param string $sql
     * @param array $params
     * @return false|mixed
     * @throws DbException
     */
    public function getOne($sql, $params = [])
    {
        $ret = $this->exec($sql, $params);
        if ($ret) {
            return $ret->fetch(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    /**
     * Replace keys in query for its values
     * @param string $sql
     * @param array $params
     * @return array|string|string[]
     * @throws DbException
     */
    public function prepareSql($sql, array $params)
    {
        foreach ($params as $key => $val) {
            $type = gettype($val);
            if (!in_array($type, ["boolean", "integer", "double", "string", "NULL"])) {
                throw new DbException("Can't prepare Sql statement for this variable type {$type}", 500);
            }
            if (in_array($type, ["boolean", "integer"])) {
                $params[$key] = intval($val);
            } elseif ($type !== "NULL") {
                $params[$key] = $this->pdo->quote($val);
            }
        }
        $keys = array_map(function ($val) {
            return ":{$val}";
        }, array_keys($params));
        $keys[] = "{{";
        $keys[] = "}}";
        $values = array_values($params);
        $values[] = "`$this->table_prefix";
        $values[] = "`";

        return str_replace($keys, $values, $sql);
    }

    /**
     * Execute query
     * @param $sql
     * @param $params
     * @return false|PDOStatement
     * @throws DbException
     */
    public function exec($sql, $params = [])
    {
        $this->errors = [];

        if (!$this->pdo) {
            throw new DbException("PDO not initialized. Probably not set params for db-connections in config", 500);
        }

        try {

            foreach ($params as $key => $val) {
                if (strrpos($sql, ":$key") === false) {
                    unset($params[$key]);
                }
            }
            $sql = str_replace(['{{', '}}'], ["`{$this->table_prefix}", "`"], $sql);

            $sth = $this->pdo->prepare($sql);
            $result = $sth->execute($params);
            if ($result === false) {
                //$this->errors[] = ['query' => $sql, 'data' => $params];
                $this->errors[] = $this->prepareSql($sql, $params);
                $this->errors[] = $sth->errorInfo();
                $this->errors[] = $this->pdo->errorInfo();
                return false;
            }
            return $sth;

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            if ($this->pdo) {
                $this->errors[] = $this->pdo->errorInfo();
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            }
            return false;
        }
    }

    /**
     * Start transaction
     * @return void
     */
    public function beginTransaction()
    {
        $this->pdo && $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     * @return void
     */
    public function commit()
    {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    /**
     * Rollback transaction
     * @return void
     */
    public function rollBack()
    {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Return last id for inserted record
     * @return int|null
     */
    public function lastInsert()
    {
        if ($this->pdo) {
            return intval($this->pdo->lastInsertId());
        }
        return null;
    }
}
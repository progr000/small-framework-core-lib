<?php

namespace Core;

use Exception;
use PDO;
use Core\Exceptions\DbException;

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
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @throws Exceptions\ConfigException
     */
    private function __construct()
    {
        /* try to obtain table prefix */
        $conn = App::$config->get('db');
        $this->table_prefix = isset($conn['table_prefix']) ? $conn['table_prefix'] : "";

        /* connect to DB */
        if (isset($conn['dsn'], $conn['user'], $conn['password'])) {
            $this->pdo = new PDO(
                $conn['dsn'],
                $conn['user'],
                $conn['password']
            );
            //$this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
            $this->pdo->exec('SET NAMES UTF8');
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
                throw new DbException("Can't prepare Sql statement for this variable type {$type}");
            }
            if (in_array($type, ["boolean", "integer"])) {
                $params[$key] = intval($val);
            } else {
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
     * @return false|\PDOStatement
     */
    public function exec($sql, $params = [])
    {
        $this->errors = [];
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
            $this->errors[] = $this->pdo->errorInfo();
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
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
        $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     * @return void
     */
    public function commit()
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    /**
     * Rollback transaction
     * @return void
     */
    public function rollBack()
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Return last id for inserted record
     * @return int
     */
    public function lastInsert()
    {
        return intval($this->pdo->lastInsertId());
    }
}
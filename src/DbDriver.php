<?php

namespace Core;

use Core\Providers\ExtendedStdClass;
use Exception;
use PDO;
use Core\Exceptions\DbException;
use PDOStatement;

class DbDriver
{
    const mssql_driver = 'sqlsrv';
    const mysql_driver = 'mysql';
    const pgsql_driver = 'pgsql';
    const sqlite_driver = 'sqlite';

    /** @var PDO  */
    private $pdo;
    /** @var array */
    private $errors;
    /** @var string */
    private $table_prefix = "";
    /** @var string */
    private $driver;
    /** @var string */
    private $sql_quote = "";
    /** @var int|null */
    private $affectedRows;
    /** @var string */
    private $connection_name;

    /**
     * @param string $db_conf_name
     * @return DbDriver
     */
    public static function getInstance($db_conf_name = 'db-main')
    {
        if (!isset(App::$DbInstances[$db_conf_name])) {
            App::$DbInstances[$db_conf_name] = new self($db_conf_name);
        }

        return App::$DbInstances[$db_conf_name];
    }

    /**
     * @param string $db_conf_name
     */
    private function __construct($db_conf_name = 'db-main')
    {
        /* try to obtain table prefix */
        $conn = config("databases->{$db_conf_name}", []);
        $this->table_prefix = isset($conn['table_prefix']) ? $conn['table_prefix'] : "";
        $this->connection_name = $db_conf_name;

        /* connect to DB */
        if (is_array($conn) && !empty($conn['dsn']) && !empty($conn['user']) && !empty($conn['password'])) {
            try {
                $this->pdo = new PDO(
                    $conn['dsn'],
                    $conn['user'],
                    $conn['password']
                );
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                //$this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
                $this->driver = mb_strtolower($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
                if ($this->driver === self::mssql_driver) {
                    $this->sql_quote = '"';
                } elseif ($this->driver === self::mysql_driver) {
                    $this->sql_quote = '`';
                } elseif ($this->driver === self::pgsql_driver) {
                    $this->sql_quote = '"';
                }
                if (isset($conn['charset']) && $this->driver !== self::mssql_driver) {
                    try {
                        $this->pdo->exec("SET NAMES '{$conn['charset']}'");
                    } catch (Exception $e) {
                        // skip this error
                    }
                }
            } catch (Exception $e) {

                $sql_error_handler = config('sql_error_handler', null);
                if (is_callable($sql_error_handler)) {
                    $sql_error_handler($e);
                }

            }
        }
    }

    /**
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @return string
     */
    public function getSqlQuote()
    {
        return $this->sql_quote;
    }

    public function getTablePrefix()
    {
        return $this->table_prefix;
    }

    public function getConnectionName()
    {
        return $this->connection_name;
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
     * @param string $table
     * @param bool $only_show_sql
     * @param string $class
     * @return QueryBuilderDriver
     */
    public function table($table, $only_show_sql = false, $class = ExtendedStdClass::class)
    {
        return new QueryBuilderDriver($this, $class, $table, $only_show_sql);
    }

    /**
     * @param $val
     * @return false|int|string|void
     * @throws DbException
     */
    public function prepareValType($val)
    {
        if (!$this->pdo) {
            throw new DbException("PDO not initialized. Probably not set params for db-connections in config", 500);
        }
        $type = gettype($val);
        if (!in_array($type, ["boolean", "integer", "double", "string", "NULL"])) {
            throw new DbException("Can't prepare Sql statement for this variable type {$type}", 500);
        }
        if (in_array($type, ["boolean", "integer"])) {
            return intval($val);
        } elseif ($type !== "NULL") {
            return $this->pdo->quote($val);
        } else {
            return 'null'; // TODO check this on Postgres and MsSql (MySql is OK)
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
            $params[$key] = $this->prepareValType($val);
        }
        $keys = array_map(function ($val) {
            return ":{$val}";
        }, array_keys($params));
        $keys[] = "{{";
        $keys[] = "}}";
        $values = array_values($params);
        $values[] = "{$this->sql_quote}{$this->table_prefix}";
        $values[] = "{$this->sql_quote}";

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
            $sql = str_replace(['{{', '}}'], ["{$this->sql_quote}{$this->table_prefix}", "{$this->sql_quote}"], $sql);

            $sth = $this->pdo->prepare($sql);

            /* +++ for debug panel */
            $sql_start = microtime(true);
            /* --- */

            $result = $sth->execute($params);

            $ready_sql = $this->prepareSql($sql, $params);
            /* +++ for debug panel */
            if (config('IS_DEBUG', false)) {
                $sql_finish = microtime(true);
                App::$debug->_set('sqlLog', [0 => [
                    'sql' => $ready_sql,
                    'params' => $params,
                    'status' => 'successful',
                    'label' => '',
                    'sqlTimeStart' => $sql_start,
                    'sqlTimeFinish' => $sql_finish,
                    'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
                    'connection' => $this->connection_name,
                    'driver' => $this->driver,
                ]]);
            }
            /* --- */

            $this->affectedRows = $sth->rowCount();
            if ($result === false) {
                //$this->errors[] = ['query' => $sql, 'data' => $params];
                $this->errors[] = $ready_sql;
                $this->errors[] = $sth->errorInfo();
                $this->errors[] = $this->pdo->errorInfo();
                return false;
            }
            return $sth;

        } catch (Exception $e) {
            /* +++ for debug panel */
            if (config('IS_DEBUG', false)) {
                App::$debug->_set('sqlLog', [0 => [
                    'sql' => $sql,
                    'params' => $params,
                    'status' => 'failed',
                    'label' => $e->getMessage(),
                    'sqlTimeStart' => microtime(true),
                    'sqlTimeFinish' => microtime(true),
                    'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
                    'connection' => $this->connection_name,
                    'driver' => $this->driver,
                ]]);
            }
            /* --- */
            $this->errors[] = $e->getMessage();
            if ($this->pdo) {
                $this->errors[] = $sql;
                $this->errors[] = $this->pdo->errorInfo();
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            }
            $sql_error_handler = config('sql_error_handler', null);
            if (is_callable($sql_error_handler)) {
                return $sql_error_handler($e, $sql);
            } else {
                return false;
            }
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

    /**
     * @return int|null
     */
    public function affectedRows()
    {
        return $this->affectedRows;
    }
}
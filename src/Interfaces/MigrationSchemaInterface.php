<?php

namespace Core\Interfaces;

use Core\DbDriver;
use Core\Exceptions\DbException;

abstract class MigrationSchemaInterface
{
    /** @var DbDriver */
    protected $db;

    /**
     * @param DbDriver $db
     */
    public function __construct(DbDriver $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $queries
     * @param array $params
     * @return bool
     * @throws DbException
     */
    protected function exec($queries, $params = [])
    {
        $queries_ = explode(';', $queries);
        foreach ($queries_ as $sql) {
            $sql = trim($sql);
            if (!empty($sql)) {
                if (!$this->db->exec($sql, $params)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string $table
     * @return bool
     * @throws DbException
     */
    public function dropTable($table)
    {
        return $this->db->exec("DROP TABLE {$table}");
    }

    /**
     * @param string $table
     * @return bool|\PDOStatement
     * @throws DbException
     */
    public function dropTableIfExists($table)
    {
        return $this->db->exec("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param string $options
     * @return mixed
     */
    abstract public function createTable($tableName, \Closure $function, $options = "");

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param string $options
     * @return mixed
     */
    abstract public function createTableIfNotExists($tableName, \Closure $function, $options = "");
}
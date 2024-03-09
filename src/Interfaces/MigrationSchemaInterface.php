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
     * @param array $options
     * @return mixed
     */
    abstract public function createTable($tableName, \Closure $function, array $options = []);

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param array $options
     * @return mixed
     */
    abstract public function createTableIfNotExists($tableName, \Closure $function, array $options = []);
}
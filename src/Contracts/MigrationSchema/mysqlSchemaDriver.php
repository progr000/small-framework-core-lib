<?php

namespace Core\Contracts\MigrationSchema;

use Core\Contracts\MigrationSchema\Common\SchemaTable;
use Core\Exceptions\DbException;
use Core\Interfaces\MigrationSchemaInterface;

class mysqlSchemaDriver extends MigrationSchemaInterface
{
    /** @var string */
    private $if_not_exists = "";

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param array $options
     * @return string
     */
    private function createStatement($tableName, \Closure $function, $options=['engine' => 'InnoDB', 'collate' => 'utf8_general_ci'])
    {
        /* init SchemaTable and Closure for it */
        $table = new SchemaTable($this->db);
        $function($table);

        /* init sql for create table */
        $sql = "CREATE TABLE {$this->if_not_exists} {$tableName} (";
        /* columns and indexes */
        $sql .= implode(", ", $table->columns);
        if (count($table->indexes) > 0) {
            $sql .= ", " . implode(", ", $table->indexes);
        }
        /* finalize sql for create table */
        $sql .= PHP_EOL . ")";
        if (isset($options['engine'])) {
            $sql .= PHP_EOL . "ENGINE = {$options['engine']}";
        }
        if (isset($options['collate'])) {
            $sql .= PHP_EOL . "COLLATE = {$options['collate']}";
        }

        return $sql;
    }

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param array $options
     * @return bool|\PDOStatement
     * @throws DbException
     */
    public function createTable($tableName, \Closure $function, array $options = ['engine' => 'InnoDB', 'collate' => 'utf8_general_ci'])
    {
        $this->if_not_exists = "";
        $sql = $this->createStatement($tableName, $function, $options);
        return $this->db->exec($sql);
    }

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param array $options
     * @return bool|\PDOStatement
     * @throws DbException
     */
    public function createTableIfNotExists($tableName, \Closure $function, array $options = ['engine' => 'InnoDB', 'collate' => 'utf8_general_ci'])
    {
        $this->if_not_exists = "IF NOT EXISTS";
        $sql = $this->createStatement($tableName, $function, $options);
        return $this->db->exec($sql);
    }
}
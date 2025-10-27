<?php

namespace Core\Contracts\MigrationSchema;

use Core\Contracts\MigrationSchema\Common\SchemaTable;
use Core\Interfaces\MigrationSchemaInterface;
use Core\Exceptions\DbException;
use Maksym\Config\ConfigException;

class mysqlSchemaDriver extends MigrationSchemaInterface
{
    /** @var string */
    private $if_not_exists = "";

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param string $options
     * @return string
     */
    private function createStatement($tableName, \Closure $function, $options = "")
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
        $sql .= PHP_EOL . ") ";
        $sql .= PHP_EOL . $options;

        return $sql;
    }

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param string $options
     * @return bool|\PDOStatement
     * @throws DbException
     */
    public function createTable($tableName, \Closure $function, $options = "ENGINE = InnoDB COLLATE = utf8_general_ci")
    {
        $this->if_not_exists = "";
        $sql = $this->createStatement($tableName, $function, $options);
        return $this->exec($sql);
    }

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param string $options
     * @return bool|\PDOStatement
     * @throws DbException|ConfigException
     */
    public function createTableIfNotExists($tableName, \Closure $function, $options = "ENGINE = InnoDB COLLATE = utf8_general_ci")
    {
        $this->if_not_exists = "IF NOT EXISTS";
        $sql = $this->createStatement($tableName, $function, $options);
        return $this->exec($sql);
    }
}
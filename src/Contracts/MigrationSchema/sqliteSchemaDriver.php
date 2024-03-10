<?php

namespace Core\Contracts\MigrationSchema;

use Core\Contracts\MigrationSchema\Common\SchemaColumn;
use Core\Contracts\MigrationSchema\Common\SchemaTable;
use Core\Exceptions\DbException;
use Core\Interfaces\MigrationSchemaInterface;

class sqliteSchemaDriver extends MigrationSchemaInterface
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
        /* columns */
        $sql .= implode(", ", $table->columns);
        if (count($table->indexes) > 0) {
            foreach ($table->indexes as $key => $index) {
                /** @var SchemaColumn $index */
                if (!$index->is_foreign) {
                    $for_prepend = (string)$index;
                    unset($table->indexes[$key]);
                }
            }
            if (count($table->indexes) > 0) {
                $sql .= ", " . implode(", ", $table->indexes);
            }
        }
        /* finalize sql for create table */
        $sql .= PHP_EOL . ")" . ($options !== "" ? " {$options};" : ";") . PHP_EOL;
        /* indexes */
        if (count($table->prepend) > 0) {
            $sql .=  str_replace('%%table_name%%', $tableName, implode(";" . PHP_EOL, $table->prepend));
        }

        //dd($sql);
        return $sql;
    }

    /**
     * @param string $tableName
     * @param \Closure $function
     * @param string $options
     * @return bool|\PDOStatement
     * @throws DbException
     */
    public function createTable($tableName, \Closure $function, $options = "")
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
     * @throws DbException
     */
    public function createTableIfNotExists($tableName, \Closure $function, $options = "")
    {
        $this->if_not_exists = "IF NOT EXISTS";
        $sql = $this->createStatement($tableName, $function, $options);
        return $this->exec($sql);
    }
}
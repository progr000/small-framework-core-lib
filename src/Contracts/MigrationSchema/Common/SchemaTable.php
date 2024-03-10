<?php

namespace Core\Contracts\MigrationSchema\Common;

use Core\DbDriver;
use Core\Exceptions\DbException;

/**
 * @property DbDriver $db
 */
class SchemaTable
{
    /** @var array */
    public $columns = [];
    /** @var array */
    public $indexes = [];
    /** @var mixed */
    public $append;
    /** @var mixed */
    public $prepend;
    /** @var bool */
    public $pkf_is_defined = false;
    
    /** @var DbDriver  */
    public $db;

    /**
     * @param DbDriver $db
     */
    public function __construct(DbDriver $db)
    {
        $this->db = $db;
    }

    /**
     * Add new column into the table with specified name
     * @param string $name
     * @return SchemaColumn
     */
    public function column($name)
    {
        $this->columns[$name] = (new SchemaColumn($this, $name, false, false));
        return $this->columns[$name];
    }

    /**
     * Add the index for the field or several fields.
     * @param string $name the index name
     * @param array|string $column name of column or columns (if array given)
     * @return void
     */
    public function index($name, $column)
    {
        if (!is_array($column)) { $column = [$column]; }
        $this->indexes[$name] = (new SchemaColumn($this, $name, true, false, false));
        $this->indexes[$name]->setIndexColumns($column);
        //return $this->indexes[$name];
    }

    /**
     * Add the unique index for the field or several fields.
     * @param string $name the index name
     * @param array|string $column name of column or columns (if array given)
     * @return void
     */
    public function unique($name, $column)
    {
        if (!is_array($column)) { $column = [$column]; }
        $this->indexes[$name] = (new SchemaColumn($this, $name, true, true, false));
        $this->indexes[$name]->setIndexColumns($column);
        //return $this->indexes[$name];
    }

    /**
     * Add a foreign key constraint to the table.
     * @param string $name the name of the foreign key constraint.
     * @param string|array $column the name of the column to that the constraint will be added on. If there are multiple columns, separate them with commas or use an array.
     * @param string $refTable the table that the foreign key references to.
     * @param string|array $refColumn the name of the column that the foreign key references to. If there are multiple columns, separate them with commas or use an array.
     * @param string $delete the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     * @param string $update the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     * @throws DbException
     */
    public function foreignKey($name, $column, $refTable, $refColumn, $delete = 'SET NULL', $update = 'SET NULL')
    {
        if (!is_array($column)) { $column = [$column]; }
        if (!is_array($refColumn)) { $refColumn = [$refColumn]; }
        $delete = mb_strtoupper($delete);
        if (!in_array($delete, ['RESTRICT', 'CASCADE', 'NO ACTION', 'SET DEFAULT', 'SET NULL'])) {
            throw new DbException(get_class($this) . "::foreignKey(): You should specify correct data (\$delete) for foreign `{$name}`");
        }
        if (!in_array($update, ['RESTRICT', 'CASCADE', 'NO ACTION', 'SET DEFAULT', 'SET NULL'])) {
            throw new DbException(get_class($this) . "::foreignKey(): You should specify correct data (\$update) for foreign `{$name}`");
        }
        $this->indexes[$name] = (new SchemaColumn($this, $name, true, false, true));
        $this->indexes[$name]->setForeignData([
            'name' => $name,
            'column' => $column,
            'refTable' => $refTable,
            'refColumn' => $refColumn,
            'delete' => $delete,
            'update' => $update,
        ]);
    }
}
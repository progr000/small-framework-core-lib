<?php

namespace Core;

use PDO;
use Core\Exceptions\DbException;

class QueryBuilderDriver
{
    private $select = " * ";
    private $andWhere = [];
    private $orWhere = [];
    private $orderBy = "";
    private $limit = 0;
    private $offset = 0;

    /** @var DbDriver */
    private $connection;
    private $sql_quote = "";
    private $class;
    private $table;

    /**
     * @param DbDriver $connection
     * @param string $class
     * @param string $table
     */
    public function __construct($connection, $class, $table)
    {
        $this->connection = $connection;
        $this->sql_quote = $this->connection->sql_quote;
        $this->class = $class;
        $this->table = $table;
    }

    /**
     * @param array|string $columns
     * @return $this
     */
    public function select($columns)
    {
        if (gettype($columns) === 'string') {
            $this->select = " {$columns} ";
        } else {
            $this->select = " {$this->sql_quote}" . implode("{$this->sql_quote}, {$this->sql_quote}", $columns) . "{$this->sql_quote} ";
        }
        return $this;
    }

    /**
     * @param array|string $condition
     * @param array $params
     * @return $this
     * @throws DbException
     */
    public function where($condition, $params = [])
    {
        if (gettype($condition) === 'string') {
            $this->andWhere[] = $this->connection->prepareSql("({$condition})", $params);
        } else {
            foreach ($condition as $k => $v) {
                $this->andWhere[] = $this->connection->prepareSql("({$this->sql_quote}{$k}{$this->sql_quote} = :{$k})", [$k => $v]);
            }
        }
        return $this;
    }

    /**
     * @param array|string $condition
     * @param array $params
     * @return $this
     * @throws DbException
     */
    public function orWhere($condition, $params = [])
    {
        if (gettype($condition) === 'string') {
            $this->orWhere[] = $this->connection->prepareSql("({$condition})", $params);
        } else {
            foreach ($condition as $k => $v) {
                $this->orWhere[] = $this->connection->prepareSql("({$this->sql_quote}{$k}{$this->sql_quote} = :{$v})", [$k => $v]);
            }
        }
        return $this;
    }

    /**
     * @param array|string $columns
     * @return $this
     */
    public function orderBy($columns)
    {
        if (gettype($columns) === 'string') {
            $this->orderBy = " ORDER BY {$columns} ";
        } else {
            $array_order = [];
            foreach ($columns as $k => $v) {
                if (in_array(mb_strtoupper($v), ['ASC', 'DESC'])) {
                    $array_order[] = "{$this->sql_quote}{$k}{$this->sql_quote} " . mb_strtoupper($v);
                } else {
                    $array_order[] = "{$this->sql_quote}{$v}{$this->sql_quote} ASC";
                }
            }
            if (sizeof($array_order)) {
                $this->orderBy = " ORDER BY " . implode(', ', $array_order) . " ";
            }
        }
        return $this;
    }

    /**
     * @param int $value
     * @return $this;
     */
    public function limit($value)
    {
        $this->limit = $value;
        return $this;
    }

    /**
     * @param int $value
     * @return $this;
     */
    public function offset($value)
    {
        $this->offset = $value;
        return $this;
    }

    /**
     * @return array|string|string[]|null
     * @throws DbException
     */
    public function rawSql()
    {
        /**/
        $sql = $this->connection->prepareSql("{$this->select} FROM " . $this->table . " ", []);

        /**/
        $where = "";
        if (sizeof($this->andWhere) || sizeof($this->orWhere)) {
            if (sizeof($this->andWhere)) {
                $where = " WHERE " . implode(" AND ", $this->andWhere);
            }
            if (sizeof($this->orWhere)) {
                if ($where === "") {
                    $where .= implode(" OR ", $this->andWhere);
                } else {
                    $where .= "OR " . implode(" OR ", $this->andWhere);
                }
            }
        }
        $sql .= $where;

        /**/
        $sql .= $this->orderBy;

        /**/
        if ($this->connection->driver === 'sqlsrv') {
            $TOP = "";
            $LIMIT = "";
            if ($this->orderBy === "") {
                if ($this->limit > 0) {
                    $TOP = " TOP {$this->limit} ";
                }
            } else {
                if ($this->limit > 0) {
                    $LIMIT = " OFFSET {$this->offset} ROWS FETCH NEXT {$this->limit} ROWS ONLY";
                }
            }
            $sql = "SELECT {$TOP} {$sql} {$LIMIT}";
        } else {
            $LIMIT = "";
            if ($this->limit > 0) {
                $LIMIT = " LIMIT {$this->limit} OFFSET {$this->offset}";
            }
            $sql = "SELECT {$sql} {$LIMIT}";
        }

        return preg_replace("/[\s]+/", " ", trim($sql));
    }

    /**
     * @return array|false|null
     * @throws DbException
     */
    public function get()
    {
        $sth = $this->connection->exec($this->rawSql());
        if ($sth) {
            return $sth->fetchAll(PDO::FETCH_CLASS, $this->class);
        }
        return null;
    }

}
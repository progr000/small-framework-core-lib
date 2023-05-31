<?php

namespace Core;

use PDO;
use stdClass;
use Core\Exceptions\DbException;

class QueryBuilderDriver
{
    /** @var DbDriver */
    private $connection;
    /** @var string */
    private $class;
    /** @var string */
    private $sql_quote = "";

    /** @var string */
    private $table;
    /** @var string */
    private $alias = "";
    /** @var string */
    private $select = " * ";
    /** @var array */
    private $join = [];
    /** @var array */
    private $andWhere = [];
    /** @var array */
    private $orWhere = [];
    /** @var string */
    private $orderBy = "";
    /** @var int */
    private $limit = 0;
    /** @var int */
    private $offset = 0;
    /** @var array */
    private $all_aliases = [];


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
            $this->select = str_replace(
                "{$this->sql_quote}{$this->sql_quote}",
                "{$this->sql_quote}" ,
                str_replace(".", "{$this->sql_quote}.{$this->sql_quote}", $this->select)
            );
        }
        return $this;
    }

    /**
     * @param $alias
     * @return $this
     */
    public function alias($alias)
    {
        $this->all_aliases[$alias] = $this->table;
        $this->alias = " AS {$this->sql_quote}{$alias}{$this->sql_quote} ";
        return $this;
    }

    /**
     * @param array|string $condition
     * @param array $params
     * @return string
     * @throws DbException
     */
    private function prepareCondition($condition, $params = [])
    {
        if (gettype($condition) === 'string') {
            return $this->connection->prepareSql("({$condition})", $params);
        } else {
            $tmp = [];
            foreach ($condition as $k => $v) {
                $where_field = $k;
                $where_field = str_replace($this->sql_quote, "", $where_field);
                $where_field = "{$this->sql_quote}" . str_replace(".", "{$this->sql_quote}.{$this->sql_quote}", $where_field) . "{$this->sql_quote}";
                $tmp[] = $this->connection->prepareSql("({$where_field} = :{$k})", [$k => $v]);
            }
            return "(" . implode(" AND ", $tmp) . ")";
        }
    }

    /**
     * @param string $table
     * @return string
     */
    private function prepareJoinTableName($table)
    {
        $table = preg_replace("/[\s]+/", " ", trim($table));
        $table = str_replace([$this->sql_quote, ' as '], ['', ' AS '], $table);
        $tmp = explode(' AS ', $table);
        if (isset($tmp[1])) {
            $tmp[1] = trim($tmp[1]);
            $tmp[0] = trim($tmp[0]);
            $this->all_aliases[$tmp[1]] = $tmp[0];
        }
        return "{$this->sql_quote}" . str_replace(' AS ', "{$this->sql_quote} AS {$this->sql_quote}", $table) . "{$this->sql_quote}";
    }

    /**
     * @param string $table
     * @param string $on
     * @param array $params
     * @return $this
     * @throws DbException
     */
    public function innerJoin($table, $on, $params = [])
    {
        $this->join[] = [
            'type' => 'INNER JOIN',
            'table' => $this->prepareJoinTableName($table),
            'on' => $this->connection->prepareSql("({$on})", $params),
        ];
        return $this;
    }

    /**
     * @param string $table
     * @param string $on
     * @param array $params
     * @return $this
     * @throws DbException
     */
    public function leftJoin($table, $on, $params = [])
    {
        $this->join[] = [
            'type' => 'LEFT JOIN',
            'table' => $this->prepareJoinTableName($table),
            'on' => $this->connection->prepareSql("({$on})", $params),
        ];
        return $this;
    }

    /**
     * @param string $table
     * @param string $on
     * @param array $params
     * @return $this
     * @throws DbException
     */
    public function rightJoin($table, $on, $params = [])
    {
        $this->join[] = [
            'type' => 'RIGHT JOIN',
            'table' => $this->prepareJoinTableName($table),
            'on' => $this->connection->prepareSql("({$on})", $params),
        ];
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
        $this->andWhere = array_merge($this->andWhere, [$this->prepareCondition($condition, $params)]);
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
        $this->orWhere = array_merge($this->orWhere, [$this->prepareCondition($condition, $params)]);
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
                    $sort_direction = mb_strtoupper($v);
                    $sort_field = $k;
                } else {
                    $sort_direction = "ASC";
                    $sort_field = $v;
                }
                $sort_field = str_replace($this->sql_quote, "", $sort_field);
                $sort_field = "{$this->sql_quote}" . str_replace(".", "{$this->sql_quote}.{$this->sql_quote}", $sort_field) . "{$this->sql_quote}";
                $array_order[] = "{$sort_field} " . $sort_direction;
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
        $sql = $this->connection->prepareSql("{$this->select} FROM {$this->table} {$this->alias}", []);
        if ($this->select !== " * ") {
            /* if field specified, can't return rear modelClass, return stdClass instead */
            $this->class = stdClass::class;
        }

        /**/
        if (sizeof($this->join)) {
            foreach ($this->join as $v) {
                $sql .= " {$v['type']} {$v['table']} ON {$v['on']} ";
            }
            /* if join enabled can't return rear modelClass, return stdClass instead */
            $this->class = stdClass::class;
        }

        /**/
        $where = "";
        if (sizeof($this->andWhere) || sizeof($this->orWhere)) {
            if (sizeof($this->andWhere)) {
                $where = " WHERE " . implode(" AND ", $this->andWhere);
            }
            if (sizeof($this->orWhere)) {
                if ($where === "") {
                    $where .= " WHERE " . implode(" OR ", $this->orWhere);
                } else {
                    $where .= " OR " . implode(" OR ", $this->orWhere);
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

        /**/
        $search = [];
        $replace = [];
        foreach ($this->all_aliases as $k => $v) {
            $search[] = "{$k}.";
            $search[] = "{$v}.";
            $replace[] = "\"{$k}\".";
            $replace[] = "\"{$v}\".";
        }
        $sql = str_replace($search, $replace, $sql);

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
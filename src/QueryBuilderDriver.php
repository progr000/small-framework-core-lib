<?php

namespace Core;

use Core\Providers\RelationshipContainer;
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
    /** @var array */
    private $insert_update_upsert_data = [];
    /** @var array */
    private $upsert_unique_by = [];

    /** @var bool */
    private $only_show_sql = false;

    /** @var array */
    private $relations = [];

    /**
     * @param DbDriver $connection
     * @param string $class
     * @param string $table
     */
    public function __construct($connection, $class, $table, $only_show_sql=false)
    {
        $this->connection = $connection;
        $this->sql_quote = $this->connection->sql_quote;
        $this->class = $class;
        $this->table = $table;
        $this->only_show_sql = $only_show_sql;
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
            foreach ($columns as $k => $v) {
                if (gettype($k) === 'integer') {
                    $select_field = $v;
                    $select_field = str_replace([$this->sql_quote, "'", '"', "`"], "", $select_field);
                    $select_field = "{$this->sql_quote}" . str_replace(".", "{$this->sql_quote}.{$this->sql_quote}", $select_field) . "{$this->sql_quote}";
                    $columns[$k] = $select_field;
                } else {
                    // raw content as is it
                    $do_nothing = true;
                }
            }
            $this->select = implode(", ", $columns);
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
        if (gettype($condition) === 'string' && (trim($condition) !== "")) {
            return $this->connection->prepareSql("({$condition})", $params);
        } else {
            $tmp = [];
            if (sizeof($condition)) {
                foreach ($condition as $k => $v) {
                    $where_field = $k;
                    $where_field = str_replace([$this->sql_quote, "'", '"', "`"], "", $where_field);
                    $where_field = "{$this->sql_quote}" . str_replace(".", "{$this->sql_quote}.{$this->sql_quote}", $where_field) . "{$this->sql_quote}";
                    $tmp[] = $this->connection->prepareSql("({$where_field} = :{$k})", [$k => $v]);
                }
                if (sizeof($tmp) === 1) {
                    return implode("", $tmp);
                } else {
                    return "(" . implode(" AND ", $tmp) . ")";
                }
            }
        }

        return "";
    }

    /**
     * @param string $table
     * @return string
     */
    private function prepareJoinTableName($table)
    {
        $table = str_replace(['{{', '}}'], [$this->connection->table_prefix, ''], $table);
        $table = replaceMultiSpacesAndNewLine(trim($table));
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
        $tmp = $this->prepareCondition($condition, $params);
        if (!empty($tmp)) {
            $this->andWhere = array_merge($this->andWhere, [$tmp]);
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
        $tmp = $this->prepareCondition($condition, $params);
        if (!empty($tmp)) {
            $this->orWhere = array_merge($this->orWhere, [$tmp]);
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
                    $sort_direction = mb_strtoupper($v);
                    $sort_field = $k;
                } else {
                    $sort_direction = "ASC";
                    $sort_field = $v;
                }
                $sort_field = str_replace([$this->sql_quote, "'", '"', "`"], "", $sort_field);
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
     * @param array $relations
     * @return $this
     */
    public function with(array $relations)
    {
        $this->relations = array_merge($this->relations, $relations);
        return $this;
    }

    /**
     * @return false|array
     * @throws DbException
     */
    public function get()
    {
        $sql = $this->prepareRawSql('select');
        if ($this->only_show_sql) {
            return $sql;
        }
        $sth = $this->connection->exec($sql);
        if ($sth) {
            //dump($sql);
            if ($this->class !== 'stdClass' && !empty($this->relations)) {
                $res = $sth->fetchAll(PDO::FETCH_CLASS, $this->class);
                $_unique_result_key = md5($sql . $this->class);
                RelationshipContainer::$_mainResultContainer[$_unique_result_key] = &$res;
                RelationshipContainer::$_withRelations[$_unique_result_key] = &$this->relations;
                foreach ($res as $obj) {
                    $obj->__setTechnicalData('unique_result_key', $_unique_result_key);
                }
                return $res;
            } else {
                return $sth->fetchAll(PDO::FETCH_CLASS, $this->class);
            }

        }
        return false;
    }

    /**
     * @return false|array
     * @throws DbException
     */
    public function all()
    {
        return $this->get();
    }

    /**
     * @return false|mixed
     * @throws DbException
     */
    public function one()
    {
        $this->limit(1);
        $res = $this->get();
        if ($this->only_show_sql) {
            //return $res . " LIMIT 1";
            return $res;
        }
        if (isset($res[0])) {
            return $res[0];
        }

        return false;
    }

    /**
     * @return false|int|string
     * @throws DbException
     */
    public function count()
    {
        $this->select = " count(*) as cnt ";
        $this->orderBy = "";
        $sql = $this->prepareRawSql('select');
        if ($this->only_show_sql) {
            return $sql;
        }
        $sth = $this->connection->exec($sql);
        if ($sth) {
            $res = $sth->fetch(PDO::FETCH_ASSOC);
            if (isset($res['cnt'])) {
                return intval($res['cnt']);
            }
        }
        return false;
    }

    /**
     * @param array|string $condition
     * @param array $params
     * @return false|int|null|string
     * @throws DbException
     */
    public function delete($condition = [], $params = [])
    {
        $this->where($condition, $params);
        $sql = $this->prepareRawSql('delete');
        if ($this->only_show_sql) {
            return $sql;
        }
        $sth = $this->connection->exec($sql);
        if ($sth) {
            return $this->connection->affectedRows();
        } else {
            return false;
        }
    }

    /**
     * @param array $fields
     * @return false|int|null|string
     * @throws DbException
     */
    public function insert(array $fields)
    {
        $this->insert_update_upsert_data = $fields;
        $sql = $this->prepareRawSql('insert');
        if ($this->only_show_sql) {
            return $sql;
        }
        $sth = $this->connection->exec($sql);
        if ($sth) {
            return $this->connection->affectedRows();
        } else {
            return false;
        }
    }

    /**
     * @param array $fields
     * @param array $condition
     * @return false|int|null|string
     * @throws DbException
     */
    public function update(array $fields, $condition = [])
    {
        $this->insert_update_upsert_data = $fields;
        $this->where($condition, []);
        $sql = $this->prepareRawSql('update');
        if ($this->only_show_sql) {
            return $sql;
        }
        $sth = $this->connection->exec($sql);
        if ($sth) {
            return $this->connection->affectedRows();
        } else {
            return false;
        }
    }

    /**
     * @param array $fields
     * @param array $uniqueBy
     * @return false|int|null|string
     * @throws DbException
     */
    public function upsert(array $fields, $uniqueBy = [])
    {
        $this->insert_update_upsert_data = $fields;
        $this->upsert_unique_by = $uniqueBy;
        $sql = $this->prepareRawSql('upsert');
        if ($this->only_show_sql) {
            return $sql;
        }

        /**/
        $sth = $this->connection->exec($sql);
        if ($sth) {
            return $this->connection->affectedRows();
        } else {
            return false;
        }
    }

    /**
     * @param string $type
     * @return string
     * @throws DbException
     */
    private function prepareRawSql($type = 'select')
    {
        $type = mb_strtoupper($type);
        if ($type === 'SELECT') {
            $sql = $this->prepareRawSqlSelect();
        } elseif ($type === 'DELETE') {
            $sql = $this->prepareRawSqlDelete();
        } elseif ($type === 'INSERT') {
            $sql = $this->prepareRawSqlInsert();
        } elseif ($type === 'UPDATE') {
            $sql = $this->prepareRawSqlUpdate();
        } elseif ($type === 'UPSERT') {
            $sql = $this->prepareRawSqlUpsert();
        } else {
            throw new DbException('Wrong query type', 500);
        }

        /* some finally replacement */
        $search = [];
        $replace = [];
        foreach ($this->all_aliases as $k => $v) {
            $search[] = "{$k}.";
            $search[] = "{$v}.";
            $replace[] = "{$this->sql_quote}{$k}{$this->sql_quote}.";
            $replace[] = "{$this->sql_quote}{$v}{$this->sql_quote}.";
        }
        $sql = str_replace($search, $replace, $sql);

        /* return query */
        return replaceMultiSpacesAndNewLine(trim($sql));
    }

    /**
     * @return string
     */
    private function prepareRawWhere()
    {
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

        return $where;
    }

    /**
     * @param string $type
     * @param string $sql
     * @return string
     */
    private function addRawLimit($type, $sql)
    {
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
            $sql = "{$type} {$TOP} {$sql} {$LIMIT}";
        } else {
            $LIMIT = "";
            if ($this->limit > 0) {
                if ($type === 'SELECT') {
                    $LIMIT = " LIMIT {$this->limit} OFFSET {$this->offset}";
                } else {
                    $LIMIT = " LIMIT {$this->limit}";
                }
            }
            $sql = "{$type} {$sql} {$LIMIT}";
        }

        return $sql;
    }

    /**
     * @return string
     * @throws DbException
     */
    private function prepareRawSqlSelect()
    {
        /* begin query */
        $sql = $this->connection->prepareSql("{$this->select} FROM {$this->table} {$this->alias}", []);
        if ($this->select !== " * ") {
            /* if field specified, can't return real modelClass, return stdClass instead */
            $this->class = stdClass::class;
        }

        /* all joins for query */
        if (sizeof($this->join)) {
            foreach ($this->join as $v) {
                $sql .= " {$v['type']} {$v['table']} ON {$v['on']} ";
            }
            /* if join enabled can't return real modelClass, return stdClass instead */
            $this->class = stdClass::class;
        }

        /* where for query */
        $sql .= $this->prepareRawWhere();

        /* order for query */
        $sql .= $this->orderBy;

        /* limit for query and return*/
        return $this->addRawLimit('SELECT', $sql);
    }

    /**
     * @return string
     * @throws DbException
     */
    private function prepareRawSqlDelete()
    {
        /* unset some stuf for delete */
        $this->select = "";
        if ($this->connection->driver === 'sqlsrv') {
            $this->orderBy = "";
            $this->offset = 0;
        }

        /* begin query */
        $sql = $this->connection->prepareSql("FROM {$this->table} {$this->alias}", []);

        /* where for query */
        $sql .= $this->prepareRawWhere();

        /* order for query */
        $sql .= $this->orderBy;

        /* limit for query and return*/
        return $this->addRawLimit('DELETE', $sql);
    }

    /**
     * @return string
     * @throws DbException
     */
    private function prepareRawSqlInsert()
    {
        /* check is multiple array for insert */
        if (isset($this->insert_update_upsert_data[0]) && is_array($this->insert_update_upsert_data[0])) {
            $fields_names_get_from = $this->insert_update_upsert_data[0];
            $values_array = $this->insert_update_upsert_data;
        } else {
            $fields_names_get_from = $this->insert_update_upsert_data;
            $values_array[] = $this->insert_update_upsert_data;
        }

        /* check is associative array with data */
        $is_assoc = true;
        foreach ($fields_names_get_from as $k => $v) {
            if (gettype($k) === 'integer') {
                $is_assoc = false;
                break;
            }
        }

        /* assoc or not */
        if ($is_assoc) {
            $fields_names = array_keys($fields_names_get_from);
            foreach ($fields_names as $k => $insert_field) {
                $insert_field = str_replace([$this->sql_quote, "'", '"', "`"], "", $insert_field);
                $insert_field = "{$this->sql_quote}" . str_replace(".", "{$this->sql_quote}.{$this->sql_quote}", $insert_field) . "{$this->sql_quote}";
                $fields_names[$k] = $insert_field;
            }
            $fields = "(" . implode(', ', $fields_names) . ")";
        } else {
            $fields = "";
        }

        /* values */
        $values_string_array = [];
        foreach ($values_array as $values) {
            $keys = array_keys($values);
            $values_string = "(:" . implode(', :', $keys) . ")";
            $values_string = $this->connection->prepareSql($values_string, $values);
            $values_string_array[] = $values_string;
        }
        $values_sql = implode(', ', $values_string_array);

        /* return */
        return $this->connection->prepareSql("INSERT INTO {$this->table} {$fields} VALUES {$values_sql}", []);
    }

    /**
     * @return string
     * @throws DbException
     */
    private function prepareRawSqlUpdate()
    {
        $f = [];
        foreach ($this->insert_update_upsert_data as $field => $value) {
            $f[] = "{$field} = :$field";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $f);
        $sql .= $this->prepareRawWhere();

        return $this->connection->prepareSql($sql, $this->insert_update_upsert_data);
    }

    /**
     * @return string|string[]
     * @throws DbException
     */
    private function prepareRawSqlUpsert()
    {
        /**/
        $sql = $this->prepareRawSqlInsert();

        /**/
        $f = [];
        foreach ($this->insert_update_upsert_data as $field => $value) {
            $f[$field] = "{$field} = :$field";
        }

        /**/
        if ($this->connection->driver === 'mysql') {

            $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $f);

        } elseif ($this->connection->driver === 'pgsql') {

            if (empty($this->upsert_unique_by)) {
                throw new DbException("You must specify 'uniqueBy'");
            }
            $sql .= " ON CONFLICT (" . implode($this->upsert_unique_by) . ") DO UPDATE SET " . implode(', ', $f);

        } else {

            if (empty($this->upsert_unique_by)) {
                throw new DbException("You must specify 'uniqueBy'");
            }
            $where = [];
            foreach ($this->upsert_unique_by as $k => $v) {
                if (!isset($this->insert_update_upsert_data[$v])) {
                    throw new DbException("You must specify correct 'uniqueBy'");
                }
                unset($f[$v]);
                $where[] = "({$v} = :{$v})";
            }

            $sql = "SET IDENTITY_INSERT {$this->table} ON UPDATE {$this->table} SET " . implode(', ', $f) . " WHERE " . implode($where) ." if @@ROWCOUNT = 0 " . $sql . " SET IDENTITY_INSERT {$this->table} OFF";
            //throw new DbException("This DB-driver doesn't support UPSERT method");

        }

        return $this->connection->prepareSql($sql, $this->insert_update_upsert_data);
    }
}
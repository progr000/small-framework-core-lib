<?php

namespace Core;

use Core\Exceptions\DbException;
use ReflectionObject;
use stdClass;
use PDO;

abstract class ActiveRecordDriver extends stdClass
{
    /** @var string if field name is different from 'id' redeclare this param in Model::class */
    protected static $_primary_key_field = 'id';

    /** @var string */
    protected static $_table_name;

    /** @var string */
    protected static $connection_name;

    /**
     * @return DbDriver
     * @throws DbException
     */
    protected static function getDbConnection()
    {
        if (!static::$connection_name) {
            static::$connection_name = isset(App::$config->get('databases', [])['default-db-connection-name'])
                ? App::$config->get('databases', [])['default-db-connection-name']
                : 'db-main';
        }

        if (isset(App::$DbInstances[static::$connection_name])) {
            return App::$DbInstances[static::$connection_name];
        } else {
            throw new DbException("This connection is not initialized correctly", 500);
        }
    }

    /**
     * @return string
     */
    protected static function getTableName()
    {
        if (static::$_table_name) {
            return static::$_table_name;
        } else {
            //return "{{" . strtolower(basename(str_replace('\\', '/', static::class))) . 's}}';
            $name = basename(str_replace('\\', '/', static::class));
            $name = preg_replace("/([A-Z])/", "_\\1", $name);
            $name = mb_substr($name, 1);
            $name = mb_strtolower($name);
            return "{{" . $name . "s}}";
        }
    }

    /**
     * @return static[]|null
     * @throws DbException
     */
    public static function findAll()
    {
        $sth = self::getDbConnection()->exec("SELECT * FROM " . static::getTableName());
        if ($sth) {
            return $sth->fetchAll(PDO::FETCH_CLASS, static::class);
        }
        return null;
    }

    /**
     * @param int $id
     * @return static|null
     * @throws DbException
     */
    public static function findById($id)
    {
        return self::findOne([static::$_primary_key_field => $id]);
    }

    /**
     * @param array|string $condition
     * @return static|null
     * @throws DbException
     */
    public static function findOne($condition = [])
    {
        $sql_quote = self::getDbConnection()->sql_quote;
        $WHERE = "";
        if (is_array($condition)) {
            if (sizeof($condition)) {
                $el = [];
                foreach ($condition as $k => $v) {
                    $el[] = "({$sql_quote}{$k}{$sql_quote} = :{$k})";
                }
                $WHERE = "WHERE " . implode(" AND ", $el);
            }
        } else {
            $WHERE = "WHERE " . $condition;
            $condition = [];
        }

        if (self::getDbConnection()->driver === 'sqlsrv') {
            $sth = self::getDbConnection()->exec("SELECT TOP 1 * FROM " . static::getTableName() . " {$WHERE}", $condition);
        } else {
            $sth = self::getDbConnection()->exec("SELECT * FROM " . static::getTableName() . " {$WHERE} LIMIT 1", $condition);
        }
        if ($sth) {
            $sth->setFetchMode(PDO::FETCH_CLASS, static::class);
            $res = $sth->fetch(PDO::FETCH_CLASS);
            if ($res) {
                return $res;
            }
        }

        return null;
    }

    /**
     * @param array|string $condition
     * @param int $limit
     * @param array $order
     * @param int $offset
     * @return static[]|false|null
     * @throws DbException
     */
    public static function find($condition = [], $limit = 0, $order = [], $offset = 0)
    {
        $sql_quote = self::getDbConnection()->sql_quote;
        $WHERE = "";
        if (is_array($condition)) {
            if (sizeof($condition)) {
                $el = [];
                foreach ($condition as $k => $v) {
                    $el[] = "({$sql_quote}{$k}{$sql_quote} = :{$k})";
                }
                $WHERE = "WHERE " . implode(" AND ", $el);
            }
        } else {
            $WHERE = "WHERE " . $condition;
            $condition = [];
        }

        $array_order = [];
        foreach ($order as $k => $v) {
            if (in_array(mb_strtoupper($v), ['ASC', 'DESC'])) {
                $array_order[] = "{$k} " . mb_strtoupper($v);
            } else {
                $array_order[] = "{$v} ASC";
            }
        }
        if (sizeof($array_order)) {
            $ORDER_SQL = "ORDER BY " . implode(', ', $array_order);
        } else {
            $ORDER_SQL = "";
        }

        if (self::getDbConnection()->driver === 'sqlsrv') {
            $TOP = "";
            if ($ORDER_SQL === "") {
                if ($limit) {
                    $TOP = " TOP {$limit} ";
                }
            } else {
                if ($limit) {
                    $ORDER_SQL .= " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
                }
            }
            $sth = self::getDbConnection()->exec("SELECT {$TOP} * FROM " . static::getTableName() . " {$WHERE} {$ORDER_SQL}", $condition);
        } else {
            if ($limit) {
                $ORDER_SQL .= " LIMIT {$limit} OFFSET {$offset}";
            }
            $sth = self::getDbConnection()->exec("SELECT * FROM " . static::getTableName() . " {$WHERE} {$ORDER_SQL} ", $condition);
        }
        if ($sth) {
            return $sth->fetchAll(PDO::FETCH_CLASS, static::class);
        }

        return null;
    }

    /**
     * Save ActiveRecord
     * @return bool
     * @throws DbException
     */
    public function save()
    {
        $pkf = static::$_primary_key_field;
        $mappedProperties = $this->mapProperties();
        if ($this->$pkf !== null) {
            return $this->update($mappedProperties);
        } else {
            return $this->insert($mappedProperties);
        }
    }

    /**
     * @param array $mappedProperties
     * @return bool
     * @throws DbException
     */
    private function update(array $mappedProperties)
    {
        $sql_quote = self::getDbConnection()->sql_quote;
        $columns = [];
        $params = [];
        foreach ($mappedProperties as $column => $value) {
            // TODO *** CHECK this
            if (self::getDbConnection()->driver === 'sqlsrv' && $column === static::$_primary_key_field) {
                continue;
            }
            $columns[] = "{$sql_quote}{$column}{$sql_quote} = :{$column}";
            $params[$column] = $value;
        }
        $sql = "UPDATE " . static::getTableName() . " SET " . implode(', ', $columns) . " " .
            "WHERE {$sql_quote}" . static::$_primary_key_field . "{$sql_quote} = " . $this->{static::$_primary_key_field};
        //return self::getDbConnection()->exec($sql, $params);
        self::getDbConnection()->exec($sql, $params);
        return (self::getDbConnection()->affectedRows() > 0);
    }

    /**
     * @param array $mappedProperties
     * @return bool
     * @throws DbException
     */
    private function insert(array $mappedProperties)
    {
        $sql_quote = self::getDbConnection()->sql_quote;
        $filteredProperties = array_filter($mappedProperties);

        $columns = [];
        $params = [];
        $columnsParamName = [];
        foreach ($filteredProperties as $column => $value) {
            $columns[] = "{$sql_quote}{$column}{$sql_quote}";
            $columnsParamName[] = ":{$column}";
            $params[$column] = $value;
        }
        $sql = "INSERT INTO " . static::getTableName() . " (" . implode(', ', $columns) . ") " .
            "VALUES (" . implode(', ', $columnsParamName) . ")";
        $sth = self::getDbConnection()->exec($sql, $params);
        if ($sth) {
            $this->{static::$_primary_key_field} = self::getDbConnection()->lastInsert();
            $this->refresh();
        }

        //return $sth;
        return (self::getDbConnection()->affectedRows() > 0);
    }

    /**
     * @throws DbException
     */
    public function delete()
    {
        $sql_quote = self::getDbConnection()->sql_quote;
        $sql = "DELETE FROM " . static::getTableName() . " WHERE {$sql_quote}" . static::$_primary_key_field . "{$sql_quote} = :id";
        self::getDbConnection()->exec(
            $sql,
            ['id' => $this->{static::$_primary_key_field}]
        );
        $this->{static::$_primary_key_field} = null;
    }

    /**
     * @return array
     */
    private function mapProperties()
    {
        $properties = get_object_vars($this);
        $mappedProperties = [];
        foreach ($properties as $propertyName => $propertyVal) {
            $mappedProperties[$propertyName] = $this->$propertyName;
        }
        return $mappedProperties;
    }

    /**
     * Refresh an ActiveRecord after insert
     * @throws DbException
     */
    private function refresh()
    {
        $objectFromDb = static::findById($this->{static::$_primary_key_field});
        $reflector = new ReflectionObject($objectFromDb);
        $properties = $reflector->getProperties();

        foreach ($properties as $property) {
            if (!$property->isStatic()) {
                $property->setAccessible(true);
                $propertyName = $property->getName();
                $this->$propertyName = $property->getValue($objectFromDb);
            }
        }
    }
}

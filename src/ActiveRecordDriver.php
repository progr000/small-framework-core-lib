<?php

namespace Core;

use Core\Exceptions\DbException;
use ReflectionObject;
use stdClass;
use PDO;

abstract class ActiveRecordDriver
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
            dump(static::$connection_name);
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
     * @return array|null
     * @throws DbException
     */
    public static function findAll()
    {
        $sth = self::getDbConnection()->exec("SELECT * FROM " . static::getTableName());
        if ($sth) {
            return $sth->fetchAll(PDO::FETCH_CLASS, stdClass::class);
        }
        return null;
    }

    /**
     * @param int $id
     * @return stdClass|null
     * @throws DbException
     */
    public static function findById($id)
    {
        return self::findOne([static::$_primary_key_field => $id]);
    }

    /**
     * @param array $condition
     * @return stdClass|null
     * @throws DbException
     */
    public static function findOne(array $condition)
    {
        $WHERE = "";
        if (sizeof($condition)) {
            $el = [];
            foreach ($condition as $k => $v) {
                $el[] = "(`{$k}` = :{$k})";
            }
            $WHERE = "WHERE " . implode(" AND ", $el);
        }

        $sth = self::getDbConnection()->exec("SELECT * FROM " . static::getTableName() . " {$WHERE} LIMIT 1", $condition);
        if ($sth) {
            $sth->setFetchMode(PDO::FETCH_CLASS, stdClass::class);
            $res = $sth->fetch(PDO::FETCH_CLASS);
            if ($res) {
                return $res;
            }
        }

        return null;
    }

    /**
     * @param array $condition
     * @return array|false|null
     * @throws DbException
     */
    public static function find(array $condition)
    {
        $WHERE = "";
        if (sizeof($condition)) {
            $el = [];
            foreach ($condition as $k => $v) {
                $el[] = "(`{$k}` = :{$k})";
            }
            $WHERE = "WHERE " . implode(" AND ", $el);
        }

        $sth = self::getDbConnection()->exec("SELECT * FROM " . static::getTableName() . " {$WHERE} ", $condition);
        if ($sth) {
            return $sth->fetchAll(PDO::FETCH_CLASS, stdClass::class);
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
        $columns = [];
        $params = [];
        foreach ($mappedProperties as $column => $value) {
            $columns[] = "`{$column}` = :{$column}";
            $params[$column] = $value;
        }
        $sql = "UPDATE " . static::getTableName() . " SET " . implode(', ', $columns) . " " .
            "WHERE `" . static::$_primary_key_field . "` = " . $this->{static::$_primary_key_field};
        return self::getDbConnection()->exec($sql, $params);
    }

    /**
     * @param array $mappedProperties
     * @return bool
     * @throws DbException
     */
    private function insert(array $mappedProperties)
    {
        $filteredProperties = array_filter($mappedProperties);

        $columns = [];
        $params = [];
        $columnsParamName = [];
        foreach ($filteredProperties as $column => $value) {
            $columns[] = "`{$column}`";
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

        return $sth;
    }

    /**
     * @throws DbException
     */
    public function delete()
    {
        $sql = "DELETE FROM " . static::getTableName() . " WHERE `" . static::$_primary_key_field . "` = :id";
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

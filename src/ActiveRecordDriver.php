<?php

namespace Core;

use ReflectionObject;
use stdClass;
use PDO;

abstract class ActiveRecordDriver
{
    /** @var string if field name is different from 'id' redeclare this param in Model::class */
    protected static $_primary_key_field = 'id';

    /** @var string */
    protected static $_table_name;

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
     */
    public static function findAll()
    {
        $sth = App::$db->exec("SELECT * FROM " . static::getTableName());
        if ($sth) {
            return $sth->fetchAll(PDO::FETCH_CLASS, stdClass::class);
        }
        return null;
    }

    /**
     * @param int $id
     * @return stdClass|null
     */
    public static function findById($id)
    {
        return self::findOne([static::$_primary_key_field => $id]);
    }

    /**
     * @param array $condition
     * @return stdClass|null
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

        $sth = App::$db->exec("SELECT * FROM " . static::getTableName() . " {$WHERE} LIMIT 1", $condition);
        if ($sth) {
            $sth->setFetchMode(PDO::FETCH_CLASS, stdClass::class);
            $res = $sth->fetch(PDO::FETCH_CLASS);
            if ($res) {
                return $res;
            }
        }

        return null;
    }

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

        $sth = App::$db->exec("SELECT * FROM " . static::getTableName() . " {$WHERE} ", $condition);
        if ($sth) {
            return $sth->fetchAll(PDO::FETCH_CLASS, stdClass::class);
        }

        return null;
    }

    /**
     * Save ActiveRecord
     * @return bool
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
        return App::$db->exec($sql, $params);
    }

    /**
     * @param array $mappedProperties
     * @return bool
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
        $sth = App::$db->exec($sql, $params);
        if ($sth) {
            $this->{static::$_primary_key_field} = App::$db->lastInsert();
            $this->refresh();
        }

        return $sth;
    }

    /**
     *
     */
    public function delete()
    {
        $sql = "DELETE FROM " . static::getTableName() . " WHERE `" . static::$_primary_key_field . "` = :id";
        App::$db->exec(
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

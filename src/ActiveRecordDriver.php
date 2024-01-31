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
            static::$connection_name = config('databases->default-db-connection-name', 'db-main');
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
     * @return array|mixed
     * @throws DbException
     */
    public static function getErrors()
    {
        return self::getDbConnection()->getErrors();
    }

    /**
     * @return static[]|null|false
     * @throws DbException
     */
    public static function findAll()
    {
        return self::find()->get();
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
     * @param int $id
     * @return static
     * @throws DbException
     */
    public static function findOrFail($id)
    {
        $ret = self::findById($id);
        if ($ret) {
            return $ret;
        } else {
            throw new DbException("Model with id = {$id} doesn't exist.");
        }
    }

    /**
     * @param int $id
     * @return static
     * @throws DbException
     */
    public static function findOrNew($id)
    {
        $ret = self::findById($id);
        if ($ret) {
            return $ret;
        } else {
            return new static();
        }
    }

    /**
     * @param array $condition
     * @return $this|ActiveRecordDriver
     * @throws DbException
     */
    public static function firstOrNew($condition = [])
    {
        $ret = self::findOne($condition);
        if ($ret) {
            return $ret;
        } else {
            return new static();
        }
    }

    /**
     * @param int $id
     * @param mixed $or
     * @return static
     * @throws DbException
     */
    public static function findOr($id, $or)
    {
        $ret = self::findById($id);
        if ($ret) {
            return $ret;
        } else {
            return $or;
        }
    }

    /**
     * @param array|string $condition
     * @return static|null
     * @throws DbException
     */
    public static function findOne($condition = [])
    {
        $res = self::find()->where($condition)->limit(1)->get();
        if (isset($res[0])) {
            return $res[0];
        }

        return null;
    }

    /**
     * @param array $relations
     * @param bool $only_show_sql
     * @return QueryBuilderDriver
     * @throws DbException
     */
    public static function with(array $relations, $only_show_sql = false)
    {
        $builder = self::find($only_show_sql);
        $builder->with($relations);
        return $builder;
    }

    /**
     * @param bool $only_show_sql
     * @return QueryBuilderDriver
     * @throws DbException
     */
    public static function find($only_show_sql = false)
    {
        return new QueryBuilderDriver(self::getDbConnection(), static::class, static::getTableName(), $only_show_sql);
    }

    /**
     * @param bool $only_show_sql
     * @return QueryBuilderDriver
     * @throws DbException
     */
    public static function table($only_show_sql = false)
    {
        return self::find($only_show_sql);
    }

    /**
     * @param bool $only_show_sql
     * @return QueryBuilderDriver
     * @throws DbException
     */
    public static function query($only_show_sql = false)
    {
        return self::find($only_show_sql);
    }

    /**
     * @param array $fields
     * @return false|int|string|null
     * @throws DbException
     */
    public static function insert(array $fields)
    {
        return self::table()->insert($fields);
    }

    /**
     * @param array $fields
     * @param array $condition
     * @return false|int|string|null
     * @throws DbException
     */
    public static function update(array $fields, $condition = [])
    {
        return self::table()->update($fields, $condition);
    }

    /**
     * @param array $fields
     * @param array $uniqueBy
     * @return false|int|string|null
     * @throws DbException
     */
    public static function upsert(array $fields, $uniqueBy = [])
    {
        return self::table()->upsert($fields, $uniqueBy);
    }

    /**
     * @param array|string $condition
     * @param array $params
     * @return false|int|null
     * @throws DbException
     */
    public static function deleteRecords($condition = [], $params = [])
    {
        return self::find()->delete($condition, $params);
    }

    /**
     * @throws DbException
     */
    public static function truncate()
    {
        return self::execRawSql("TRUNCATE TABLE " . static::getTableName());
    }

    /**
     * @param string $sql
     * @param array $params
     * @return array|false|null
     * @throws DbException
     */
    public static function execRawSql($sql, $params=[])
    {
        $sth = self::getDbConnection()->exec($sql, $params);
        if ($sth) {
            return $sth->fetchAll(PDO::FETCH_CLASS, stdClass::class);
        }
        return null;
    }

    /**
     * @param array $only
     * @return void
     */
    public function load(array $data, array $only = [])
    {
        if (sizeof($only)) {
            foreach ($only as $v) {
                unset($data[$v]);
            }
        }

        foreach ($data as $k => $v) {
            $this->{$k} = $v;
        }
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
        if (isset($this->$pkf)) { // TODO repair case when I want to change primary key and this key already exists - than give not error but update another record,
            return $this->_update($mappedProperties);
        } else {
            return $this->_insert($mappedProperties);
        }
    }

    /**
     * @param array $mappedProperties
     * @return bool
     * @throws DbException
     */
    private function _update(array $mappedProperties)
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
        return (sizeof(self::getDbConnection()->getErrors()) == 0);
    }

    /**
     * @param array $mappedProperties
     * @return bool
     * @throws DbException
     */
    private function _insert(array $mappedProperties)
    {
        $sql_quote = self::getDbConnection()->sql_quote;
        //$filteredProperties = array_filter($mappedProperties);
        $filteredProperties = $mappedProperties;

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
        } else {
            return false;
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
     * @throws DbException
     */
    public function getError()
    {
        return self::getErrors();
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

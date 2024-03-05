<?php

namespace Core;

use Core\Exceptions\DbException;
use Core\Providers\ExtendedStdClass;
use ReflectionObject;
use stdClass;
use PDO;

abstract class ActiveRecordDriver extends ExtendedStdClass
{
    /**
     * For store some technical data during script alive
     * @var stdClass
     */
    protected $___technical_data;

    /**
     * if field name is different from 'id' redeclare this param in Model::class
     * @var string
     */
    protected static $_primary_key_field = 'id';

    /**
     * The table associated with the model.
     * @var string|null
     */
    protected static $_table_name;

    /**
     * The connection name for the model.
     * @var string|null
     */
    protected static $connection_name;

    /**
     * Indicates if the model should be timestamped.
     * @var bool
     */
    protected static $timestamps = false;

    /**
     * The name of the "created at" column.
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * The model's default values for attributes.
     * Default values for initialization
     * fields on create new object (record)
     * @var array
     */
    protected static $defaults = [];

    /**
     * @param array $fields
     */
    public function __construct(array $fields = [])
    {
        /* determination is new record or old from database */
        $is_new = true;
        $dbg = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        foreach ($dbg as $k => $item) {
            if (isset($item['function'])) {
                //dump($k . "=>" . $item['function']);
                if (mb_strrpos(mb_strtolower($item['function']), 'fetch') !== false) {
                    $is_new = false;
                    break;
                }
            }
        }
        /* init ___technical_data */
        $this->___technical_data = new stdClass();
        $this->___technical_data->_is_new_record = $is_new;
        $this->___technical_data->_current_pkf_value = null;
        if ($this->___technical_data->_is_new_record) {
            /* fill by defaults */
            foreach (static::$defaults as $key => $value) {
                $this->{$key} = $value;
            }
            /* fill by given data if needed */
            foreach ($fields as $key => $value) {
                $this->{$key} = $value;
            }
        } else {
            $pkf = static::$_primary_key_field;
            $this->___technical_data->_current_pkf_value = $this->{$pkf};
        }
    }

    /**
     * @param array $fields
     * @return static|ActiveRecordDriver
     */
    public static function create(array $fields = [])
    {
        return new static($fields);
    }

    /**
     * @param string $propertyName
     * @param mixed $propertyValue
     * @return void
     */
    public function __setTechnicalData($propertyName, $propertyValue)
    {
        $this->___technical_data->{$propertyName} = $propertyValue;
    }

    /**
     * @param string $propertyName
     * @return void
     */
    public function __getTechnicalData($propertyName)
    {
        return $this->___technical_data->{$propertyName};
    }

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
        if (static::$timestamps) {
            $fields[static::UPDATED_AT] = self::getSqlDate();
        }
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
        if (static::$timestamps) {
            $fields[static::CREATED_AT] = self::getSqlDate();
            $fields[static::UPDATED_AT] = self::getSqlDate();
        }
        return self::table()->upsert($fields, $uniqueBy, static::CREATED_AT);
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
            return $sth->fetchAll(PDO::FETCH_CLASS, ExtendedStdClass::class);
        }
        return null;
    }

    /**
     * @param array $data
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

    private static function getSqlDate()
    {
        // dump(self::getDbConnection()->driver);
        // TODO format of the date can be depend of self::getDbConnection()->driver
        return date('Y-m-d H:i:s');
    }

    /**
     * Save ActiveRecord
     * @return bool
     * @throws DbException
     */
    public function save()
    {
        // updated_at and created_at
        if (static::$timestamps) {
            $u_key = static::UPDATED_AT;
            $c_key = static::CREATED_AT;
            $this->{$u_key} = self::getSqlDate();
            if ($this->___technical_data->_is_new_record) {
                $this->{$c_key} = self::getSqlDate();
            }
        }
        // get properties
        $mappedProperties = $this->mapProperties();
        if ($this->___technical_data->_is_new_record) {
            return $this->_insert($mappedProperties);
        } else {
            return $this->_update($mappedProperties);
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
            if (self::getDbConnection()->driver === DbDriver::mssql_driver && $column === static::$_primary_key_field) {
                continue;
            }
            $columns[] = "{$sql_quote}{$column}{$sql_quote} = :{$column}";
            $params[$column] = $value;
        }
        /**/
        $current_pkf_value = $this->{static::$_primary_key_field};
        if (!is_null($this->___technical_data->_current_pkf_value)) {
            $current_pkf_value = $this->___technical_data->_current_pkf_value;
        }
        /**/
        $sql = "UPDATE " . static::getTableName() . " SET " . implode(', ', $columns) . " " .
            "WHERE {$sql_quote}" . static::$_primary_key_field . "{$sql_quote} = " . $current_pkf_value;
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
            if ($propertyName === '___technical_data') {
                continue;
            }
            $mappedProperties[$propertyName] = $this->{$propertyName};
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
                $this->{$propertyName} = $property->getValue($objectFromDb);
            }
        }

        $pkf = static::$_primary_key_field;
        $this->___technical_data->_is_new_record = false;
        $this->___technical_data->_current_pkf_value = $this->{$pkf};
    }
}

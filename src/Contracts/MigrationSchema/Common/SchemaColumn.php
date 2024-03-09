<?php

namespace Core\Contracts\MigrationSchema\Common;

use stdClass;
use Core\Exceptions\DbException;

/**
 * @property SchemaTable $schemaTable
 * @property string $name
 * @property bool $is_index
 * @property bool $is_unique
 * @property bool $is_foreign
 */
class SchemaColumn extends stdClass
{
    const TYPE_MANUAL_RAW = 'manual_raw';

    const TYPE_STRING = 'string';
    const TYPE_CHAR = 'char';
    const TYPE_TEXT = 'text';
    const TYPE_BLOB = 'blob';

    const TYPE_DATE = 'date';
    const TYPE_TIME = 'time';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_YEAR = 'year';

    const TYPE_BIT = 'bit';
    const TYPE_BOOL = 'bool';
    const TYPE_SMALLINT = 'smallint';
    const TYPE_INT = 'int';
    const TYPE_BIGINT = 'bigint';

    const TYPE_FLOAT = 'float';
    const TYPE_DOUBLE = 'double';
    const TYPE_DECIMAL = 'decimal';
    public $schemaTable;

    /** @var string */
    private $_manual_text = "";
    /** @var string */
    private $_type;
    /** @var bool */
    private $_is_pkf = false;
    /** @var bool */
    private $_is_auto_increment = false;
    /** @var bool */
    private $_nullable = true;
    /** @var bool */
    private $_unsigned = false;
    /** @var string */
    private $_comment;
    /** @var mixed */
    private $_default;
    /** @var int */
    private $_length;
    /** @var array */
    private $_columns;
    /** @var array */
    private $_foreign_data = [];
    /** @var array  */
    private $_double_params = ['total' => 8, 'decimal' => 2];

    /** @var string  */
    private $name;
    /** @var bool */
    private $is_index = false;
    /** @var bool */
    private $is_unique = false;
    /** @var bool */
    private $is_foreign = false;

    /**
     * @param SchemaTable $schemaTable
     * @param string $name
     * @param bool $is_index
     * @param bool $is_unique
     * @param bool $is_foreign
     */
    public function __construct(SchemaTable $schemaTable, $name, $is_index = false, $is_unique = false, $is_foreign = false)
    {
        $this->schemaTable = $schemaTable;
        $this->name = $name;
        $this->is_index = $is_index;
        $this->is_unique = $is_unique;
        $this->is_foreign = $is_foreign;
    }

    /**
     * @param string $name
     * @return void
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return null;
    }

    /**
     * @param $text
     * @return void
     */
    public function manual_raw($text)
    {
        $this->_type = self::TYPE_MANUAL_RAW;
        $this->_manual_text = $text;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function string($length = 255)
    {
        $this->_type = self::TYPE_STRING;
        $this->_length = $length;
        return $this;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function char($length = 1)
    {
        $this->_type = self::TYPE_CHAR;
        $this->_length = $length;
        return $this;
    }

    /**
     * @return $this
     */
    public function text()
    {
        $this->_type = self::TYPE_TEXT;
        return $this;
    }

    /**
     * @return $this
     */
    public function blob()
    {
        $this->_type = self::TYPE_BLOB;
        return $this;
    }

    /**
     * @return $this
     */
    public function date()
    {
        $this->_type = self::TYPE_DATE;
        return $this;
    }

    /**
     * @return $this
     */
    public function time()
    {
        $this->_type = self::TYPE_TIME;
        return $this;
    }

    /**
     * @return $this
     */
    public function datetime()
    {
        $this->_type = self::TYPE_DATETIME;
        return $this;
    }

    /**
     * @return $this
     */
    public function timestamp()
    {
        $this->_type = self::TYPE_TIMESTAMP;
        return $this;
    }

    /**
     * @return $this
     */
    public function year()
    {
        $this->_type = self::TYPE_YEAR;
        return $this;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function bit($length = 1)
    {
        $this->_type = self::TYPE_BIT;
        $this->_length = $length;
        return $this;
    }

    /**
     * @return $this
     */
    public function bool()
    {
        $this->_type = self::TYPE_BOOL;
        return $this;
    }

    /**
     * @param int|null $length
     * @return $this
     */
    public function int($length = null)
    {
        $this->_type = self::TYPE_INT;
        $this->_length = $length;
        return $this;
    }

    /**
     * @param int $total
     * @param int $decimal
     * @return $this
     */
    public function float($total = 8, $decimal = 2)
    {
        $this->_type = self::TYPE_FLOAT;
        $this->_double_params = ['total' => $total, 'decimal' => $decimal];
        return $this;
    }

    /**
     * @param int $total
     * @param int $decimal
     * @return $this
     */
    public function double($total = 8, $decimal = 2)
    {
        $this->_type = self::TYPE_DOUBLE;
        $this->_double_params = ['total' => $total, 'decimal' => $decimal];
        return $this;
    }

    /**
     * @param int $total
     * @param int $decimal
     * @return $this
     */
    public function decimal($total = 8, $decimal = 2)
    {
        $this->_type = self::TYPE_DECIMAL;
        $this->_double_params = ['total' => $total, 'decimal' => $decimal];
        return $this;
    }

    /**
     * @return $this
     */
    public function primaryKey($auto_increment = true)
    {
        $this->_is_pkf = true;
        $this->_is_auto_increment = $auto_increment;
        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function nullable($value = true)
    {
        $this->_nullable = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsigned()
    {
        $this->_unsigned = true;
        return $this;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function len($length)
    {
        $this->_length = $length;
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function defaultVal($value)
    {
        $this->_default = $value;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function comment($text)
    {
        $this->_comment = $text;
        return $this;
    }

    /**
     * @param string|null $index_name
     * @return $this
     */
    public function index($index_name = null)
    {
        if (is_null($index_name)) {
            $index_name = "idx_" . $this->name;
        }
        $this->schemaTable->index($index_name, [$this->name]);
        return $this;
    }

    /**
     * @param string|null $unique_index_name
     * @return $this
     */
    public function unique($unique_index_name = null)
    {
        if (is_null($unique_index_name)) {
            $unique_index_name = "unique_idx_" . $this->name;
        }
        $this->schemaTable->unique($unique_index_name, [$this->name]);
        return $this;
    }

    /**
     * @param array $columns
     * @return void
     */
    public function setIndexColumns(array $columns)
    {
        $this->_columns = $columns;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setForeignData($data)
    {
        $this->_foreign_data = $data;
    }

    /**
     * @return string
     * @throws DbException
     */
    public function __toString()
    {
        $className = "Core\\Contracts\\MigrationSchema\\{$this->schemaTable->db->getDriver()}PrepareField";
        if (class_exists($className) || !method_exists($className, 'prepareColumn')) {
            return (new $className($this))->prepareColumn();
        } else {
            throw new DbException(get_class($this) . "::__toString(): class for prepare field for this DbDriver doesn't exists or hasn't method prepareColumn()");
        }

    }
}
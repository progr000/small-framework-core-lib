<?php

namespace Core\Contracts\MigrationSchema;

use Core\Contracts\MigrationSchema\Common\SchemaColumn;
use Core\Exceptions\DbException;
use Core\Interfaces\PrepareFieldInterface;

class mysqlPrepareField extends PrepareFieldInterface
{
    /**
     * @return string
     * @throws DbException
     */
    public function prepareColumn()
    {

        if (!$this->item->__get('is_index') && !$this->item->__get('is_unique')) {
            /** columns **/

            /* name */
            $this->itemString = "{$this->quote}{$this->item->__get('name')}{$this->quote}";

            /* can append something before CREATE TABLE statement (needed for pgsql for create sequence) */
            //$this->item->schemaTable->append = "pgsql sequence for example";

            /* type */
            switch ($this->item->__get('_type')) {
                case SchemaColumn::TYPE_INT:
                case SchemaColumn::TYPE_SMALLINT:
                case SchemaColumn::TYPE_BIGINT:
                case SchemaColumn::TYPE_BIT:
                case SchemaColumn::TYPE_BOOL:
                    $this->int();
                    break;
                case SchemaColumn::TYPE_STRING:
                case SchemaColumn::TYPE_CHAR:
                case SchemaColumn::TYPE_TEXT:
                case SchemaColumn::TYPE_BLOB:
                    $this->string();
                    break;
                case SchemaColumn::TYPE_DATE:
                case SchemaColumn::TYPE_TIME:
                case SchemaColumn::TYPE_DATETIME:
                case SchemaColumn::TYPE_TIMESTAMP:
                case SchemaColumn::TYPE_YEAR:
                    $this->date();
                    break;
                case SchemaColumn::TYPE_FLOAT:
                case SchemaColumn::TYPE_DOUBLE:
                case SchemaColumn::TYPE_DECIMAL:
                    $this->float();
                    break;
                case SchemaColumn::TYPE_MANUAL_RAW:
                    $this->manual_raw();
                    break;
                default:
                    throw new DbException(get_class($this) . "::prepareColumn(): You should specify column type for column `{$this->item->name}`");
            }

            /* null */
            if (!$this->item->__get('_nullable')) {
                $this->itemString .= " NOT NULL";
            }

            /* primary key */
            if ($this->item->__get('_is_pkf')) {
                if ($this->item->schemaTable->pkf_is_defined === false) {
                    $this->itemString .= " PRIMARY KEY";
                    $this->item->schemaTable->pkf_is_defined = $this->item->name;
                    if ($this->item->__get('_is_auto_increment')) {
                        $this->itemString .= " AUTO_INCREMENT";
                    }
                } else {
                    throw new DbException(get_class($this) . "::prepareColumn(): Multiple primary key defined `{$this->item->schemaTable->pkf_is_defined}` and `{$this->item->name}`");
                }
            }

            /* comment */
            if (is_string($this->item->__get('_comment'))) {
                $_comment = str_replace("'", "`", $this->item->__get('_comment'));
                $this->itemString .= " COMMENT '{$_comment}'";
            }

        } else {
            /** indexes **/
            if (!$this->item->__get('is_foreign')) {
                $this->itemString = "INDEX {$this->quote}{$this->item->name}{$this->quote} ({$this->quote}" .
                    implode("{$this->quote}, {$this->quote}", $this->item->__get('_columns')) .
                    "{$this->quote})";
                if ($this->item->__get('is_unique')) {
                    $this->itemString = "UNIQUE {$this->itemString}";
                }
            } else {
                $this->prepareForeign();
            }
        }

        return PHP_EOL . $this->itemString;
    }

    /**
     * @return void
     */
    private function manual_raw()
    {
        $this->itemString .= $this->item->__get('_manual_text');
    }

    /**
     * @return string
     */
    protected function prepareForeign()
    {
        $data = $this->item->__get('_foreign_data');
        $this->itemString =
            "CONSTRAINT {$this->quote}{$this->item->name}{$this->quote} " .
            "FOREIGN KEY ({$this->quote}" . implode("{$this->quote}, {$this->quote}", $data['column']) . "{$this->quote}) " .
            "REFERENCES {$data['refTable']} ({$this->quote}" . implode("{$this->quote}, {$this->quote}", $data['refColumn']) . "{$this->quote}) " .
            "ON UPDATE {$data['update']} ON DELETE {$data['delete']}";
        return $this->itemString;
    }

    /**
     * @return void
     */
    private function prepareUnsigned()
    {
        if ($this->item->__get('_unsigned') &&
            in_array($this->item->__get('_type'), [
                SchemaColumn::TYPE_INT,
                SchemaColumn::TYPE_SMALLINT,
                SchemaColumn::TYPE_BIGINT,
                SchemaColumn::TYPE_FLOAT,
                SchemaColumn::TYPE_DOUBLE,
                SchemaColumn::TYPE_DECIMAL,
            ])
        ) {
            $this->itemString .= " UNSIGNED";
        }
    }

    /**
     * prepare smallint, int, bigint fields
     * @return void
     */
    private function int()
    {
        $this->itemString .= " {$this->item->__get('_type')}";
        if ($this->item->__get('_type') !== SchemaColumn::TYPE_BOOL) {
            /* length */
            if ($this->item->__get('_length')) {
                $this->itemString .= "({$this->item->__get('_length')})";
            }
            /* unsigned */
            if ($this->item->__get('_type') !== SchemaColumn::TYPE_BIT) {
                $this->prepareUnsigned();
            }
        }
        /* default */
        if (!$this->item->__get('_is_auto_increment') && !is_null($this->item->__get('_default'))) {
            $this->itemString .= " DEFAULT " . intval($this->item->__get('_default'));
        }
    }

    /**
     * @return void
     */
    private function float()
    {
        $this->itemString .= " {$this->item->__get('_type')}";
        $_double_params = $this->item->__get('_double_params');
        $this->itemString .= "({$_double_params['total']}, {$_double_params['decimal']})";
        /* unsigned */
        $this->prepareUnsigned();
        /* default */
        if (!is_null($this->item->__get('_default'))) {
            $this->itemString .= " DEFAULT " . doubleval($this->item->__get('_default'));
        }
    }

    /**
     * prepare varchar, char, text and blob fields
     * @return void
     */
    private function string()
    {
        /* type */
        $type = $this->item->__get('_type');
        if ($type === SchemaColumn::TYPE_STRING) {
            $type = 'varchar';
        }

        if (!in_array($type, [SchemaColumn::TYPE_BLOB, SchemaColumn::TYPE_TEXT])) {
            /* length */
            $this->itemString .= " {$type}({$this->item->__get('_length')})";
            /* default value */
            if (!is_null($this->item->__get('_default'))) {
                /* default */
                $this->itemString .= " DEFAULT '{$this->item->__get('_default')}'";
            }
        } else {
            $this->itemString .= " {$type}";
        }
    }

    /**
     * prepare date, time, datetime, timestamp and year fields
     * @return void
     */
    private function date()
    {
        $type = $this->item->__get('_type');
        $this->itemString .= " {$type}";
        if (!is_null($this->item->__get('_default'))) {
            /* default */
            if ($type !== SchemaColumn::TYPE_YEAR) {
                $this->itemString .= " DEFAULT '{$this->item->__get('_default')}'";
            } else {
                $this->itemString .= " DEFAULT {$this->item->__get('_default')}";
            }
        }
    }
}
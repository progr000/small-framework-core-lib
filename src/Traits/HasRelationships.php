<?php

namespace Core\Traits;

use Core\ActiveRecordDriver;
use Core\Exceptions\DbException;
use Core\Providers\RelationshipContainer;

trait HasRelationships
{
    /**
     * @param string|null $key
     * @return mixed
     */
    private function prepareKey($key)
    {
        $class = get_class($this);
        if (is_null($key)) {
            $key = $class::$_primary_key_field;
        }
        return $key;
    }

    /**
     * @return void
     */
    private function detectCaller()
    {
        $this->___technical_data->caller = null;
        $dbg = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $this->___technical_data->caller = isset($dbg[2]['function']) ? $dbg[2]['function'] : null;
    }

    /**
     * @return void
     */
    private function prepareEagerData()
    {
        $this->___technical_data->enable_eager = false;
        $this->___technical_data->next_eager = null;
        // $this->___technical_data->unique_result_key it is $_unique_main_result_key
        foreach (RelationshipContainer::$_withRelations[$this->___technical_data->unique_result_key] as $r_key => $r_value) {
            if ($r_key === $this->___technical_data->caller || $r_value === $this->___technical_data->caller) {
                $this->___technical_data->enable_eager = true;
                if (is_array($r_value) || is_callable($r_value)) {
                    $this->___technical_data->next_eager = $r_value;
                }
                break;
            }
        }
    }

    /**
     * @param string $keyField
     * @return array
     */
    private function getAllValuesForLocalKey($keyField)
    {
        $keyField_values = [];
        // $this->___technical_data->unique_result_key it is $_unique_main_result_key
        foreach (RelationshipContainer::$_mainResultContainer[$this->___technical_data->unique_result_key] as $obj) {
            if (property_exists($obj, $keyField)) {
                $keyField_values[] = $obj->$keyField;
            }
        }
        return array_unique($keyField_values);
    }

    /**
     * @param string $related
     * @param string $foreignKey
     * @param string|null $localKey
     * @return ActiveRecordDriver|null
     * @throws DbException
     */
    public function hasOne($related, $foreignKey, $localKey = null)
    {
        /** @var $related ActiveRecordDriver */

        // prepare $localKey from given model, if not presented
        $localKey = $this->prepareKey($localKey);

        // prepare some technical stuff for get result without querying if possible (eagerRelations)
        if (isset($this->___technical_data->unique_result_key)) {
            $_unique_main_result_key = $this->___technical_data->unique_result_key;
            // detect caller function from model (which call this function)
            $this->detectCaller();
            if (isset(RelationshipContainer::$_withRelations[$_unique_main_result_key])) {
                // check is eager needed
                $this->prepareEagerData();
                if ($this->___technical_data->enable_eager) {
                    $_unique_relative_result_key = md5($_unique_main_result_key . $related . $foreignKey . $localKey . "hasOne");
                    return $this->commonPartForAll($_unique_relative_result_key, $related, $localKey, $foreignKey, false);
                }
            }
        }

        // return non-eager-relations
        return $related::findOne([$foreignKey => $this->{$localKey}]);
    }

    /**
     * @param string $related
     * @param string $foreignKey
     * @param string|null $localKey
     * @return array|false|string
     * @throws DbException
     */
    public function hasMany($related, $foreignKey, $localKey = null)
    {
        /** @var $related ActiveRecordDriver */

        // prepare $localKey from given model, if not presented
        $localKey = $this->prepareKey($localKey);

        // prepare some technical stuff for get result without querying if possible (eagerRelations)
        if (isset($this->___technical_data->unique_result_key)) {
            $_unique_main_result_key = $this->___technical_data->unique_result_key;
            // detect caller function from model (which call this function)
            $this->detectCaller();
            if (isset(RelationshipContainer::$_withRelations[$_unique_main_result_key])) {
                // check is eager needed
                $this->prepareEagerData();
                if ($this->___technical_data->enable_eager) {
                    $_unique_relative_result_key = md5($_unique_main_result_key . $related . $foreignKey . $localKey . "hasMany");
                    return $this->commonPartForAll($_unique_relative_result_key, $related, $localKey, $foreignKey, true);
                }
            }
        }

        // return non-eager-relations
        return $related::find()->where([$foreignKey => $this->{$localKey}])->all();
    }

    /**
     * @param string $related
     * @param string $localKey
     * @param string|null $foreignKey
     * @return ActiveRecordDriver|null
     * @throws DbException
     */
    public function belongsTo($related, $localKey, $foreignKey = null)
    {
        /** @var $related ActiveRecordDriver */

        // prepare $foreignKey from given model, if not presented
        $foreignKey = $this->prepareKey($foreignKey);

        // prepare some technical stuff for get result without querying if possible (eagerRelations)
        if (isset($this->___technical_data->unique_result_key)) {
            $_unique_main_result_key = $this->___technical_data->unique_result_key;
            // detect caller function from model (which call this function)
            $this->detectCaller();
            if (isset(RelationshipContainer::$_withRelations[$_unique_main_result_key])) {
                // check is eager needed
                $this->prepareEagerData();
                if ($this->___technical_data->enable_eager) {
                    $_unique_relative_result_key = md5($_unique_main_result_key . $related . $localKey . $foreignKey . "belongsTo");
                    return $this->commonPartForAll($_unique_relative_result_key, $related, $localKey, $foreignKey, false);
                }
            }
        }

        // return non-eager-relations
        return $related::findOne([$foreignKey => $this->{$localKey}]);
    }

    /**
     * @param string $_unique_relative_result_key
     * @param string $localKey
     * @param string $related
     * @param string $foreignKey
     * @param bool $many
     * @return mixed|null
     * @throws DbException
     */
    private function commonPartForAll(
        $_unique_relative_result_key,
        $related,
        $localKey,
        $foreignKey,
        $many = false
    )
    {
        /** @var $related ActiveRecordDriver */

        if (!isset(RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key])) {
            // get all values for $localKey from RelationshipContainer::$_mainResultContainer[$_unique_main_result_key]
            $localKey_values = $this->getAllValuesForLocalKey($localKey);

            // get all relative data for this $localKey_values
            $relatedQB = $related::find();
            if (is_array($this->___technical_data->next_eager)) {
                $relatedQB->with($this->___technical_data->next_eager);
            }
            $relatedQB->where("{$foreignKey} IN (" . implode(", ", $localKey_values) . ")");
            if (is_callable($this->___technical_data->next_eager)) {
                $call = $this->___technical_data->next_eager;
                $call($relatedQB);
            }
            $relatedRes = $relatedQB->all();
            RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key] = [];
            foreach ($relatedRes as $key => $obj) {
                if ($many) {
                    RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key][$obj->$foreignKey][] = $obj;
                } else {
                    RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key][$obj->$foreignKey] = $obj;
                }
            }
            unset($relatedRes);
        }

        // return eager-relations
        return isset(RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key][$this->{$localKey}])
            ? RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key][$this->{$localKey}]
            : null;
    }
}
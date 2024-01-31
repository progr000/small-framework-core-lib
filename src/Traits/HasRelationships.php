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
     * @param ActiveRecordDriver|mixed $related
     * @param string $foreignKey
     * @param string|null $localKey
     * @return ActiveRecordDriver|null
     * @throws DbException
     */
    public function hasOne($related, $foreignKey, $localKey = null)
    {
        $localKey = $this->prepareKey($localKey);
        return $related::findOne([$foreignKey => $this->{$localKey}]);
    }

    /**
     * @param ActiveRecordDriver|mixed $related
     * @param string $foreignKey
     * @param string|null $localKey
     * @return array|false|string
     * @throws DbException
     */
    public function hasMany($related, $foreignKey, $localKey = null)
    {
        $localKey = $this->prepareKey($localKey);
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
        if (isset($this->___unique_result_key___)) {
            $_unique_main_result_key = $this->___unique_result_key___;
            $dbg = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
            $caller = isset($dbg[1]['function']) ? $dbg[1]['function'] : null;
            //dd($caller, RelationshipContainer::$_withRelations[$_unique_main_result_key]);
            if (isset(RelationshipContainer::$_withRelations[$_unique_main_result_key]) && in_array($caller, RelationshipContainer::$_withRelations[$_unique_main_result_key])) {
                $_unique_relative_result_key = md5($_unique_main_result_key . $related . $localKey . $foreignKey . "belongsTo");
                if (!isset(RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key])) {
                    //dump('eager querying');
                    // get all values for $localKey from RelationshipContainer::$_mainResultContainer[$_unique_main_result_key]
                    $localKey_values = [];
                    foreach (RelationshipContainer::$_mainResultContainer[$_unique_main_result_key] as $obj) {
                        if (property_exists($obj, $localKey)) {
                            $localKey_values[] = $obj->$localKey;
                        }
                    }
                    $localKey_values = array_unique($localKey_values);

                    // get all relative data for this $localKey_values
                    $relatedRes = $related::find()
                        ->where("{$foreignKey} IN (" . implode(", ", $localKey_values) . ")")
                        ->all();
                    RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key] = [];
                    foreach ($relatedRes as $key => $obj) {
                        RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key][$obj->$foreignKey] = $obj;
                    }
                    unset($relatedRes);
                }

                return isset(RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key][$this->{$localKey}])
                    ? RelationshipContainer::$_relatedResultsContainer[$_unique_relative_result_key][$this->{$localKey}]
                    : null;

            }
        }
        //dump('not eager querying');
        return $related::findOne([$foreignKey => $this->{$localKey}]);
    }
}
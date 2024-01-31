<?php

namespace Core\Traits;

use Core\ActiveRecordDriver;
use Core\Exceptions\DbException;

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
     * @param ActiveRecordDriver|mixed $related
     * @param string $localKey
     * @param string|null $foreignKey
     * @return ActiveRecordDriver|null
     * @throws DbException
     */
    public function belongsTo($related, $localKey, $foreignKey = null)
    {
        $foreignKey = $this->prepareKey($foreignKey);
        return $related::findOne([$foreignKey => $this->{$localKey}]);
    }
}
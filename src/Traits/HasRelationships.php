<?php

namespace Core\Traits;

use Core\ActiveRecordDriver;
use Core\Exceptions\DbException;

trait HasRelationships
{
    /**
     * @throws DbException
     */
    public function hasOne($related, $foreignKey, $localKey = null)
    {
        $class = get_class($this);
        if (is_null($localKey)) {
            $localKey = $class::$_primary_key_field;
        }
        //dd($class, $related, $foreignKey, $localKey);
        /** @var ActiveRecordDriver $related */
        return $related::findOne([$foreignKey => $this->{$localKey}]);
    }

    /**
     * @throws DbException
     */
    public function hasMany($related, $foreignKey, $localKey = null)
    {
        $class = get_class($this);
        if (is_null($localKey)) {
            $localKey = $class::$_primary_key_field;
        }
        /** @var ActiveRecordDriver $related */
        return $related::find()->where([$foreignKey => $this->{$localKey}])->all();
    }
}
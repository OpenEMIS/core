<?php

namespace App\Traits;

trait NumericId
{
    public static function getNextId()
    {
        return \DB::transaction(function () {
            $maxId = self::max('id');
            return (int) $maxId + 1;
        });
    }

    protected static function bootNumericId()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = self::getNextId();
            }
        });
    }
}

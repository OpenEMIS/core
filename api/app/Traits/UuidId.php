<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait UuidId
{
    public static function getNextId()
    {
        return \DB::transaction(function () {
            $nextId = Str::uuid();
            return (string) $nextId;
        });
    }

    protected static function bootUuidId()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = self::getNextId();
            }
        });
    }
}

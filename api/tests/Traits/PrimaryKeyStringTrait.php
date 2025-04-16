<?php

namespace Tests\Traits;

trait PrimaryKeyStringTrait
{
    public function getPrimaryKeyString($record)
    {
        $primaryKeys = $record->getKeyName();
        if (!is_array($primaryKeys)) {
            $primaryKeys = [$primaryKeys];
        }

        $keyString = '';
        foreach ($primaryKeys as $key) {
            $keyString .= "/$key/" . $record->$key;
        }

        return $keyString;
    }
}

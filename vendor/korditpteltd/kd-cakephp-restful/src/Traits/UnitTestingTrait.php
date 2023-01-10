<?php
namespace Restful\Traits;

use Cake\Core\Configure;

trait UnitTestingTrait {
    private static $connectionName = 'default';

    public static function setConnectionName($connectionName)
    {
        self::$connectionName = $connectionName;
    }

    public static function getConnectionName()
    {
        return self::$connectionName;
    }

    public static function defaultConnectionName()
    {
        if (Configure::read('debug')) {
            return self::getConnectionName();
        } else {
            return 'default';
        }
    }
}

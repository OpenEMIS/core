<?php
namespace MoodleApi\Controller\Component\MoodleFunction;

class MoodleFunction
{
    protected static $_functionParam = "";

    protected static $_userAllowedParams 
        = [];

    protected static $_userMandatoryParams
        = [];

    public static function getFunctionParam()
    {
        return static::$_functionParam;
    }

    public static function _checkUserData($data) 
    {
        $mandatoryParams = static::$_userMandatoryParams;
        $allowedParams = static::$_userAllowedParams;
        $mandatoryFieldCount = 0;

        foreach ($data as $param => $value) {
            if (in_array($param, $allowedParams)) {
                if (in_array($param, $mandatoryParams)) {
                    $mandatoryFieldCount++;
                }
            } else {
                //TODO - ERROR LOGGING
                return false;
            }
        }

        if ($mandatoryFieldCount == count($mandatoryParams)) {
            return true;
        } else {
            //TODO - ERROR LOGGING
            return false;
        }
    }
}
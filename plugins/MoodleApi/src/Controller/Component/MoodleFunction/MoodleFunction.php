<?php
/**
 * MoodleFunction - Abstraction class. All other functions extends this class.
 * SEE MoodleCreateUser.php
 *
 * PHP version 7.2
 *
 * @category  API
 * @package   MoodleApi
 * @author    Ervin Kwan <ekwan@kordit.com>
 * @copyright 2018 KORDIT PTE LTD
 */
namespace MoodleApi\Controller\Component\MoodleFunction;

class MoodleFunction
{
    protected static $functionParam = "";

    protected static $userAllowedParams 
        = [];

    protected static $userMandatoryParams
        = [];

    public static function getFunctionParam()
    {
        return static::$functionParam;
    }

    public static function checkData($data) 
    {
        $mandatoryParams = static::$userMandatoryParams;
        $allowedParams = static::$userAllowedParams;
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
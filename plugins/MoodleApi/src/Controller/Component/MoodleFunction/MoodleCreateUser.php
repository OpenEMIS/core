<?php
/**
 * MoodleCreateUser - Handles any moodle's core_user_create_users logic
 * To be used with MoodleApiComponent
 *
 * PHP version 7.2
 *
 * @category  API
 * @package   MoodleApi
 * @author    Ervin Kwan <ekwan@kordit.com>
 * @copyright 2018 KORDIT PTE LTD
 */
namespace MoodleApi\Controller\Component\MoodleFunction;

class MoodleCreateUser extends MoodleFunction
{
    protected static $functionParam = "core_user_create_users";

    protected static $userAllowedParams 
        = [
            "username",
            "password",
            "createpassword",
            "firstname",
            "lastname",
            "email",
            "auth",
            "idnumber",
            "lang",
            "calendartype",
            "theme",
            "timezone",
            "mailformat",
            "description",
            "city",
            "country",
            "firstnamephonetic",
            "lastnamephonetic",
            "middlename",
            "alternatename"
        ];

    protected static $userMandatoryParams
        = [
            "username",
            "password",
            "firstname",
            "lastname",
            "email"
        ];

    public static function convertDataToParam($data)
    {
        $data = [0 => $data];
        return ["users" => $data];
    }
}
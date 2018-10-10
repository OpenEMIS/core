<?php
namespace MoodleApi\Controller\Component\MoodleFunction;

class MoodleCreateUser extends MoodleFunction
{
    protected static $_functionParam = "core_user_create_users";

    protected static $_userAllowedParams 
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

    protected static $_userMandatoryParams
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
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
namespace App\MoodleApi\MoodleFunction;

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
            "firstname",
            "lastname",
            "email"
        ];

    /**
     * Converts data array into moodle restful format
     *
     * @return null
     */
    protected function convertDataToParam()
    {
        $this->data = [0 => $this->data];
        $this->data = ["users" => $this->data];
    }

    /**
     * Converts an entity object into array data and stores in $this->data
     *
     * @param object $entity - \User\Model\Entity\User
     *
     * @return null
     */
    protected function convertEntityToData($entity)
    {
        if (!$entity instanceof \User\Model\Entity\User) {
            $this->setError("Entity Datatype is not \User\Model\Entity\User");
        }

        $users = array();
        $users["username"] = $entity->username;
        $users["password"]= $this->generatePassword($entity);
        $users["firstname"] = $entity->first_name;
        $users["lastname"] = $entity->last_name;
        //Hardcode for now first
        $users["email"] = $entity->openemis_no . "@kordit.com";

        $this->data = $users;
    }

    /**
     * Generate password based on a certain hardcoded pattern.
     * To create a system configuration such that user can define the pattern.
     *
     * @param object $entity - \User\Model\Entity\User
     *
     * @return string - password
     */
    private function generatePassword($entity)
    {
        return $entity->openemis_no . "_Moodle";
    }
}
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
namespace App\MoodleApi\MoodleFunction;
use Cake\Log\Log;

abstract class MoodleFunction
{
    protected static $functionParam = "";

    protected static $userAllowedParams
        = [];

    protected static $userMandatoryParams
        = [];

    protected $data = [];

    public function __construct($entity)
    {
        $this->convertEntityToData($entity);
        $this->checkData();
        $this->convertDataToParam();
    }

    /**
     * Converts an entity object into array data and stores in $this->data
     *
     * @param entity $data -
     *
     * @return null
     */
    abstract protected function convertEntityToData($data);

    /**
     * Converts data array into moodle restful format
     *
     * @return null
     */
    abstract protected function convertDataToParam();

    private function checkData()
    {
        $data = $this->data;
        $mandatoryParams = static::$userMandatoryParams;
        $allowedParams = static::$userAllowedParams;
        $mandatoryFieldCount = 0;

        foreach ($data as $param => $value) {
            if (in_array($param, $allowedParams)) {
                if (in_array($param, $mandatoryParams)) {
                    $mandatoryFieldCount++;
                }
            } else {
                $this->setError("This paramter is now allowed. Param: " . $param . ".");
            }
        }

        if ($mandatoryFieldCount < count($mandatoryParams)) {
            $this->setError("Not all mandatory fields are set.");
        }
    }

    public function getData()
    {
        return $this->data;
    }

    public static function getFunctionParam()
    {
        return static::$functionParam;
    }

    protected function setError($msg)
    {
        throw new \Exception($msg);
        Log::write('debug', "MoodleFunction.php - " . $msg);
        Log::write('error', "MoodleFunction.php - " . $msg);
    }
}
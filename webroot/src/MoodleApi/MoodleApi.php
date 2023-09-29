<?php
/**
 * MoodleApiComponent - Uses Cake's HTTP to do webservice call to moodle.
 * Moodle does not complies to restful. Only GET and POST is sufficient.
 * For moodle specifc function logic, please create a class under MoodleFunction.
 * SEE MoodleFunction\MoodleCreateUser for example.
 *
 * Use $reponse->error to check error details.
 *
 * PHP version 7.2
 *
 * @category  API
 * @package   MoodleApi
 * @author    Ervin Kwan <ekwan@kordit.com>
 * @copyright 2018 KORDIT PTE LTD
 */
namespace App\MoodleApi;

use Cake\Http\Client;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use App\MoodleApi\MoodleFunction\MoodleCreateUser;
use MoodleApi\Model\Table\MoodleApiLogTable;

class MoodleApi
{
    private $_token;
    private $_baseURL;
    const WEB_SERVICE_URL = "webservice/rest/server.php";
    const TOKEN_PARAM = "wstoken";
    const FUNCTION_PARAM = "wsfunction";
    const JSON_MODE_PARAM = "moodlewsrestformat=json";

    public function __construct()
    {
        $this->_loadConfig();
    }

    /**
     * To call moodle api GET functions.
     *
     * @param string $function - moodle api function name.
     *                           Example: core_webservice_get_site_info
     *
     * @return object - the response data. Use $response->json to get the json data.
     */
    public function get($function = "core_webservice_get_site_info")
    {
        $url = $this->getUrl($function);
        $http = new Client();

        $response = $http->get($url);

        return $this->_checkError($response);
    }

    /**
     * To call moodle api POST functions.
     *
     * @param string $function - moodle api function name.
     *                           Example: core_webservice_get_site_info
     *
     * @param array $params - look at moodle api for the structure.
     *
     * @return object - the response data. Use $response->json to get the json data.
     */
    public function post($function = null, $params = null)
    {
        if (!$function || !$params) {
            Log::write('debug', "MoodleApiComponent @post Exception - function or params are null");
            $errorObject = $this->_createErrorObject();
            $errorObject->error["param_invalid_exception"] = "Please check your data parameters.";
            return $errorObject;
        }

        $url = $this->getUrl($function);
        $http = new Client();

        $response = $http->post($url, $params);

        return $this->_checkError($response);
    }

    /**
     * Creates user on moodle. Return false if fails.
     *
     * @param string $data - params data for user. Check class MoodleCreateUser
     *                       for available fields.
     *
     * @return object - the response data. Use $response->json to get the json data.
     */
    public function createUser($data)
    {
        if ($this->enableUserCreation()) {
            $moodleUser = new MoodleCreateUser($data);

            $response = $this->post(MoodleCreateUser::getFunctionParam(), $moodleUser->getData());

            $this->_apiLog(MoodleCreateUser::getFunctionParam(), $moodleUser->getData(), $response, __METHOD__, $data);

            if ($response->isOk()) {
                $data = $response->json;
                $data = $data["0"];
                $moodleUser->linkMoodletoOpenEmis($data['id'], $data['username']);
                //POCOR-5677 starts
                //$moodleUser->linkMoodletoOpenEmis($data->id, $data->username);
                //POCOR-5677 ends
            }
            return $response;
        } else {
            return null;
        }
    }

    /**
     * To construct Moodle API URL based on the function name you are calling.
     *
     * @param string $function - moodle api function name.
     *                           Example: core_webservice_get_site_info
     *
     * @return string - the url to do query for API without params
     */
    public function getUrl($function)
    {
        return $this->_baseURL
                . self::WEB_SERVICE_URL
                . "?"
                . $this->_constructBasicParams($function)
                . "&" . self::JSON_MODE_PARAM;
    }

    public function enableUserCreation()
    {
        return isset($this->_enableUserCreation) && $this->_enableUserCreation;
    }

    private function _apiLog($action, $param, $response, $callback, $callbackData)
    {
        $apiLogTable = TableRegistry::get("MoodleApi.MoodleApiLog");
        $apiInstance = $apiLogTable->newEntity();

        if ($response->isOk()) {
            $status = MoodleApiLogTable::STATUS_SUCCESS;
        } else {
            $status = MoodleApiLogTable::STATUS_FAILED;
        }
        $apiInstance->action = $action;
        $apiInstance->params = json_encode($param);
        $apiInstance->response = json_encode($response);
        $apiInstance->status = $status;
        $apiInstance->callback = $callback;
        $apiInstance->callback_param = serialize($callbackData);

        $apiLogTable->save($apiInstance);
    }

    private function _loadConfig()
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $this->_token = $ConfigItems->value("api_token");
        $this->_baseURL = $ConfigItems->value("base_url");
        $this->_enableUserCreation = $ConfigItems->value("core_user_create_users");
    }

    private function _constructBasicParams($function)
    {
        return self::TOKEN_PARAM . "=" . $this->_token
                . "&" .
                self::FUNCTION_PARAM . "=" . $function;
    }

    private function _checkError(&$response)
    {
        $this->_initResponseError($response);
        if ($response->isOk()) {
            $responseBody = $response->json;
            if (isset($responseBody["exception"])) {
                Log::write('debug', "MoodleApi Exception - " . $responseBody["exception"]);
                Log::write('debug', "MoodleApi Exception Message - " . $responseBody["message"]);
                $response->error["api_exception"] = $responseBody;
            }
        } else {
            Log::write('debug', "MoodleApi Exception - response error");
            Log::write('debug', "MoodleApi Exception response - ");
            Log::write('debug', $response);
            $response->error["http_exception"] = $response->code;
        }
        return $response;
    }

    private function _createErrorObject()
    {
        $errorObject = new \stdClass();
        $this->_initResponseError($errorObject);
        return $errorObject;
    }

    private function _initResponseError(&$response)
    {
        if (!isset($response->error)) {
            $response->error = [];
        }

        return $response;
    }

    /**
     * To be deleted. This function is to test that create users works.
     * @return object - the response data. Use $response->json to get the json data.
     */
    public function test_create_user()
    {
        $users = array();
        $users["username"]= "ervinz" . time();
        $users["password"]= "Password12#$";
        // $users["createpassword"]= 0;
        $users["firstname"]= "Ervin";
        $users["lastname"]= "Kwan";
        $users["email"]= "ekwanzs" . time() . "@kordit.com";
        // $users["auth"]= "manual";
        // $users["idnumber"]= "";
        // $users["lang"]= "en";
        // $users["calendartype"]= "gregorian";
        // $users["theme"]= "";
        // $users["timezone"]= "Asia/Singapore";
        // $users["mailformat"]= "";
        // $users["description"]= "";
        // $users["city"]= "";
        // $users["country"]= "";
        // $users["firstnamephonetic"]= "";
        // $users["lastnamephonetic"]= "";
        // $users["middlename"]= "";
        // $users["alternatename"]= "";

        return $this->createUser($users);
    }

}

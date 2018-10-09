<?php
namespace MoodleApi\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Client;
use Cake\Log\Log;
use MoodleApi\Controller\Component\MoodleFunction\MoodleUser;

class MoodleApiComponent extends Component
{
    private $_token;
    private $_baseURL;
    const WEB_SERVICE_URL = "webservice/rest/server.php";
    const TOKEN_PARAM = "wstoken";
    const FUNCTION_PARAM = "wsfunction";
    const JSON_MODE_PARAM = "moodlewsrestformat=json";

    public function initialize(array $config)
    {
        parent::initialize($config);
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

        if ($this->_hasError($response)) {
            return false;
        } else {
            return $response;
        }
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
            return false;
        }

        $url = $this->getUrl($function);
        $http = new Client();

        $response = $http->post($url, $params);

        if ($this->_hasError($response)) {
            return false;
        } else {
            return $response;
        }
    }

    public function createUser($data)
    {
        dd(MoodleUser::getFunctionParam());
        if (!MoodleUser::checkUserData($data)) {
            return false;
        }

        $response = $this->post(MoodleUser::getFunctionParam(), ["users" => $data]);

        return $response;
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

    //TODO - load token from configuration instead of hardcode
    private function _loadConfig()
    {
        $this->_token = "426856ef1e1e4ea867c78d4818915836";
        $this->_baseURL = "https://dmo-tst.openemis.org/learning/";
    }

    private function _constructBasicParams($function)
    {
        return self::TOKEN_PARAM . "=" . $this->_token 
                . "&" . 
                self::FUNCTION_PARAM . "=" . $function;
    }

    private function _hasError($response)
    {
        if ($response->isOk()) {
            $responseBody = $response->json;
            if (isset($responseBody["exception"])) {
                Log::write('debug', "MoodleApiComponent Exception - " . $responseBody["exception"]);
                Log::write('debug', "MoodleApiComponent Exception Message - " . $responseBody["message"]);
                return true;
            } else {
                return false;
            }
        } else {
            Log::write('debug', "MoodleApiComponent Exception - response error");
            Log::write('debug', "MoodleApiComponent Exception response - " . $response);
            return true;
        }
    }

    /**
     * To be deleted. This function is to test that create users works.
     * @return object - the response data. Use $response->json to get the json data.
     */
    public static function test_create_user()
    {
        $users = array();
        $users[0]["username"]= "ervinz" . time();
        $users[0]["password"]= "Password12#$";
        // $users[0]["createpassword"]= 0;
        $users[0]["firstname"]= "Ervin";
        $users[0]["lastname"]= "Kwan";
        $users[0]["email"]= "ekwanzs" . time() . "@kordit.com";
        // $users[0]["auth"]= "manual";
        // $users[0]["idnumber"]= "";
        // $users[0]["lang"]= "en";
        // $users[0]["calendartype"]= "gregorian";
        // $users[0]["theme"]= "";
        // $users[0]["timezone"]= "Asia/Singapore";
        // $users[0]["mailformat"]= "";
        // $users[0]["description"]= "";
        // $users[0]["city"]= "";
        // $users[0]["country"]= "";
        // $users[0]["firstnamephonetic"]= "";
        // $users[0]["lastnamephonetic"]= "";
        // $users[0]["middlename"]= "";
        // $users[0]["alternatename"]= "";

        $response = $this->post("core_user_create_users", ["users" => $users]);

        return $response;
    }

}

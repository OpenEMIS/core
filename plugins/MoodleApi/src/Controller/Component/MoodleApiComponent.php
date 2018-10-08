<?php
namespace MoodleApi\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Client;
use Cake\Log\Log;

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
     * @return string - the response data. Use $response->json to get the json data.
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

}

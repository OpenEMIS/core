<?php
namespace MoodleApi\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Client;

class MoodleApiComponent extends Component
{
    private $_token;
    private $_baseURL;
    const WEB_SERVICE_URL = "webservice/rest/server.php";
    const TOKEN_PARAM = "wstoken";
    const FUNCTION_PARAM = "wsfunction";

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->_loadConfig();
    }

    public function testConnection()
    {
        $url = $this->getUrl($this->constructParams("core_webservice_get_site_info"));
    }

    //TODO - load token from configuration instead of hardcode
    private function _loadConfig()
    {
        $this->_token = "426856ef1e1e4ea867c78d4818915836";
        $this->_baseURL = "https://dmo-tst.openemis.org/learning/";
    }

    private function constructParams($function)
    {
        return self::TOKEN_PARAM . "=" . $this->_token 
                . "&" . 
                self::FUNCTION_PARAM . "=" . $function;
    }

    public function getUrl($params = "")
    {
        return $this->_baseURL . self::WEB_SERVICE_URL . "?" . $append;
    }
}

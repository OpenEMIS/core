<?php
namespace App\Test;
use Cake\TestSuite\IntegrationTestCase;

// attempt to create extending classes and traits fail maybe because of link below
// https://getcomposer.org/doc/04-schema.md#autoload-dev
// use App\tests\TestCase\Controller\CoreTestCases;
// CoreTestCases

// extends IntegrationTestCase: "A test case class intended to make integration tests of your controllers easier... provides a number of helper methods and features that make dispatching requests and checking their responses simpler."
class AppTestCase extends IntegrationTestCase
{
    private $urlPrefix = '';

    public function setup() 
    {
        $this->setAuthSession();
    }

    public function setAuthSession() 
    {
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'admin',
                    'super_admin' => '1'
                ]
            ]
        ]);
    }

    public function urlPrefix($param = null) 
    {
        if (!is_null($param)) {
            $this->urlPrefix = $param;
        }
        return $this->urlPrefix;
    }

    public function url($action, $namedParams = []) 
    {
        $namedParamsString = '';
        if (!empty($namedParams)) {
            $namedParamsString .= '?';
            foreach ($namedParams as $key => $value) {
                $namedParamsString .= $key . '='. urlencode($value);
            }
        }
        
        return $this->urlPrefix . $action . $namedParamsString;
    }

    public function postData($url, $data = [])
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post($url, $data);
    }

    public function putData($url, $data = [])
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->put($url, $data);
    }

    public function patchData($url, $data = [])
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->patch($url, $data);
    }

    public function deleteData($url)
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->delete($url);
    }
}
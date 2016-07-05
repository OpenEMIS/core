<?php
namespace App\Test;
use Cake\TestSuite\IntegrationTestCase;
use Cake\Utility\Hash;

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

    private function generateSecurityToken($url, &$data)
    {
        $newData = $data;
        if (isset($newData['_method'])) {
            unset($newData['_method']);
        }
        $keys = array_map(function ($field) {
            return preg_replace('/(\.\d+)+$/', '', $field);
        }, array_keys(Hash::flatten($newData)));
        $tokenData = $this->_buildFieldToken($url, array_unique($keys));
        $newData['_Token'] = $tokenData;
        $newData['_Token']['debug'] = 'SecurityComponent debug data would be added here';

        $data = array_merge($data, $newData);
    }

    public function postData($url, $data = [])
    {
        $this->enableCsrfToken();
        $this->generateSecurityToken($url, $data);
        $this->post($url, $data);
    }

    public function putData($url, $data = [])
    {
        $this->enableCsrfToken();
        $this->generateSecurityToken($url, $data);
        $this->put($url, $data);
    }

    public function patchData($url, $data = [])
    {
        $this->enableCsrfToken();
        $this->generateSecurityToken($url, $data);
        $this->patch($url, $data);
    }

    public function deleteData($url)
    {
        $this->enableCsrfToken();
        $this->generateSecurityToken($url, $data);
        $this->delete($url);
    }
}
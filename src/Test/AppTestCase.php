<?php
namespace App\Test;

use Cake\TestSuite\IntegrationTestCase;
use Cake\Utility\Hash;
use Cake\Utility\Security;

use ControllerAction\Model\Traits\SecurityTrait;

// attempt to create extending classes and traits fail maybe because of link below
// https://getcomposer.org/doc/04-schema.md#autoload-dev
// use App\tests\TestCase\Controller\CoreTestCases;
// CoreTestCases

// extends IntegrationTestCase: "A test case class intended to make integration tests of your controllers easier... provides a number of helper methods and features that make dispatching requests and checking their responses simpler."

class AppTestCase extends IntegrationTestCase
{
    use SecurityTrait;

    private $urlPrefix = '';
    // public $dropTables = false;

    public function setUp()
    {
        parent::setUp();
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

    public function setInstitutionSession($id)
    {
        $this->session([
            'Institution' => [
                'Institutions' => [
                    'id' => $id
                ]
            ]
        ]);
    }

    public function setStudentSession($id)
    {
        $this->session([
            'Student' => [
                'Students' => [
                    'id' => $id
                ]
            ]
        ]);
    }

    public function setIndexesSession($id)
    {
        $this->session([
            'Indexes' => [
                'Indexes' => [
                    'primaryKey' => [
                        'id' => $id
                    ]
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
            foreach ($namedParams as $key => $value) {
                $namedParamsPrefix = empty($namedParamsString) ? '?' : '&';
                $namedParamsString .= $namedParamsPrefix . $key . '='. urlencode($value);
            }
        }

        return $this->urlPrefix . $action . $namedParamsString;
    }

    /**
     * Might be deprecated as _method issue seems to have been fixed in CakePHP 3.3.
     * Leaving the function here for now, if issue comes back again, we will reuse this function in place of enableSecurityToken().
     */
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

    public function setUrlParams($action, $params = [])
    {
        $hash = sha1(time());
        $sessionKey = 'Url.params.' . implode('.', $action) . '.' . $hash;
        $sessionData = Hash::expand([$sessionKey => $params]);

        $this->session($sessionData);
        return $hash;
    }
}

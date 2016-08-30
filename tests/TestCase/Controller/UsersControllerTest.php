<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class UsersControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testLoginIndex()
    {
        $this->get('/Users/login');
        $this->assertResponseCode(200);
    }

    public function testLogin()
    {
        $data = [
            'username' => 'admin',
            'password' => 'demo',
            'submit' => 'login'
        ];
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/Users/postLogin', $data);
        $this->assertArrayHasKey('Auth', $_SESSION, 'Error logging in!');
    }

    public function testLogout()
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/Users/logout');
        $this->assertArrayNotHasKey('Auth', $_SESSION, 'Error logging out!');
    }
}

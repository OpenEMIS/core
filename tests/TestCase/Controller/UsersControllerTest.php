<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class UsersControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.security_users',
        'app.user_identities',
        'app.identity_types',
        'app.genders',
        'app.area_administratives',
        'app.institution_staff',
        'app.staff_statuses',
        'app.security_group_users',
        'app.system_processes',
    ];

    // public function testLoginIndex()
    // {
    //     $this->get('/');
    //     $this->assertResponseCode(200);
    // }

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

<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class SecuritiesControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testSecurityUserIndex()
    {
        $this->setAuthSession();
        $this->get('/Securities/Users');
        $this->assertResponseCode(200);
    }

    public function testSecurityGroupIndex()
    {
        $this->setAuthSession();
        $this->get('/Securities/UserGroups');
        $this->assertResponseCode(200);
    }

    public function testSecurityRoleIndex()
    {
        $this->setAuthSession();
        $this->get('/Securities/Roles');
        $this->assertResponseCode(200);
    }
}

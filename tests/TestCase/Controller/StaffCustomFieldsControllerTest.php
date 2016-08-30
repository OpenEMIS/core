<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class StaffCustomFIeldsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testStaffCustomFieldIndex()
    {
        $this->setAuthSession();
        $this->get('/StaffCustomFields/Fields');
        $this->assertResponseCode(200);
    }
}

<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class StudentCustomFieldsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testStudentCustomFieldIndex()
    {
        $this->setAuthSession();
        $this->get('/StudentCustomFields/Fields');
        $this->assertResponseCode(200);
    }
}

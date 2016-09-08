<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class StaffCustomFieldsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.custom_modules',
        'app.custom_field_types',
        'app.staff_custom_fields'
    ];

    public function testStaffCustomFieldIndex()
    {
        $this->get('/StaffCustomFields/Fields');
        $this->assertResponseCode(200);
    }
}

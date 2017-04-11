<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class SecuritiesControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.security_roles',
        'app.institutions',
        'app.custom_modules',
        'app.custom_field_types',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.survey_forms',
        'app.survey_rules',
        'app.security_groups',
        'app.security_group_users',
        'app.genders'
    ];

    public function testSecurityUserIndex()
    {
        $this->get('/Securities/Users');
        $this->assertResponseCode(200);
    }

    public function testSecurityGroupIndex()
    {
        $this->get('/Securities/UserGroups');
        $this->assertResponseCode(200);
    }

    public function testSecurityRoleIndex()
    {
        $this->get('/Securities/Roles');
        $this->assertResponseCode(200);
    }
}

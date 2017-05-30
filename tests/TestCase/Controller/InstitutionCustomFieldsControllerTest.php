<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionCustomFieldsControllerTest extends AppTestCase
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
        'app.survey_forms',
        'app.institution_custom_fields'
    ];

    public function testInstitutionCustomFieldIndex()
    {
        $this->get('/InstitutionCustomFields/Fields');
        $this->assertResponseCode(200);
    }
}

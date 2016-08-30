<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class MapControllerTest extends AppTestCase
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
        'app.custom_field_values',
        'app.institutions',
        'app.institution_types',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.survey_forms',
        'app.survey_rules'
    ];

    public function testMap()
    {
        $this->get('/Map');
        $this->assertResponseCode(200);
    }
}

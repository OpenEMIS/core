<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class ExaminationsControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.area_levels',
        'app.areas',
        'app.area_administrative_levels',
        'app.area_administratives',
        'app.institutions',
        'app.institution_grades',
        'app.institution_shifts',
        'app.institution_localities',
        'app.institution_types',
        'app.institution_ownerships',
        'app.institution_statuses',
        'app.institution_sectors',
        'app.institution_providers',
        'app.institution_genders',
        'app.institution_network_connectivities',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.security_groups',
        'app.academic_period_levels',
        'app.academic_periods',
        'app.shift_options',
        'app.custom_modules',
        'app.custom_field_types',
        'app.survey_forms',
        'app.survey_rules'
    ];

	public function testInstitutionIndex()
    {
		$this->get('/Institutions');
		$this->assertResponseCode(200);
	}
}

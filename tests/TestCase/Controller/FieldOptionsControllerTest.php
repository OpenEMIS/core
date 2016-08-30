<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class FieldOptionsControllerTest extends AppTestCase
{
	public $fixtures = [
		'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.institution_genders',
        'app.institution_providers'
    ];

	public function testInstitutionGenderIndex()
    {
		$this->get('/FieldOptions/Genders');
		$this->assertResponseCode(200);
	}

    public function testInstitutionProviderIndex()
    {
        $this->get('/FieldOptions/Providers');
        $this->assertResponseCode(200);
    }
}

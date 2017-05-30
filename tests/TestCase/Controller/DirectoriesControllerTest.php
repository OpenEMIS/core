<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;

class DirectoriesControllerTest extends AppTestCase
{
	public $fixtures = [
		'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.genders',
        'app.identity_types',
        'app.user_identities',
        'app.area_administratives'
    ];

	public function testDirectoryIndex()
    {
		$this->get('/Directories');
		$this->assertResponseCode(200);
	}
}

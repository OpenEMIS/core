<?php

namespace Directory\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class DirectoriesControllerTest extends IntegrationTestCase {

	public $fixtures = [
		'app.config_items',
        'app.workflow_models',
        'app.area_administrative_levels',
        'app.area_administratives'
    ];

	public function setAuthSession() {
		
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 2,
					'username' => 'admin',
					'super_admin' => '1'
				]
			]
		]);
	}

	public function testDirectoryIndex() {

		$this->setAuthSession();
		$this->get('/Directories');
		$this->assertResponseCode(200);
	}
}
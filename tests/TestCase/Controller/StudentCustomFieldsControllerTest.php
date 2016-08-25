<?php

namespace StudentCustomField\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class StudentCustomFieldsControllerTest extends IntegrationTestCase {

	public $fixtures = [
        'app.config_items',
        'app.workflow_models'
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

	public function testStudentCustomFieldIndex() {

		$this->setAuthSession();
		$this->get('/StudentCustomFields/Fields');
		$this->assertResponseCode(200);
	}
}
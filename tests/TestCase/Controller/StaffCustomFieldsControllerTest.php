<?php

namespace StaffCustomField\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class StaffCustomFIeldsControllerTest extends IntegrationTestCase {

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

	public function testStaffCustomFieldIndex() {

		$this->setAuthSession();
		$this->get('/StaffCustomFields/Fields');
		$this->assertResponseCode(200);
	}
}
<?php

namespace Security\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class SecuritiesControllerTests extends IntegrationTestCase {

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

	public function testSecurityUserIndex() {

		$this->setAuthSession();
		$this->get('/Securities/Users');
		$this->assertResponseCode(200);
	}

	public function testSecurityGroupIndex() {

		$this->setAuthSession();
		$this->get('/Securities/UserGroups');
		$this->assertResponseCode(200);
	}

	public function testSecurityRoleIndex() {

		$this->setAuthSession();
		$this->get('/Securities/Roles');
		$this->assertResponseCode(200);
	}
}
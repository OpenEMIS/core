<?php

namespace App\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class ConfigurationsControllerTest extends IntegrationTestCase {

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

	public function testConfigurationIndex() {

		$this->setAuthSession();
		$this->get('/Configurations');
		$this->assertResponseCode(200);
	}
}
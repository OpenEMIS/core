<?php

namespace Infrastructure\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class InfrastructuresControllerTest extends IntegrationTestCase {

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

	public function testInfrastructureIndex() {

		$this->setAuthSession();
		$this->get('/Infrastructures/Fields');
		$this->assertResponseCode(200);
	}
}
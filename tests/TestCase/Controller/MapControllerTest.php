<?php

namespace Map\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class ReportsControllerTest extends IntegrationTestCase {

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

	public function testMap() {

		$this->setAuthSession();
		$this->get('/Map');
		$this->assertResponseCode(200);
	}
}
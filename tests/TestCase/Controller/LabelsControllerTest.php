<?php

namespace App\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class LabelsControllerTest extends IntegrationTestCase {

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

	public function testLabelsIndex() {

		$this->setAuthSession();
		$this->get('/Labels');
		$this->assertResponseCode(200);
	}
}
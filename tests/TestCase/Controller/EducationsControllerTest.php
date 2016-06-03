<?php

namespace Education\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class EducationsControllerTest extends IntegrationTestCase {

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

	public function testEducationIndex() {

		$this->setAuthSession();
		$this->get('/Educations/Systems');
		$this->assertResponseCode(200);
	}
}
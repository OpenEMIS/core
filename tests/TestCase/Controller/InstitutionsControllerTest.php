<?php

namespace Institution\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;
use Cake\ORM\TableRegistry;

class InstitutionsControllerTest extends IntegrationTestCase {

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

	public function testInstitutionIndex() {

		$this->setAuthSession();
		$this->get('/Institutions');
		$this->assertResponseCode(200);
	}
}
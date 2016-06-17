<?php

namespace InstitutionCustomField\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class InstitutionCustomFieldsControllerTest extends IntegrationTestCase {

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

	public function testInstitutionCustomFieldIndex() {

		$this->setAuthSession();
		$this->get('/InstitutionCustomFields/Fields');
		$this->assertResponseCode(200);
	}
}
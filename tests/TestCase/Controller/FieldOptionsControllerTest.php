<?php

namespace FieldOption\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class FieldOptionsControllerTest extends IntegrationTestCase {

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

	public function testFieldOptionIndex() {

		$this->setAuthSession();
		$this->get('/FieldOptions/Genders?field_option_id=1');
		$this->assertResponseCode(200);
	}
}
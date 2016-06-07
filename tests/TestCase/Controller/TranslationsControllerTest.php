<?php

namespace Localization\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class TranslationsControllerTest extends IntegrationTestCase {

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

	public function testTranslationsIndex() {

		$this->setAuthSession();
		$this->get('/Translations');
		$this->assertResponseCode(200);
	}
}
<?php

namespace Directory\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class DirectoriesControllerTest extends IntegrationTestCase {

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

	public function testDirectoryIndex() {

		$this->setAuthSession();
		$this->get('/Directories');
		$this->assertResponseCode(200);
	}
}
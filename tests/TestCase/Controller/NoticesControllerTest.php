<?php

namespace App\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class NoticesControllerTest extends IntegrationTestCase {

	public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

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

	public function testNoticeIndex() {

		$this->setAuthSession();
		$this->get('/Notices');
		$this->assertResponseCode(200);
	}
}
<?php

namespace Alert\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class AlertsControllerTest extends IntegrationTestCase {

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

	public function testAlertQuestionIndex() {

		$this->setAuthSession();
		$this->get('/Alerts/Questions');
		$this->assertResponseCode(200);
	}

	public function testAlertResponseIndex() {

		$this->setAuthSession();
		$this->get('/Alerts/Responses');
		$this->assertResponseCode(200);
	}

	public function testAlertLogIndex() {

		$this->setAuthSession();
		$this->get('/Alerts/Logs');
		$this->assertResponseCode(200);
	}
}
<?php

namespace Survey\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class SurveysControllerTest extends IntegrationTestCase {

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

	public function testSurveyQuestionIndex() {

		$this->setAuthSession();
		$this->get('/Surveys/Questions');
		$this->assertResponseCode(200);
	}
}
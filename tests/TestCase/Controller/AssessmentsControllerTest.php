<?php

namespace Assessment\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class EducationsControllerTest extends IntegrationTestCase {
	public $fixtures = [
        'app.academic_periods',
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

	public function testAssessmentIndex() {

		$this->setAuthSession();
		$this->get('/Assessments/Assessments');
		$this->assertResponseCode(200);
	}
}
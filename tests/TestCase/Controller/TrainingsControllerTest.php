<?php

namespace Training\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class TrainingsControllerTest extends IntegrationTestCase {

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

	public function testTrainingCourseIndex() {

		$this->setAuthSession();
		$this->get('/Trainings/Courses');
		$this->assertResponseCode(200);
	}

	public function testTrainingSessionIndex() {

		$this->setAuthSession();
		$this->get('/Trainings/Sessions');
		$this->assertResponseCode(200);
	}

	public function testTrainingResultIndex() {

		$this->setAuthSession();
		$this->get('/Trainings/Results');
		$this->assertResponseCode(200);
	}
}
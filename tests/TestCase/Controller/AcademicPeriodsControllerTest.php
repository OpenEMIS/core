<?php

namespace AcademicPeriod\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class AcademicPeriodsControllerTest extends IntegrationTestCase {

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

	public function testAcademicPeriodIndex() {

		$this->setAuthSession();
		$this->get('/AcademicPeriods/Periods/index?parent=9');
		$this->assertResponseCode(200);
	}
}
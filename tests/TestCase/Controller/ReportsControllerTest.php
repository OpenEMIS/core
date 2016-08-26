<?php

namespace Report\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class ReportsControllerTest extends IntegrationTestCase {

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

	public function testInstitutionReportIndex() {

		$this->setAuthSession();
		$this->get('/Reports/Institutions');
		$this->assertResponseCode(200);
	}

	public function testStudentReportIndex() {

		$this->setAuthSession();
		$this->get('/Reports/Students');
		$this->assertResponseCode(200);
	}

	public function testStaffReportIndex() {

		$this->setAuthSession();
		$this->get('/Reports/Staff');
		$this->assertResponseCode(200);
	}

	public function testSurveyReportIndex() {

		$this->setAuthSession();
		$this->get('/Reports/Surveys');
		$this->assertResponseCode(200);
	}

	public function testQualityReportIndex() {

		$this->setAuthSession();
		$this->get('/Reports/InstitutionRubrics');
		$this->assertResponseCode(200);
	}

	public function testDataQualityReportIndex() {

		$this->setAuthSession();
		$this->get('/Reports/DataQuality');
		$this->assertResponseCode(200);
	}

	public function testAuditReportIndex() {

		$this->setAuthSession();
		$this->get('/Reports/Audit');
		$this->assertResponseCode(200);
	}
}
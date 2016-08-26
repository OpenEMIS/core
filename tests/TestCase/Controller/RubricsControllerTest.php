<?php

namespace Rubrics\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class RubricsControllerTest extends IntegrationTestCase {

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

	public function testRubricIndex() {

		$this->setAuthSession();
		$this->get('/Rubrics/Templates');
		$this->assertResponseCode(200);
	}
}
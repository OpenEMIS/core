<?php

namespace Workflow\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class WorkflowsControllerTest extends IntegrationTestCase {

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

	public function testWorkflowIndex() {

		$this->setAuthSession();
		$this->get('/Workflows/Workflows');
		$this->assertResponseCode(200);
	}
}
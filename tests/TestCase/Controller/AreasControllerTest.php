<?php

namespace Area\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;
use Cake\ORM\TableRegistry;

class AreasControllerTest extends IntegrationTestCase {

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

	public function testAreaIndex() {

		$this->setAuthSession();
		$this->get('/Areas/Areas/index?parent=1');
		$this->assertResponseCode(200);
	}

	public function testAddArea() {

 		$this->setAuthSession();

		$data = [
			'id' => '2',
			'code' => 'SGP',
			'name' => 'Singapore',
			'parent_id' => 1,
			'area_level_id' => 2,
			'order' => 1,
			'visible' => 1
		];

		$this->post('/Areas/Areas/add?parent=1', $data);

		$table = TableRegistry::get('Area.Areas');
		$this->assertNotEmpty($table->get(2));
	}

	public function testViewArea() {

 		$this->setAuthSession();
		$this->get('/Areas/Areas/index?parent=2');
		$this->assertResponseCode(200);
	}

	public function testEditArea() {

 		$this->setAuthSession();

		$data = [
			'code' => 'JPN',
			'name' => 'Japan',
			'area_level_id' => 2,
		];

		$this->post('/Areas/Areas/edit/2?parent=1', $data);

		$table = TableRegistry::get('Area.Areas');
		$entity = $table->get(2);
		$this->assertEquals($data['code'], $entity->code);
	}

	// public function testDeleteArea() {

	// 	$this->setAuthSession();

	// 	$this->get('Areas/Areas/remove/2?parent=1');
 // 		$this->assertResponseCode(200);

	// 	$data = [
	// 		'id' => 2,
	// 		'transfer_to' => 1,
	// 		'_method' => 'DELETE'
	// 	];
		
	// 	$this->post('/Areas/Areas/remove/2?parent=1', $data);
	// 	$table = TableRegistry::get('Area.Areas');
	// 	$exists = $table->exists([$table->primaryKey() => 2]);
	// 	$this->assertFalse($exists);
	// }
}
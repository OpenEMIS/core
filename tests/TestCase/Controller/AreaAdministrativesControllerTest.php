<?php

namespace Area\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;
use Cake\ORM\TableRegistry;

class AreaAdministrativesControllerTest extends IntegrationTestCase {

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

	public function testAreaAdministrativesIndex() {

		$this->setAuthSession();
		$this->get('/Areas/Administratives/index?parent=8');
		$this->assertResponseCode(200);
	}

	// public function testAddAreaAdministratives() {

 // 		$this->setAuthSession();

	// 	$data = [
	// 		'parent' => NULL,
	// 		'id' => '51',
	// 		'code' => 'SGP',
	// 		'name' => 'Singapore',
	// 		'is_main_country' => 0,
	// 		'parent_id' => 8,
	// 		'area_administrative_level_id' => 1,
	// 		'order' => 1,
	// 		'visible' => 1
	// 	];

	// 	$this->post('/Areas/Administratives/add?parent=8', $data);

	// 	$table = TableRegistry::get('Area.AreaAdministratives');
	// 	$this->assertNotEmpty($table->get(51));
	// }

	// public function testViewAreaAdministratives() {

 // 		$this->setAuthSession();
	// 	$this->get('/Areas/Administratives/index?parent=51');
	// 	$this->assertResponseCode(200);
	// }

	// public function testEditAreaAdministratives() {

 // 		$this->setAuthSession();

	// 	$data = [
	// 		'code' => 'JPN',
	// 		'name' => 'Japan',
	// 		'area_administrative_level_id' => 1,
	// 	];

	// 	$this->post('/Areas/Administratives/edit/51?parent=8', $data);

	// 	$table = TableRegistry::get('Area.AreaAdministratives');
	// 	$entity = $table->get(51);
	// 	$this->assertEquals($data['code'], $entity->code);
	// }

	// public function testDeleteAreaAdministrative() {

	// 	$this->setAuthSession();

	// 	$this->get('Areas/Administratives/remove/2?parent=8');
 // 		$this->assertResponseCode(200);

	// 	$data = [
	// 		'id' => 51,
	// 		'transfer_to' => 8,
	// 		'_method' => 'DELETE'
	// 	];
		
	// 	$this->post('/Areas/Administratives/remove/2?parent=8', $data);
	// 	$table = TableRegistry::get('Area.AreaAdministratives');
	// 	$exists = $table->exists([$table->primaryKey() => 51]);
	// 	$this->assertFalse($exists);
	// }
}
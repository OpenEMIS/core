<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AreasControllerTest extends AppTestCase
{
	public $fixtures = [
		'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.area_levels',
        'app.areas',
    ];

	public function testAreaIndex()
	{
		$this->get('/Areas/Areas/index?parent=1');
		$this->assertResponseCode(200);
	}

	public function testAddArea()
	{
		$data = [
			'id' => '2',
			'code' => 'SGP',
			'name' => 'Singapore',
			'parent_id' => 1,
			'area_level_id' => 2,
			'order' => 1,
			'visible' => 1
		];

		$this->postData('/Areas/Areas/add', $data);

		$table = TableRegistry::get('Area.Areas');
		$this->assertNotEmpty($table->get(2));
	}

	public function testViewArea()
	{
		$this->get('/Areas/Areas/index?parent=2');
		$this->assertResponseCode(200);
	}

	public function testEditArea()
	{
		$data = [
			'code' => 'JPN',
			'name' => 'Japan',
			'area_level_id' => 2,
		];

		$this->postData('/Areas/Areas/edit/2', $data);

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

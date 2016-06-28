<?php

namespace Education\tests\TestCase\Controller;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class EducationsControllerTest extends AppTestCase {

	public function testEducationSystemIndex() {

		$this->setAuthSession();
		$this->get('/Educations/Systems');
		$this->assertResponseCode(200);
	}

	public function testAddEducationSystem() {

 		$this->setAuthSession();

		$data = [
			'id' => 1,
			'name' => 'National Education System'
		];
		$this->postData('/Educations/Systems/add', $data);

		$table = TableRegistry::get('Education.EducationSystems');
		$this->assertNotEmpty($table->get(1));
	}

	public function testViewEducationSystem() {

		$this->setAuthSession();

		$this->setAuthSession();
		$this->get('/Educations/Systems/view/1');
		$this->assertResponseCode(200);
	}

	public function testEditEducationSystem() {

 		$this->setAuthSession();

		$data = [
			'name' => 'PHPUnit Education System'
		];
		$this->postData('/Educations/Systems/edit/1', $data);
		$table = TableRegistry::get('Education.EducationSystems');
		$entity = $table->get(1);
		$this->assertEquals($data['name'], $entity->name);
	}

	// public function testDeleteEducationSystem() {

 // 		$this->setAuthSession();
 // 		$this->get('Educations/Systems/remove/1');
 // 		$this->assertResponseCode(200);

	// 	$data = [
	// 		'id' => 1,
	// 		'_method' => 'DELETE',
	// 	];
	// 	$this->postData('/Educations/Systems/remove/1', $data);
	// 	$table = TableRegistry::get('Education.EducationSystems');
	// 	$exists = $table->exists([$table->primaryKey() => 1]);
	// 	$this->assertFalse($exists);
	// }

}
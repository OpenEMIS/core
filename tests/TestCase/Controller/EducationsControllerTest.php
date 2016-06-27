<?php

namespace Education\tests\TestCase\Controller;

<<<<<<< HEAD
use Cake\TestSuite\IntegrationTestCase;
use Cake\ORM\TableRegistry;

class EducationsControllerTest extends IntegrationTestCase {

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
=======
use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class EducationsControllerTest extends AppTestCase {
>>>>>>> origin_ssh/POCOR-2978-dev

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
<<<<<<< HEAD
		$this->enableSecurityToken();
		$this->post('/Educations/Systems/add', $data);
=======
		$this->postData('/Educations/Systems/add', $data);
>>>>>>> origin_ssh/POCOR-2978-dev

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
<<<<<<< HEAD
		$this->enableSecurityToken();
		$this->post('/Educations/Systems/edit/1', $data);
=======
		$this->postData('/Educations/Systems/edit/1', $data);

>>>>>>> origin_ssh/POCOR-2978-dev
		$table = TableRegistry::get('Education.EducationSystems');
		$entity = $table->get(1);
		$this->assertEquals($data['name'], $entity->name);
	}

	public function testDeleteEducationSystem() {

 		$this->setAuthSession();
<<<<<<< HEAD
 		$this->enableSecurityToken();
=======

>>>>>>> origin_ssh/POCOR-2978-dev
 		$this->get('Educations/Systems/remove/1');
 		$this->assertResponseCode(200);

		$data = [
			'id' => 1,
			'_method' => 'DELETE',
		];
<<<<<<< HEAD
		$this->enableSecurityToken();
		$this->post('/Educations/Systems/remove/1', $data);
=======
		$this->postData('/Educations/Systems/remove/1', $data);
>>>>>>> origin_ssh/POCOR-2978-dev
		$table = TableRegistry::get('Education.EducationSystems');
		$exists = $table->exists([$table->primaryKey() => 1]);
		$this->assertFalse($exists);
	}

}
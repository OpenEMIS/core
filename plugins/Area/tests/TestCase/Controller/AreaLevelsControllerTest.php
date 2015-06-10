<?php

namespace Area\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

class AreaLevelsControllerTest extends IntegrationTestCase {
	public $fixtures = ['pligin.area.area_levels'];

	public function testIndex() {
        	$this->get('/articles?page=1');
        	$this->assertResponseOk();
        	// More asserts.
	}

	
}

<?php
namespace Area\Test\TestCase\Model;

use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use Area\Model\Table\AreaLevelsTable;

class AreaLevelsTest extends TestCase {
	public $fixtures = ['plugin.area.area_levels'];

	public function setUp() {
		parent::setUp();
		$this->AreaLevels = TableRegistry::get('AreaLevels');
	}

	public function testFirstRecord() {
		$query = $this->AreaLevels->find('all');
		$result = $query->hydrate(false)->toArray();

		$this->assertEquals('Country', $result[0]['name']);
	}
}

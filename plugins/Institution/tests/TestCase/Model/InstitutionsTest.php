<?php
namespace Institution\Test\TestCase\Model;

use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use Institution\Model\Table\InstitutionsTable;

class InstitutionsTest extends TestCase {
	public $fixtures = ['plugin.institution.institutions'];

	public function setUp() {
		parent::setUp();
		$this->Institutions = TableRegistry::get('institution_sites');
	}

	public function testRecords() {
		$query = $this->Institutions->find('all');
		$result = $query->hydrate(false)->toArray();

		$this->assertEquals('NTU', $result[0]['alternative_name']);
		$this->assertEquals('NUS', $result[1]['alternative_name']);
	}
}

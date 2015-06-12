<?php

namespace Institution\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

class InstitutionsControllerTest extends IntegrationTestCase {
	public $fixtures = ['plugin.institution.institutions'];
	private $prefix = '/Institutions';

	public function setUp() {
		parent::setUp();
		$this->Institutions = TableRegistry::get('institution_sites');
	}

	public function testIndex() {
        $this->get($this->prefix);
        $this->assertResponseOk();
        
        // Example: Check if we have at least 10 records out from the pagination
        $this->assertEquals(true, (count($this->viewVariable('data')) <= 10));
	}

	public function testSearch() {
		$this->post($this->prefix . '/index', ['Search' => ['searchField' => 'NUS']]);
		$this->assertEquals(1, count($this->viewVariable('data')));

		$data = $this->viewVariable('data');
		
		foreach ($data as $obj) {
			$recordData = $obj->code . '|' . $obj->name;
			$this->assertTextNotContains('-1', strpos($recordData, 'NUS'));
		}
	}

	public function testAdd() {
		$data = [
			'name' => 'Singapore Management University',
			'alternative_name' => 'SMU',
			'code' => 'SMU',
			'address' => 'test address 3',
			'postal_code' => '1234560',
			'contact_person' => 'Karl Turnbull',
			'telephone' => '123456781',
			'fax' => '187654321',
			'email' => 'kturnbull@kordit.com',
			'website' => 'http://www.smu.edu.sg',
			'date_opened' => '01-01-1985',
			'year_opened' => 1985,
			'date_closed' => '01-01-1990',
			'year_closed' => 1990,
			'longitude' => '100',
			'latitude' => '1.32',
			'institution_site_area_id' => null,
			'area_id' => 1,
			'area_administrative_id' => 1,
			'institution_site_locality_id' => 83,
			'institution_site_type_id' => 65,
			'institution_site_ownership_id' => 72,
			'institution_site_status_id' => 89,
			'institution_site_sector_id' => 2,
			'institution_site_provider_id' => 1,
			'institution_site_gender_id' => 6,
			'security_group_id' => 0
		];

		$this->post($this->prefix . '/add', $data);
		$this->assertResponseSuccess();

		$institutionsTable = TableRegistry::get('institution_sites');

		$query = $institutionsTable->find()->where(['code' => 'SMU']);
		$this->assertEquals(1, $query->count());
	}

	public function testEdit() {
		// Test edit the first row of the Institutions fixture
		$data = [
			'id' => 1,
			'name' => 'Singapore Management University',
			'alternative_name' => 'SMU',
			'code' => 'SMU',
			'address' => 'test address 3',
			'postal_code' => '1234560',
			'contact_person' => 'Karl Turnbull',
			'telephone' => '123456781',
			'fax' => '187654321',
			'email' => 'kturnbull@kordit.com',
			'website' => 'http://www.smu.edu.sg',
			'date_opened' => '01-01-1985',
			'year_opened' => 1985,
			'date_closed' => '01-01-1990',
			'year_closed' => 1990,
			'longitude' => '100',
			'latitude' => '1.32',
			'institution_site_area_id' => null,
			'area_id' => 1,
			'area_administrative_id' => 1,
			'institution_site_locality_id' => 83,
			'institution_site_type_id' => 65,
			'institution_site_ownership_id' => 72,
			'institution_site_status_id' => 89,
			'institution_site_sector_id' => 2,
			'institution_site_provider_id' => 1,
			'institution_site_gender_id' => 6,
			'security_group_id' => 0
		];

		$this->post($this->prefix . '/edit/1', $data);
		$this->assertResponseSuccess();

		$institutionsTable = TableRegistry::get('institution_sites');

        $fData = $institutionsTable->findById(1)->hydrate(false)->toArray();

		$this->assertEquals($data['name'], $fData[0]['name']);
	}

	/*public function testDelete() {
		$this->delete($this->prefix . '/remove?id=1');

		$query = $this->Institutions->find('all');
		$result = $query->hydrate(false)->toArray();

		//$this->assertResponseSuccess();
		$this->assertEquals(1, count($result));
	}*/

	public function testView() {
		// Test view the first row of the Institutions fixture
		$this->get($this->prefix . '/view/1');
		$this->assertResponseSuccess();

		// As an example, check whether the output has the words "Nanyang Technological University" and "NTU"
		$this->assertResponseContains('Nanyang Technological University');
		$this->assertResponseContains('NTU');
	}
}

<?php

namespace Institution\tests\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;
use Cake\ORM\TableRegistry;

class InstitutionsControllerTest extends IntegrationTestCase {
	public $fixtures = [
        'app.area_levels',
        'app.areas',
        'app.area_administrative_levels',
        'app.area_administratives',
        'app.institution_localities',
        'app.institution_types',
        'app.institution_ownerships',
        'app.institution_statuses',
        'app.institution_sectors',
        'app.institution_providers',
        'app.institution_genders',
        'app.institution_network_connectivities',
        'app.security_groups',
        'app.academic_period_levels',
        'app.academic_periods',
        'app.institutions',
        'app.shift_options',
        'app.institution_shifts'
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

	public function testInstitutionIndex() {

		$this->setAuthSession();
		$this->get('/Institutions');
		$this->assertResponseCode(200);
	}
}
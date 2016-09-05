<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AreasControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.config_items',
        'app.config_product_lists',
        'app.security_users',
        'app.labels',
        'app.areas',
        'app.area_levels',
        // needed by testDelete()
        'app.institutions',
        'app.custom_modules',
        'app.custom_field_types',
        'app.institution_custom_field_values',
        'app.institution_custom_fields',
        'app.survey_forms',
        'app.survey_rules',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.security_groups',
        'app.security_group_areas'
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


// // AdminBoundaries unitTest
    private $id = 2;
    private $table;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Areas/Areas/');
        $this->table = TableRegistry::get('Area.Areas');
    }

    public function testIndexAdministrativeBoundaries()
    {
        $testUrl = $this->url('index', ['parent' => 1]);
        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFoundAdministrativeBoundaries()
    {
        $testUrl = $this->url('index', ['parent' => 1]);
        $data = [
            'Search' => [
                'searchField' => 'east'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFoundAdministrativeBoundaries()
    {
        $testUrl = $this->url('index', ['parent' => 1]);
        $data = [
            'Search' => [
                'searchField' => 'eastssss'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testViewAdministrativeBoundaries()
    {
        $testUrl = $this->url('view/' . $this->id, ['parent' => 1]);

        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdateAdministrativeBoundaries()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('edit/' . $this->id, ['parent' => 1]);

        // TODO: DO A GET FIRST
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'id' => '2',
                'code' => 'SG001',
                'name' => 'CentralTestUpdate',
                'parent_id' => '1',
                'lft' => '2',
                'rght' => '173',
                'area_level_id' => '2',
                'order' => '1',
                'visible' => '1'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $entity = $this->table->get($this->id);
        $this->assertEquals($data[$alias]['name'], $entity->name);
    }

    public function testCreateAdministrativeBoundaries()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('add', ['parent' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'id' => '222',
                'code' => 'SG00222',
                'name' => 'Central Business District',
                'parent_id' => '1',
                'area_level_id' => '2',
                'order' => '1',
                'visible' => '1',
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2016-01-01 00:00:00'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $this->table->find()
            ->where([$this->table->aliasField('name') => $data[$alias]['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testDeleteAdministrativeBoundaries()
    {
        $testUrl = $this->url('remove/213', ['parent' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'id' => 213,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        $table = TableRegistry::get('Area.Areas');
        $exists = $table->exists([$table->primaryKey() => 213]);
        $this->assertFalse($exists);
    }

    public function testSyncInvalidUrlAdministrativeBoundaries()
    {
        $testUrl = $this->url('synchronize', ['parent' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(302);
    }

    public function testUpdateAssociatedRecordAdministrativeBoundaries()
    {
        $id = 13;

        $requestData = ['Areas' => [
            'data_url' => '',
            'transfer_areas' => [
                13 => [
                    'area_id' => 13,
                    'new_area_id' => 2
                ]
            ]
        ]];

        $expectedSecurityAreaId = 2;
        $expectedInstitutionAreaId = 2;

        $securityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
        $institutions = TableRegistry::get('Instution.Institutions');

        // Calling doUpdateAssociatedRecord method from areasTable.php
        $this->table->doUpdateAssociatedRecord($requestData);

        $resultInstitutionAreaId = $institutions->find()
            ->where(['id' => $id])
            ->first()->area_id;

        $resultSecurityAreaId = $securityGroupAreas->find()
            ->where(['security_group_id' => $id])
            ->first()->area_id;

        $this->assertEquals($expectedSecurityAreaId, $resultSecurityAreaId);
        $this->assertEquals($expectedInstitutionAreaId, $resultInstitutionAreaId);
    }

    public function testDoReplaceAreaTableAdministrativeBoundaries()
    {
        $missingAreaId = 13;
        $jsonAreaId = 1;
        $expectedJsonName = 'Lao PDR';

        $missingAreaArray = [
            13 => [
                'id' => 13,
                'parent_id' => 2,
                'code' => 'SG001007',
                'name' => 'Bishan',
                'area_level_id' => 3,
                'order' => 7
            ]
        ];

        $jsonArray = [
            1 => [
                'id' => 1,
                'parent_id' => '',
                'code' => 'LAO',
                'name' => 'Lao PDR',
                'area_level_id' => 1,
                'order' => 1
            ]
        ];

        $areas = TableRegistry::get('Area.Areas');

        // Calling doReplaceAreaTable method from areasTable.php
        $this->table->doReplaceAreaTable($missingAreaArray, $jsonArray);

        $resultMissingArea = $areas->find()
            ->where(['id' => $missingAreaId])
            ->first();

        $resultJsonName = $areas->find()
            ->where(['id' => $jsonAreaId])
            ->first()->name;

        $this->assertNull($resultMissingArea);
        $this->assertEquals($expectedJsonName, $resultJsonName);
    }
}

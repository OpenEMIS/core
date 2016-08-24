<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionInfrastructuresOccupierControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.academic_periods',
        'app.institutions',
        'app.institution_shifts',
        'app.infrastructure_conditions',
        'app.infrastructure_levels',
        'app.infrastructure_types',
        'app.infrastructure_ownerships',
        'app.institution_infrastructures'
    ];

    private $testingId = 1;
    private $table;

    public function setup()
    {
        $this->setInstitutionSession(2);
        parent::setUp();
        $this->urlPrefix('/Institutions/Infrastructures/');
        $table = TableRegistry::get('Institution.InstitutionInfrastructures');
    }

    public function testIndex()
    {
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);
        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);

        $data = [
            'Search' => [
                'searchField' => 'land'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFound()
    {
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);
        $data = [
            'Search' => [
                'searchField' => '@#!@!cantFindThis!@#!'
            ]
        ];
        $this->postData($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testCreate()
    {
        // occupier cant create infrastructure, it will redirect to index page and show warning message.
        // 302 is redirect response code.
        $testUrl = $this->url('add', ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(302);
    }

    public function testRead()
    {
        $this->setInstitutionSession(2);
        // http://localhost:8888/core/Institutions/Infrastructures/view/1?level=1&type=1
        // $testUrl = $this->url('view/'.$this->testingId, ['level' => 1, 'type' => 1]);
        $testUrl = $this->url('view/1?level=1&type=1');

        $table = TableRegistry::get('Institution.InstitutionInfrastructures');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    }

    public function testUpdate() {
        $testUrl = $this->url('edit/'. $this->testingId, ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(302);

    }

    public function testDelete()
    {
        $testUrl = $this->url('remove/'. $this->testingId, ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(302);
    }
}

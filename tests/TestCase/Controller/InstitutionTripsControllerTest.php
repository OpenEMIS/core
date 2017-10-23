<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class InstitutionTripsControllerTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $primaryKey = ['id' => 1];
    private $modelPlugin = 'Transport';
    private $modelAlias = 'TransportTrips';
    private $institutionId = ['id' => 1];

    public function __construct()
    {
        $this->fixtures[] = 'app.academic_periods';
        $this->fixtures[] = 'app.academic_period_levels';
        $this->fixtures[] = 'app.institutions';
        $this->fixtures[] = 'app.institution_students';
        $this->fixtures[] = 'app.institution_transport_providers';
        $this->fixtures[] = 'app.institution_trips';
        $this->fixtures[] = 'app.institution_trip_days';
        $this->fixtures[] = 'app.institution_buses';
        $this->fixtures[] = 'app.trip_types';
        $this->fixtures[] = 'app.custom_modules';
        $this->fixtures[] = 'app.custom_field_types';
        $this->fixtures[] = 'app.institution_custom_fields';
        $this->fixtures[] = 'app.institution_custom_forms_fields';
        $this->fixtures[] = 'app.institution_custom_forms_filters';
        $this->fixtures[] = 'app.institution_custom_field_values';
        $this->fixtures[] = 'app.survey_forms';
        $this->fixtures[] = 'app.survey_rules';

        parent::__construct();
    }

    public function testIndex()
    {
        $id = $this->paramsEncode($this->institutionId);
        $this->get("/Institution/$id/InstitutionTrips/index");
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 0));
    }

    // public function testRead()
    // {
    //     $this->get('/FieldOptions/TransportFeatures/view/' . $this->paramsEncode($this->primaryKey));
    //     $this->assertResponseOk();
    //     $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    // }

    // public function testUpdate()
    // {
    //     $url = '/FieldOptions/TransportFeatures/edit/' . $this->paramsEncode($this->primaryKey);
    //     $this->get($url);
    //     $this->assertResponseOk();
    //     $this->assertEquals(true, (count($this->viewVariable('data')) == 1));

    //     $data = [
    //         $this->modelAlias => [
    //             'id' => $this->primaryKey['id'],
    //             'name' => 'NEW LABEL',
    //             'visible' => 1,
    //             'default' => 1
    //         ]
    //     ];

    //     $this->postData($url, $data);

    //     $table = TableRegistry::get($this->modelPlugin.'.'.$this->modelAlias);
    //     $entity = $table->get($this->primaryKey);
    //     $this->assertEquals($data[$this->modelAlias]['name'], $entity->name);
    // }

    // public function testCreate()
    // {
    //     $url = '/FieldOptions/TransportFeatures/add';
    //     $this->get($url);
    //     $this->assertResponseOk();

    //     $data = [
    //         $this->modelAlias => [
    //             'id' => '2',
    //             'name' => 'Windows',
    //             'order' => '2',
    //             'visible' => '1',
    //             'editable' => '1',
    //             'default' => '1',
    //             'created_user_id' => '1',
    //             'created' => '2017-10-19 05:29:26'
    //         ]
    //     ];

    //     $this->postData($url, $data);

    //     $table = TableRegistry::get($this->modelPlugin.'.'.$this->modelAlias);
    //     $entity = $table->get($data[$this->modelAlias]['id']);
    //     $this->assertEquals($data[$this->modelAlias]['name'], $entity->name);
    // }

    // public function testDelete()
    // {
    //     $url = '/FieldOptions/TransportFeatures/remove/' . $this->paramsEncode($this->primaryKey);
    //     $this->get($url);
    //     $this->assertResponseOk();

    //     $this->deleteData($url);

    //     $table = TableRegistry::get($this->modelPlugin.'.'.$this->modelAlias);
    //     $entity = $table->find()->where($this->primaryKey)->first();

    //     $this->assertEquals($entity, null);
    // }
}

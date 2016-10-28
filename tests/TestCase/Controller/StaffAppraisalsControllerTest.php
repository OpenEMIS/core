<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class StaffAppraisalsControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.config_items',
        'app.config_item_options',
        'app.config_product_lists',
        'app.labels',
        'app.security_users',
        'app.translations',
        'app.workflows',
        'app.workflows_filters',
        'app.workflow_actions',
        'app.workflow_comments',
        'app.workflow_transitions',
        'app.workflow_steps_roles',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.institutions',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.institution_infrastructures',
        'app.institution_staff',
        'app.staff_statuses',
        'app.staff_custom_fields',
        'app.staff_custom_field_values',
        'app.staff_custom_forms_fields',
        'app.custom_modules',
        'app.custom_field_types',
        'app.survey_forms',
        'app.survey_rules',
        'app.staff_appraisals'
    ];

    // private $nonAcademicInstitutionId = 517;
    // private $academicInstitutionId = 2;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Staff/Appraisals/'); // Staff/Appraisals/index
    }

	public function testCompetenciesIndex()
    {
        $testUrl = $this->url('index');
		$this->get($testUrl);
		$this->assertResponseCode(200);
	}

//     public function testNonAcademicInstitutionCreate()
//     {
//         $testUrl = $this->url('add');
//         $this->get($testUrl);

//         $this->assertResponseCode(200);

//         $table = TableRegistry::get('Institution.Institutions');
//         $data = [
//             'Institutions' => [
//                 'name' => 'Central Region Office',
//                 'code' => 'MOECENTRALREGION',
//                 'is_academic' => 0,
//                 'address' => '2 Infinite Loop',
//                 'date_opened' => '1978-05-07',
//                 'date_closed' => null,
//                 'area_id' => '3',
//                 'area_administrative_id' => '4',
//                 'shift_type' => 0,
//                 'institution_locality_id' => '1',
//                 'institution_type_id' => '2',
//                 'institution_ownership_id' => '4',
//                 'institution_status_id' => '117',
//                 'institution_sector_id' => '1',
//                 'institution_provider_id' => '1',
//                 'institution_gender_id' => '1',
//                 'institution_network_connectivity_id' => '2',
//             ],
//             'submit' => 'save'
//         ];
//         $this->postData($testUrl, $data);

//         $record = $table->find()
//             ->where([$table->aliasField('code') => $data['Institutions']['code']])
//             ->first();

//         // Test institution record inserted
//         $this->assertEquals(true, (!empty($record)));

//         // Test security_group_institutions record inserted
//         $this->assertEquals(true, (!empty($this->getSecurityGroupInstitutionRecord($record->security_group_id, $record->id))));

//         // Test security_group_areas record inserted
//         $this->assertEquals(true, (!empty($this->getSecurityGroupAreaRecord($record->security_group_id, $record->area_id))));

//     }

//     public function testAcademicInstitutionCreate()
//     {
//         $testUrl = $this->url('add');

//         $this->get($testUrl);
//         $this->assertResponseCode(200);

//         $table = TableRegistry::get('Institution.Institutions');
//         $data = [
//             'Institutions' => [
//                 'name' => 'Test College',
//                 'code' => 'ATESTCOLLEGE',
//                 'is_academic' => 1,
//                 'address' => 'Test Address',
//                 'date_opened' => '2015-05-07',
//                 'area_id' => '94',
//                 'area_administrative_id' => '94',
//                 'shift_type' => 0,
//                 'institution_locality_id' => '1',
//                 'institution_type_id' => '2',
//                 'institution_ownership_id' => '4',
//                 'institution_status_id' => '117',
//                 'institution_sector_id' => '1',
//                 'institution_provider_id' => '1',
//                 'institution_gender_id' => '1',
//                 'institution_network_connectivity_id' => '2',
//             ],
//             'submit' => 'save'
//         ];
//         $this->postData($testUrl, $data);

//         $record = $table->find()
//             ->where([$table->aliasField('code') => $data['Institutions']['code']])
//             ->first();

//         // Test institution record inserted
//         $this->assertEquals(true, (!empty($record)));

//         // Test security_group_institutions record inserted
//         $this->assertEquals(true, (!empty($this->getSecurityGroupInstitutionRecord($record->security_group_id, $record->id))));

//         // Test security_group_areas record inserted
//         $this->assertEquals(false, (!empty($this->getSecurityGroupAreaRecord($record->security_group_id, $record->area_id))));

//     }

//     public function testAcademicInstitutionUpdate()
//     {
//         $testUrl = $this->url('edit/'.$this->academicInstitutionId);

//         $this->get($testUrl);
//         $this->assertResponseCode(200);
//         $data = [
//             'Institutions' => [
//                 'id' => $this->academicInstitutionId,
//                 'name' => 'Test Edit College',
//                 'code' => 'TESTEDITCOLLEGE',
//                 'address' => 'Test Change Address',
//                 'area_id' => '94',
//                 'area_administrative_id' => '94',
//                 'institution_locality_id' => '1',
//                 'institution_type_id' => '2',
//                 'institution_ownership_id' => '4',
//                 'institution_status_id' => '117',
//                 'institution_sector_id' => '1',
//                 'institution_provider_id' => '1',
//                 'institution_gender_id' => '1',
//                 'institution_network_connectivity_id' => '2',
//             ],
//             'submit' => 'save'
//         ];
//         $table = TableRegistry::get('Institution.Institutions');
//         $originalRecord = $table->find()
//             ->where([
//                 $table->aliasField('id') => $this->academicInstitutionId
//             ])
//             ->hydrate(false)
//             ->firstOrFail();

//         $patchedRecord = array_merge($originalRecord, $data['Institutions']);

//         unset($patchedRecord['modified']);
//         unset($patchedRecord['modified_user_id']);

//         $this->postData($testUrl, $data);

//         $table = TableRegistry::get('Institution.Institutions');
//         $record = $table->find()
//             ->where([
//                 $table->aliasField('id') => $this->academicInstitutionId
//             ])
//             ->hydrate(false)
//             ->firstOrFail();

//         unset($record['modified']);
//         unset($record['modified_user_id']);

//         // Test institution record inserted
//         $this->assertEquals($patchedRecord, $record);

//         // Test security_group_institutions record inserted
//         $this->assertTrue(!empty($this->getSecurityGroupInstitutionRecord($record['security_group_id'], $record['id'])));

//         // Test security_group_areas record inserted
//         $this->assertFalse(!empty($this->getSecurityGroupAreaRecord($record['security_group_id'], $record['area_id'])));

//     }

//     public function testNonAcademicInstitutionUpdate()
//     {
//         $testUrl = $this->url('edit/'.$this->nonAcademicInstitutionId);

//         $this->get($testUrl);
//         $this->assertResponseCode(200);
//         $data = [
//             'Institutions' => [
//                 'id' => $this->nonAcademicInstitutionId,
//                 'name' => 'Test Edit College',
//                 'code' => 'TESTEDITCOLLEGE',
//                 'address' => 'Test Change Address',
//                 'area_id' => '94',
//                 'area_administrative_id' => '94',
//                 'institution_locality_id' => '1',
//                 'institution_type_id' => '2',
//                 'institution_ownership_id' => '4',
//                 'institution_status_id' => '117',
//                 'institution_sector_id' => '1',
//                 'institution_provider_id' => '1',
//                 'institution_gender_id' => '1',
//                 'institution_network_connectivity_id' => '2',
//             ],
//             'submit' => 'save'
//         ];
//         $table = TableRegistry::get('Institution.Institutions');
//         $originalRecord = $table->find()
//             ->where([
//                 $table->aliasField('id') => $this->nonAcademicInstitutionId
//             ])
//             ->hydrate(false)
//             ->firstOrFail();

//         $patchedRecord = array_merge($originalRecord, $data['Institutions']);

//         unset($patchedRecord['modified']);
//         unset($patchedRecord['modified_user_id']);

//         $this->postData($testUrl, $data);

//         $table = TableRegistry::get('Institution.Institutions');
//         $record = $table->find()
//             ->where([
//                 $table->aliasField('id') => $this->nonAcademicInstitutionId
//             ])
//             ->hydrate(false)
//             ->firstOrFail();

//         unset($record['modified']);
//         unset($record['modified_user_id']);

//         // Test institution record inserted
//         $this->assertEquals($patchedRecord, $record);

//         // Test security_group_institutions record inserted
//         $this->assertTrue(!empty($this->getSecurityGroupInstitutionRecord($record['security_group_id'], $record['id'])));

//         // Test security_group_areas record inserted
//         $this->assertTrue(!empty($this->getSecurityGroupAreaRecord($record['security_group_id'], $record['area_id'])));

//     }

//     public function testNonAcademicInstitutionDelete()
//     {
//         $testUrl = $this->url('remove/'.$this->nonAcademicInstitutionId);

//         $table = TableRegistry::get('Institution.Institutions');

//         $record = $table->find()
//             ->where([
//                 $table->aliasField('id') => $this->nonAcademicInstitutionId
//             ])
//             ->first();

//         $this->assertTrue(!empty($record));

//         $data = [
//             'id' => $this->nonAcademicInstitutionId,
//             '_method' => 'DELETE'
//         ];
//         $this->postData($testUrl, $data);

//         $exists = $table->exists([$table->primaryKey() => $this->nonAcademicInstitutionId]);
//         $this->assertFalse($exists);

//         // Test security_group_institutions record inserted
//         $this->assertFalse(!empty($this->getSecurityGroupInstitutionRecord($record['security_group_id'], $record['id'])));

//         // Test security_group_areas record inserted
//         $this->assertFalse(!empty($this->getSecurityGroupAreaRecord($record['security_group_id'], $record['area_id'])));
//     }

//     public function testAcademicInstitutionDelete()
//     {
//         $testUrl = $this->url('remove/'.$this->academicInstitutionId);

//         $table = TableRegistry::get('Institution.Institutions');

//         $record = $table->find()
//             ->where([
//                 $table->aliasField('id') => $this->academicInstitutionId
//             ])
//             ->first();

//         $this->assertTrue(!empty($record));

//         $data = [
//             'id' => $this->academicInstitutionId,
//             '_method' => 'DELETE'
//         ];
//         $this->postData($testUrl, $data);

//         $exists = $table->exists([$table->primaryKey() => $this->academicInstitutionId]);
//         $this->assertFalse($exists);

//         // Test security_group_institutions record inserted
//         $this->assertFalse(!empty($this->getSecurityGroupInstitutionRecord($record['security_group_id'], $record['id'])));

//         // Test security_group_areas record inserted
//         $this->assertFalse(!empty($this->getSecurityGroupAreaRecord($record['security_group_id'], $record['area_id'])));
//     }

//     private function getSecurityGroupAreaRecord($securityGroupId, $areaId) {
//         $SecurityGroupAreasTable = TableRegistry::get('Security.SecurityGroupAreas');
//         return $SecurityGroupAreasTable->find()
//             ->where([
//                 $SecurityGroupAreasTable->aliasField('security_group_id') => $securityGroupId,
//                 $SecurityGroupAreasTable->aliasField('area_id') => $areaId,
//             ])
//             ->first();
//     }

//     private function getSecurityGroupInstitutionRecord($securityGroupId, $institutionId) {
//         $SecurityGroupInstitutionsTable = TableRegistry::get('Security.SecurityGroupInstitutions');
//         return $SecurityGroupInstitutionsTable->find()
//             ->where([
//                 $SecurityGroupInstitutionsTable->aliasField('security_group_id') => $securityGroupId,
//                 $SecurityGroupInstitutionsTable->aliasField('institution_id') => $institutionId,
//             ])
//             ->first();
//     }
}

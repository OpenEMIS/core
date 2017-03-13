<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionsControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.assessment_item_results',
        'app.examination_centres',
        'app.education_grades',
        'app.education_programmes',
        'app.institution_quality_rubrics',
        'app.institution_quality_visits',
        'app.institution_surveys',
        'app.institution_survey_answers',
        'app.institution_student_surveys',
        'app.institution_student_survey_answers',
        'app.survey_questions',
        'app.survey_forms_questions',
        'app.config_items',
        'app.config_product_lists',
        'app.labels',
        'app.security_users',
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
        'app.area_levels',
        'app.areas',
        'app.area_administrative_levels',
        'app.area_administratives',
        'app.institutions',
        'app.institution_shifts',
        'app.institution_classes',
        'app.institution_class_grades',
        'app.institution_class_students',
        'app.institution_grades',
        'app.institution_subjects',
        'app.institution_subject_students',
        'app.institution_subject_staff',
        'app.institution_localities',
        'app.institution_types',
        'app.institution_ownerships',
        'app.institution_statuses',
        'app.institution_sectors',
        'app.institution_providers',
        'app.institution_genders',
        'app.institution_network_connectivities',
        'app.institution_custom_forms',
        'app.institution_custom_table_cells',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.institution_infrastructures',
        'app.infrastructure_custom_field_values',
        'app.infrastructure_custom_fields',
        'app.infrastructure_custom_forms_fields',
        'app.infrastructure_custom_forms_filters',
        'app.institution_staff',
        'app.staff_statuses',
        'app.institution_staff_position_profiles',
        'app.staff_change_types',
        'app.staff_behaviours',
        'app.institution_staff_absences',
        'app.institution_student_absences',
        'app.institution_bank_accounts',
        'app.institution_student_admission',
        'app.institution_student_withdraw',
        'app.institution_fees',
        'app.absence_types',
        'app.student_behaviours',
        'app.institution_students',
        'app.institution_activities',
        'app.institution_attachments',
        'app.institution_positions',
        'app.security_groups',
        'app.security_group_users',
        'app.security_group_institutions',
        'app.security_group_areas',
        'app.academic_period_levels',
        'app.academic_periods',
        'app.shift_options',
        'app.custom_modules',
        'app.custom_field_types',
        'app.survey_forms',
        'app.survey_rules',
        'app.security_roles'
    ];

    private $nonAcademicInstitutionId = 13;
    private $academicInstitutionId = 2;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Institutions/');
    }

	public function testInstitutionIndex()
    {
		$this->get('/Institutions');
		$this->assertResponseCode(200);
	}

    public function testNonAcademicInstitutionCreate()
    {
        $testUrl = $this->url('add');
        $this->get($testUrl);

        $this->assertResponseCode(200);

        $table = TableRegistry::get('Institution.Institutions');
        $data = [
            'Institutions' => [
                'id' => '517',
                'name' => 'Amaris',
                'alternative_name' => '',
                'classification' => 0,
                'code' => 'AMR',
                'address' => 'qwe',
                'postal_code' => '',
                'contact_person' => '',
                'telephone' => '',
                'fax' => '',
                'email' => '',
                'website' => '',
                'date_opened' => '2016-11-03',
                'year_opened' => '2016',
                'date_closed' => null,
                'year_closed' => null,
                'longitude' => '',
                'latitude' => '',
                'shift_type' => '0',
                'area_id' => '3',
                'area_administrative_id' => '1',
                'institution_locality_id' => '1',
                'institution_type_id' => '1',
                'institution_ownership_id' => '4',
                'institution_status_id' => '117',
                'institution_sector_id' => '1',
                'institution_provider_id' => '1',
                'institution_gender_id' => '1',
                'institution_network_connectivity_id' => '5'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $record = $table->find()
            ->where([$table->aliasField('code') => $data['Institutions']['code']])
            ->first();

        // Test institution record inserted
        $this->assertEquals(true, (!empty($record)));

        // Test security_group_institutions record inserted
        $this->assertEquals(true, (!empty($this->getSecurityGroupInstitutionRecord($record->security_group_id, $record->id))));

        // Test security_group_areas record inserted
        $this->assertEquals(true, (!empty($this->getSecurityGroupAreaRecord($record->security_group_id, $record->area_id))));
    }

    public function testAcademicInstitutionCreate()
    {
        $testUrl = $this->url('add');

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $table = TableRegistry::get('Institution.Institutions');
        $data = [
            'Institutions' => [
                'name' => 'Test College',
                'code' => 'ATESTCOLLEGE',
                'classification' => 1,
                'address' => 'Test Address',
                'date_opened' => '2015-05-07',
                'area_id' => '94',
                'area_administrative_id' => '94',
                'shift_type' => 0,
                'institution_locality_id' => '1',
                'institution_type_id' => '2',
                'institution_ownership_id' => '4',
                'institution_status_id' => '117',
                'institution_sector_id' => '1',
                'institution_provider_id' => '1',
                'institution_gender_id' => '1',
                'institution_network_connectivity_id' => '2',
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $record = $table->find()
            ->where([$table->aliasField('code') => $data['Institutions']['code']])
            ->first();

        // Test institution record inserted
        $this->assertEquals(true, (!empty($record)));

        // Test security_group_institutions record inserted
        $this->assertEquals(true, (!empty($this->getSecurityGroupInstitutionRecord($record->security_group_id, $record->id))));

        // Test security_group_areas record inserted
        $this->assertEquals(false, (!empty($this->getSecurityGroupAreaRecord($record->security_group_id, $record->area_id))));

    }

    public function testAcademicInstitutionUpdate()
    {
        $testUrl = $this->url('edit/'.$this->academicInstitutionId);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $data = [
            'Institutions' => [
                'id' => $this->academicInstitutionId,
                'name' => 'Test Edit College',
                'code' => 'TESTEDITCOLLEGE',
                'address' => 'Test Change Address',
                'area_id' => '94',
                'area_administrative_id' => '94',
                'institution_locality_id' => '1',
                'institution_type_id' => '2',
                'institution_ownership_id' => '4',
                'institution_status_id' => '117',
                'institution_sector_id' => '1',
                'institution_provider_id' => '1',
                'institution_gender_id' => '1',
                'institution_network_connectivity_id' => '2',
            ],
            'submit' => 'save'
        ];
        $table = TableRegistry::get('Institution.Institutions');
        $originalRecord = $table->find()
            ->where([
                $table->aliasField('id') => $this->academicInstitutionId
            ])
            ->hydrate(false)
            ->firstOrFail();

        $patchedRecord = array_merge($originalRecord, $data['Institutions']);

        unset($patchedRecord['modified']);
        unset($patchedRecord['modified_user_id']);

        $this->postData($testUrl, $data);

        $table = TableRegistry::get('Institution.Institutions');
        $record = $table->find()
            ->where([
                $table->aliasField('id') => $this->academicInstitutionId
            ])
            ->hydrate(false)
            ->firstOrFail();

        unset($record['modified']);
        unset($record['modified_user_id']);

        // Test institution record inserted
        $this->assertEquals($patchedRecord, $record);

        // Test security_group_institutions record inserted
        $this->assertTrue(!empty($this->getSecurityGroupInstitutionRecord($record['security_group_id'], $record['id'])));

        // Test security_group_areas record inserted
        $this->assertFalse(!empty($this->getSecurityGroupAreaRecord($record['security_group_id'], $record['area_id'])));

    }

    public function testNonAcademicInstitutionUpdate()
    {
        $testUrl = $this->url('edit/'.$this->nonAcademicInstitutionId);
        $table = TableRegistry::get('Institution.Institutions');

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $data = [
            'Institutions' => [
                'id' => $this->nonAcademicInstitutionId,
                'name' => 'Test Edit College',
                'code' => 'TESTEDITCOLLEGE',
                'address' => 'Test Change Address',
                'area_id' => '94',
                'area_administrative_id' => '94',
                'institution_locality_id' => '1',
                'institution_type_id' => '2',
                'institution_ownership_id' => '4',
                'institution_status_id' => '117',
                'institution_sector_id' => '1',
                'institution_provider_id' => '1',
                'institution_gender_id' => '1',
                'institution_network_connectivity_id' => '2',
            ],
            'submit' => 'save'
        ];

        $originalRecord = $table->find()
            ->where([
                $table->aliasField('id') => $this->nonAcademicInstitutionId
            ])
            ->hydrate(false)
            ->firstOrFail();

        $patchedRecord = array_merge($originalRecord, $data['Institutions']);

        unset($patchedRecord['modified']);
        unset($patchedRecord['modified_user_id']);

        $this->postData($testUrl, $data);

        $record = $table->find()
            ->where([
                $table->aliasField('id') => $this->nonAcademicInstitutionId
            ])
            ->hydrate(false)
            ->firstOrFail();

        unset($record['modified']);
        unset($record['modified_user_id']);

        // Test institution record inserted
        $this->assertEquals($patchedRecord, $record);

        // Test security_group_institutions record inserted
        $this->assertTrue(!empty($this->getSecurityGroupInstitutionRecord($record['security_group_id'], $record['id'])));

        // Test security_group_areas record inserted
        $this->assertTrue(!empty($this->getSecurityGroupAreaRecord($record['security_group_id'], $record['area_id'])));

    }

    public function testNonAcademicInstitutionDelete()
    {
        $testUrl = $this->url('remove/'.$this->nonAcademicInstitutionId);

        $table = TableRegistry::get('Institution.Institutions');

        $record = $table->find()
            ->where([
                $table->aliasField('id') => $this->nonAcademicInstitutionId
            ])
            ->first();

        $this->assertTrue(!empty($record));

        $data = [
            'id' => $this->nonAcademicInstitutionId,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->nonAcademicInstitutionId]);
        $this->assertFalse($exists);

        // Test security_group_institutions record inserted
        $this->assertFalse(!empty($this->getSecurityGroupInstitutionRecord($record['security_group_id'], $record['id'])));

        // Test security_group_areas record inserted
        $this->assertFalse(!empty($this->getSecurityGroupAreaRecord($record['security_group_id'], $record['area_id'])));
    }

    public function testAcademicInstitutionDelete()
    {
        $testUrl = $this->url('remove/'.$this->academicInstitutionId);

        $table = TableRegistry::get('Institution.Institutions');

        $record = $table->find()
            ->where([
                $table->aliasField('id') => $this->academicInstitutionId
            ])
            ->first();

        $this->assertTrue(!empty($record));

        $data = [
            'id' => $this->academicInstitutionId,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->academicInstitutionId]);
        $this->assertFalse($exists);

        // Test security_group_institutions record inserted
        $this->assertFalse(!empty($this->getSecurityGroupInstitutionRecord($record['security_group_id'], $record['id'])));

        // Test security_group_areas record inserted
        $this->assertFalse(!empty($this->getSecurityGroupAreaRecord($record['security_group_id'], $record['area_id'])));
    }

    private function getSecurityGroupAreaRecord($securityGroupId, $areaId)
    {
        $SecurityGroupAreasTable = TableRegistry::get('Security.SecurityGroupAreas');
        return $SecurityGroupAreasTable->find()
            ->where([
                $SecurityGroupAreasTable->aliasField('security_group_id') => $securityGroupId,
                $SecurityGroupAreasTable->aliasField('area_id') => $areaId,
            ])
            ->first();
    }

    private function getSecurityGroupInstitutionRecord($securityGroupId, $institutionId)
    {
        $SecurityGroupInstitutionsTable = TableRegistry::get('Security.SecurityGroupInstitutions');
        return $SecurityGroupInstitutionsTable->find()
            ->where([
                $SecurityGroupInstitutionsTable->aliasField('security_group_id') => $securityGroupId,
                $SecurityGroupInstitutionsTable->aliasField('institution_id') => $institutionId,
            ])
            ->first();
    }
}

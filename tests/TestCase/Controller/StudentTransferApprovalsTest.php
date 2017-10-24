<?php
namespace App\Test\TestCases;

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class StudentTransferApprovalsTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $primaryKey = ['id' => 107];
    private $modelAlias = 'TransferApprovals';

    public function __construct()
    {
        $fixtures = [
            'app.institutions',
            'app.academic_periods',
            'app.institution_classes',
            'app.custom_modules',
            'app.education_grades',
            'app.student_transfer_reasons',
            'app.education_programmes',
            'app.education_cycles',
            'app.education_levels',
            'app.education_level_isced',
            'app.education_systems',
            'app.institution_student_admission',
            'app.institution_students',
            'app.student_statuses',
            'app.institution_statuses',
            'app.areas',
            'app.institution_student_absences',
            'app.institution_genders',
            'app.student_behaviours',
            'app.custom_field_types',
            'app.institution_custom_field_values',
            'app.institution_custom_fields',
            'app.survey_forms',
            'app.survey_rules',
            'app.institution_custom_forms_fields',
            'app.institution_custom_forms_filters'
        ];

        $this->fixtures = array_merge($this->fixtures, $fixtures);

        parent::__construct();
    }

    public function testApprovalPage()
    {
        $url = '/Dashboard/TransferApprovals/edit/'.$this->paramsEncode($this->primaryKey);

        $this->get($url);
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    }

    public function testApproved()
    {
        $url = '/Dashboard/TransferApprovals/edit/'.$this->paramsEncode($this->primaryKey);

        $this->get($url);
        $this->assertResponseOk();

        $table = TableRegistry::get('Institution.TransferApprovals');
        $entity = $table->get($this->primaryKey);

        $data = [
            $this->modelAlias => [
                'id' => $this->primaryKey['id'],
                'status' => $entity->status,
                'transfer_status' => $entity->status,
                'start_date' => new Date('24-10-2017'),
                'end_date' => $entity->end_date,
                'requested_date' => $entity->requested_date,
                'student_id' => $entity->student_id,
                'institution_id' => $entity->institution_id,
                'previous_institution_id' => $entity->previous_institution_id,
                'education_grade_id' => $entity->education_grade_id,
                'academic_period_id' => $entity->academic_period_id,
                'student_transfer_reason_id' => $entity->student_transfer_reason_id,
                'new_education_grade_id' => $entity->new_education_grade_id,
            ]
        ];

        $this->postData($url, $data);
        $entity = $table->get($this->primaryKey);

        $TransferApprovals = TableRegistry::get("Institution.TransferApprovals");
        $this->assertEquals($TransferApprovals::APPROVED, $entity->status);
    }

    public function testNotApprovedRedirectToAssociatedPage()
    {
        $url = '/Dashboard/TransferApprovals/edit/'.$this->paramsEncode($this->primaryKey);

        $this->get($url);
        $this->assertResponseOk();

        $table = TableRegistry::get('Institution.TransferApprovals');
        $entity = $table->get($this->primaryKey);

        $data = [
            $this->modelAlias => [
                'id' => $this->primaryKey['id'],
                'status' => $entity->status,
                'transfer_status' => $entity->status,
                'start_date' => $entity->start_date,
                // 'start_date' => new Date('24-10-2017'),
                'end_date' => $entity->end_date,
                'requested_date' => $entity->requested_date,
                'student_id' => $entity->student_id,
                'institution_id' => $entity->institution_id,
                'previous_institution_id' => $entity->previous_institution_id,
                'education_grade_id' => $entity->education_grade_id,
                'academic_period_id' => $entity->academic_period_id,
                'student_transfer_reason_id' => $entity->student_transfer_reason_id,
                'new_education_grade_id' => $entity->new_education_grade_id,
            ]
        ];

        $this->postData($url, $data);

        $this->assertResponseCode(302); // redirected to associated page
        $this->assertRedirect('Dashboard/TransferApprovals/associated/'.$this->paramsEncode($this->primaryKey)); // compare the redirected url
    }
}

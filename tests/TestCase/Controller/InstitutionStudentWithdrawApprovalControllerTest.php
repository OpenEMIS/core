<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionStudentWithdrawApprovalControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.custom_modules',
        'app.custom_field_types',
        'app.config_product_lists',
        'app.institution_custom_field_values',
        'app.institution_custom_fields',
        'app.survey_forms',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.institution_student_admission',
        'app.survey_rules',
        'app.education_programmes',
        'app.student_statuses',
        'app.institution_class_students',
        'app.institution_grades',
        'app.institution_classes',
        'app.institution_student_withdraw',
        'app.institution_students',
        'app.institution_subject_students',
        'app.institutions',
        'app.academic_periods',
        'app.education_grades',
        'app.student_withdraw_reasons'
    ];

    private $editId = 1;
    private $approvedStatus = 1;
    private $rejectedStatus = 2;

    public function setup()
    {
        parent::setUp();

        $this->setAuthSession();
        $this->urlPrefix('/Dashboard/StudentWithdraw/');
    }

    public function testApprove()
    {
        $testUrl = $this->url('edit/'.$this->paramsEncode(['id' => $this->editId]));
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'StudentWithdraw' => [
                'id' => $this->editId,
                'effective_date' => '2016-08-01', // correct date (after enrollment date '2016-06-01')
                'student_id' => 7,
                'status' => 0,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'student_withdraw_reason_id' => 661,
                'comment' => 'Approved'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $WithdrawTable = TableRegistry::get('Institutions.institution_student_withdraw');
        $approvedRecord = $WithdrawTable->find()
            ->where([$WithdrawTable->aliasField('id') => $data['StudentWithdraw']['id'],
                $WithdrawTable->aliasField('effective_date') => $data['StudentWithdraw']['effective_date'],
                $WithdrawTable->aliasField('comment') => $data['StudentWithdraw']['comment'],
                $WithdrawTable->aliasField('status') => $this->approvedStatus])
            ->first();
        $this->assertEquals(true, (!empty($approvedRecord)));

        $StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
        $withdrawStatus = $StudentStatusesTable->getIdByCode('WITHDRAWN');

        // check that student status is changed to withdraw
        $StudentsTable = TableRegistry::get('Institutions.institution_students');
        $withdrawStudentRecord = $StudentsTable->find()
            ->where([$StudentsTable->aliasField('student_id') => $data['StudentWithdraw']['student_id'],
                $StudentsTable->aliasField('institution_id') => $data['StudentWithdraw']['institution_id'],
                $StudentsTable->aliasField('academic_period_id') => $data['StudentWithdraw']['academic_period_id'],
                $StudentsTable->aliasField('education_grade_id') => $data['StudentWithdraw']['education_grade_id'],
                $StudentsTable->aliasField('student_status_id') => $withdrawStatus])
            ->first();
        $this->assertEquals(true, (!empty($withdrawStudentRecord)));
    }

    public function testApproveWrongDate()
    {
        $testUrl = $this->url('edit/'.$this->paramsEncode(['id' => $this->editId]));

        $data = [
            'StudentWithdraw' => [
                'id' => $this->editId,
                'effective_date' => '2016-01-01', // wrong date (before enrollment date '2016-06-01')
                'student_id' => 7,
                'status' => 0,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'student_withdraw_reason_id' => 661,
                'comment' => 'Approved'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $postData = $this->viewVariable('data');
        $errors = $postData->errors();
        $this->assertEquals(true, (array_key_exists('effective_date', $errors)));

        $StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
        $currentStatus = $StudentStatusesTable->getIdByCode('CURRENT');

        // check that student status is not changed to withdraw / still current
        $StudentsTable = TableRegistry::get('Institutions.institution_students');
        $currentStudentRecord = $StudentsTable->find()
            ->where([$StudentsTable->aliasField('student_id') => $data['StudentWithdraw']['student_id'],
                $StudentsTable->aliasField('institution_id') => $data['StudentWithdraw']['institution_id'],
                $StudentsTable->aliasField('academic_period_id') => $data['StudentWithdraw']['academic_period_id'],
                $StudentsTable->aliasField('education_grade_id') => $data['StudentWithdraw']['education_grade_id'],
                $StudentsTable->aliasField('student_status_id') => $currentStatus])
            ->first();
        $this->assertEquals(true, (!empty($currentStudentRecord)));
    }

    public function testReject() {
        $testUrl = $this->url('edit/'.$this->paramsEncode(['id' => $this->editId]));

        $data = [
            'StudentWithdraw' => [
                'id' => $this->editId,
                'effective_date' => '2016-08-01',
                'student_id' => 7,
                'status' => 0,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'student_withdraw_reason_id' => 661,
                'comment' => 'Rejected'
            ],
            'submit' => 'reject'
        ];
        $this->postData($testUrl, $data);

        $WithdrawTable = TableRegistry::get('Institutions.institution_student_withdraw');
        $rejectedRecord = $WithdrawTable->find()
            ->where([$WithdrawTable->aliasField('id') => $data['StudentWithdraw']['id'],
                $WithdrawTable->aliasField('status') => $this->rejectedStatus])
            ->first();
        $this->assertEquals(true, (!empty($rejectedRecord)));

        $StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
        $currentStatus = $StudentStatusesTable->getIdByCode('CURRENT');

        // check that student status is not changed to withdraw / still current
        $StudentsTable = TableRegistry::get('Institutions.institution_students');
        $currentStudentRecord = $StudentsTable->find()
            ->where([$StudentsTable->aliasField('student_id') => $data['StudentWithdraw']['student_id'],
                $StudentsTable->aliasField('institution_id') => $data['StudentWithdraw']['institution_id'],
                $StudentsTable->aliasField('academic_period_id') => $data['StudentWithdraw']['academic_period_id'],
                $StudentsTable->aliasField('education_grade_id') => $data['StudentWithdraw']['education_grade_id'],
                $StudentsTable->aliasField('student_status_id') => $currentStatus])
            ->first();
        $this->assertEquals(true, (!empty($currentStudentRecord)));
    }
}
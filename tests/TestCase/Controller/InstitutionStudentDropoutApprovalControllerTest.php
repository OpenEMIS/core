<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionStudentDropoutApprovalControllerTest extends AppTestCase
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
        'app.institution_custom_field_values',
        'app.institution_custom_fields',
        'app.survey_forms',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.survey_rules',
        'app.education_programmes',
        'app.student_statuses',
        'app.institution_class_students',
        'app.institution_grades',
        'app.institution_classes',
        'app.institution_student_dropout',
        'app.institution_students',
        'app.institutions',
        'app.academic_periods',
        'app.education_grades',
        'app.student_dropout_reasons'
    ];

    private $editId = 1;

    public function setup()
    {
        parent::setUp();

        $this->setAuthSession();
        $this->urlPrefix('/Dashboard/StudentDropout/');
    }

    public function testApprove()
    {
        $testUrl = $this->url('edit/' . $this->editId);
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'StudentDropout' => [
                'id' => $this->editId,
                'effective_date' => '2016-08-01', // correct date (after enrollment date '2016-06-01')
                'student_id' => 7,
                'status' => 0,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'student_dropout_reason_id' => 663,
                'comment' => 'Approved'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $DropoutTable = TableRegistry::get('Institutions.institution_student_dropout');
        $approvedRecord = $DropoutTable->find()
            ->where([$DropoutTable->aliasField('id') => $data['StudentDropout']['id'],
                $DropoutTable->aliasField('effective_date') => $data['StudentDropout']['effective_date'],
                $DropoutTable->aliasField('comment') => $data['StudentDropout']['comment'],
                $DropoutTable->aliasField('status') => 1])
            ->first();
        $this->assertEquals(true, (!empty($approvedRecord)));


        $StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
        $dropoutStatus = $StudentStatusesTable->getIdByCode('DROPOUT');

        $StudentsTable = TableRegistry::get('Institutions.institution_students');
        $dropoutStudentRecord = $StudentsTable->find()
            ->where([$StudentsTable->aliasField('student_id') => $data['StudentDropout']['student_id'],
                $StudentsTable->aliasField('institution_id') => $data['StudentDropout']['institution_id'],
                $StudentsTable->aliasField('academic_period_id') => $data['StudentDropout']['academic_period_id'],
                $StudentsTable->aliasField('education_grade_id') => $data['StudentDropout']['education_grade_id'],
                $StudentsTable->aliasField('student_status_id') => $dropoutStatus])
            ->first();
        $this->assertEquals(true, (!empty($dropoutStudentRecord)));
    }

    public function testApproveWrongDate()
    {
        $testUrl = $this->url('edit/' . $this->editId);

        $data = [
            'StudentDropout' => [
                'id' => $this->editId,
                'effective_date' => '2016-01-01', // wrong date (before enrollment date '2016-06-01')
                'student_id' => 7,
                'status' => 0,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'student_dropout_reason_id' => 663,
                'comment' => 'Approved'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $DropoutTable = TableRegistry::get('Institutions.institution_student_dropout');
        $approvedRecord = $DropoutTable->find()
            ->where([$DropoutTable->aliasField('id') => $data['StudentDropout']['id'],
                $DropoutTable->aliasField('effective_date') => $data['StudentDropout']['effective_date'],
                $DropoutTable->aliasField('comment') => $data['StudentDropout']['comment'],
                $DropoutTable->aliasField('status') => 1])
            ->first();
        $this->assertEquals(true, (empty($approvedRecord)));


        $StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
        $dropoutStatus = $StudentStatusesTable->getIdByCode('DROPOUT');

        $StudentsTable = TableRegistry::get('Institutions.institution_students');
        $dropoutStudentRecord = $StudentsTable->find()
            ->where([$StudentsTable->aliasField('student_id') => $data['StudentDropout']['student_id'],
                $StudentsTable->aliasField('institution_id') => $data['StudentDropout']['institution_id'],
                $StudentsTable->aliasField('academic_period_id') => $data['StudentDropout']['academic_period_id'],
                $StudentsTable->aliasField('education_grade_id') => $data['StudentDropout']['education_grade_id'],
                $StudentsTable->aliasField('student_status_id') => $dropoutStatus])
            ->first();
        $this->assertEquals(true, (empty($dropoutStudentRecord)));
    }

    public function testReject() {
        $testUrl = $this->url('edit/' . $this->editId);

        $data = [
            'StudentDropout' => [
                'id' => $this->editId,
                'effective_date' => '2016-08-01',
                'student_id' => 7,
                'status' => 0,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'student_dropout_reason_id' => 663,
                'comment' => 'Rejected'
            ],
            'submit' => 'reject'
        ];
        $this->postData($testUrl, $data);

        $DropoutTable = TableRegistry::get('Institutions.institution_student_dropout');
        $approvedRecord = $DropoutTable->find()
            ->where([$DropoutTable->aliasField('id') => $data['StudentDropout']['id'],
                $DropoutTable->aliasField('status') => 2])
            ->first();
        $this->assertEquals(true, (!empty($approvedRecord)));

        $StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
        $currentStatus = $StudentStatusesTable->getIdByCode('CURRENT');

        $StudentsTable = TableRegistry::get('Institutions.institution_students');
        $dropoutStudentRecord = $StudentsTable->find()
            ->where([$StudentsTable->aliasField('student_id') => $data['StudentDropout']['student_id'],
                $StudentsTable->aliasField('institution_id') => $data['StudentDropout']['institution_id'],
                $StudentsTable->aliasField('academic_period_id') => $data['StudentDropout']['academic_period_id'],
                $StudentsTable->aliasField('education_grade_id') => $data['StudentDropout']['education_grade_id'],
                $StudentsTable->aliasField('student_status_id') => $currentStatus])
            ->first();
        $this->assertEquals(true, (!empty($dropoutStudentRecord)));
    }
}
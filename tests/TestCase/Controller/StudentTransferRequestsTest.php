<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use Cake\I18n\Date;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class StudentTransferRequestsTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $primaryKey = ['id' => 106];
    private $modelAlias = 'TransferRequests';

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

    public function testCreate()
    {
        $this->setInstitutionSession(1);
        $this->setStudentSession(3);

        $params = ['student_id' => '3333', 'user_id' => 3];
        $hash = $this->setUrlParams(['controller' => 'Institutions', 'action' => 'TransferRequests', 'add'], $params);

        $url = '/Institutions/TransferRequests/add?hash=' . $hash;

        $this->get($url);
        $this->assertResponseCode(200);

        $data = [
            $this->modelAlias => [
                'id' => 104,
                'start_date' => '2017-01-01',
                'end_date' => '2017-12-31',
                'requested_date' => '2017-10-01',
                'student_id' => 3,
                'status' => 0,
                'institution_id' => 13,
                'academic_period_id' => 26,
                'education_grade_id' => 74,
                'new_education_grade_id' => 74,
                'institution_class_id' => NULL,
                'previous_institution_id' => 1,
                'student_transfer_reason_id' => 575,
                'comment' => '',
                'type' => 2,
                'modified_user_id' => 2,
                'modified' => '2017-10-20 07:50:35',
                'created_user_id' => 2,
                'created' => '2017-10-20 07:43:25'
            ]
        ];

        $this->postData($url, $data);

        $table = TableRegistry::get('Institution.TransferRequests');
        $entity = $table->get($data['TransferRequests']['id']);
        $this->assertEquals($data['TransferRequests']['id'], $entity->id);
    }

    public function testCreateWithEmptyRequestedDate()
    {
        $this->setInstitutionSession(1);
        $this->setStudentSession(3);

        $params = ['student_id' => '3333', 'user_id' => 3];
        $hash = $this->setUrlParams(['controller' => 'Institutions', 'action' => 'TransferRequests', 'add'], $params);

        $url = '/Institutions/TransferRequests/add?hash=' . $hash;

        $this->get($url);
        $this->assertResponseCode(200);

        $data = [
            $this->modelAlias => [
                'id' => 104,
                'start_date' => '2017-01-01',
                'end_date' => '2017-12-31',
                'requested_date' => NULL,
                'student_id' => 3,
                'status' => 0,
                'institution_id' => 13,
                'academic_period_id' => 26,
                'education_grade_id' => 74,
                'new_education_grade_id' => 74,
                'institution_class_id' => NULL,
                'previous_institution_id' => 1,
                'student_transfer_reason_id' => 575,
                'comment' => '',
                'type' => 2,
                'modified_user_id' => 2,
                'modified' => '2017-10-20 00:00:00',
                'created_user_id' => 2,
                'created' => '2017-10-20 00:00:00'
            ]
        ];

        $this->postData($url, $data);

        $table = TableRegistry::get('Institution.TransferRequests');
        $entity = $table->get($data['TransferRequests']['id']);
        $this->assertEquals(new Date($data['TransferRequests']['created']), $entity->requested_date);
    }

    public function testRead()
    {
        $this->setInstitutionSession(1);
        $this->setStudentSession(3);

        $url = '/Institutions/TransferRequests/view/'.$this->paramsEncode($this->primaryKey);

        $this->get($url);
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    }

    public function testUpdate()
    {
        $this->setInstitutionSession(1);
        $this->setStudentSession(3);

        $url = '/Institutions/TransferRequests/edit/'.$this->paramsEncode($this->primaryKey);

        $this->get($url);
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));

        $table = TableRegistry::get('Institution.TransferRequests');
        $entity = $table->get($this->primaryKey);

        $data = [
            $this->modelAlias => [
                'id' => $entity->id,
                'previous_institution_id' => $entity->previous_institution_id,
                'education_grade_id' => $entity->education_grade_id,
                'academic_period_id' => $entity->academic_period_id,
                'institution_id' =>  $entity->institution_id,
                'student_id' => $entity->student_id,
                'new_education_grade_id' => $entity->new_education_grade_id,
                'student_transfer_reason_id' => 653
            ]
        ];

        $this->postData($url, $data);

        $entity = $table->get($this->primaryKey);
        $this->assertEquals($data[$this->modelAlias]['student_transfer_reason_id'], $entity->student_transfer_reason_id);
    }

    public function testDelete()
    {
        $this->setInstitutionSession(1);
        $this->setStudentSession(3);

        $url = '/Institutions/TransferRequests/view/' . $this->paramsEncode($this->primaryKey);
        $this->get($url);
        $this->assertResponseOk();

        // $this->deleteData($url);

        // $table = TableRegistry::get('Institution.TransferRequests');
        // $entity = $table->find()->where($this->primaryKey)->first();
        // $this->assertEquals($entity, null);
    }

    public function testGetDataBetweenRequestedDateToTodayDate()
    {
        $this->setInstitutionSession(1);
        $this->setStudentSession(4);
        $expected = 'InstitutionStudentAbsences';

        $params = ['student_id' => '4444', 'user_id' => 4];
        $hash = $this->setUrlParams(['controller' => 'Institutions', 'action' => 'TransferRequests', 'add'], $params);

        $url = '/Institutions/TransferRequests/add?hash=' . $hash;

        $this->get($url);
        $this->assertResponseCode(200);

        $data = [
            $this->modelAlias => [
                'id' => 100,
                'start_date' => '2017-01-01',
                'end_date' => '2017-12-31',
                'requested_date' => '2017-10-01',
                'student_id' => 4,
                'status' => 0,
                'institution_id' => 13,
                'academic_period_id' => 26,
                'education_grade_id' => 74,
                'new_education_grade_id' => 74,
                'institution_class_id' => NULL,
                'previous_institution_id' => 1,
                'student_transfer_reason_id' => 575,
                'comment' => '',
                'type' => 2,
                'modified_user_id' => 2,
                'modified' => '2017-10-20 07:50:35',
                'created_user_id' => 2,
                'created' => '2017-10-20 07:43:25'
            ]
        ];

        $this->postData($url, $data);

        $table = TableRegistry::get('Institution.TransferRequests');
        $dataBetweenDate = $table->getDataBetweenDate($data, $this->modelAlias);
        $this->assertTrue(array_key_exists($expected, $dataBetweenDate));
    }

    public function testRedirectToAssociatedPage()
    {
        $params = ['student_id' => '4444', 'user_id' => 4];
        $hash = $this->setUrlParams(['controller' => 'Institutions', 'action' => 'TransferRequests', 'add'], $params);

        $this->goToAssociatedPage($hash);

        $this->assertResponseCode(302); // redirected to associated page
        $this->assertRedirect('Institution/Institutions/TransferRequests/associated?hash=' . $hash); // compare the redirected url
    }

    public function testCheckDataInAssociatedPage()
    {
        $params = ['student_id' => '4444', 'user_id' => 4];
        $hash = $this->setUrlParams(['controller' => 'Institutions', 'action' => 'TransferRequests', 'add'], $params);

        $this->goToAssociatedPage($hash);

        // expected data in the associated page
        $expected = [
            'InstitutionStudentAbsences' => 1
        ];
        $path = 'Institution.TransferRequests.associatedData';
        $this->assertSession($expected, $path);
    }

    private function goToAssociatedPage($hash)
    {
        $this->setInstitutionSession(1);
        $this->setStudentSession(4);

        $url = '/Institutions/TransferRequests/add?hash=' . $hash;

        $data = [
            $this->modelAlias => [
                'id' => 100,
                'start_date' => '2017-01-01',
                'end_date' => '2017-12-31',
                'requested_date' => '2017-10-01',
                'student_id' => 4,
                'status' => 0,
                'institution_id' => 13,
                'academic_period_id' => 26,
                'education_grade_id' => 74,
                'new_education_grade_id' => 74,
                'institution_class_id' => NULL,
                'previous_institution_id' => 1,
                'student_transfer_reason_id' => 575,
                'comment' => '',
                'type' => 2,
                'modified_user_id' => 2,
                'modified' => '2017-10-20 07:50:35',
                'created_user_id' => 2,
                'created' => '2017-10-20 07:43:25'
            ]
        ];

        $this->postData($url, $data);
    }
}

<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class StudentTransferApprovalsTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $primaryKey = ['id' => 105];
    private $modelAlias = 'TransferApprovals';

    public function __construct()
    {
        $this->fixtures = [
            'app.institutions',
            'app.academic_periods',
            'app.institution_classes',
            'app.workflow_models',
            'app.config_product_lists',
            'app.config_items',
            'app.workflow_steps',
            'app.workflow_statuses',
            'app.workflow_statuses_steps',
            'app.labels',
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
            'app.security_users',
            'app.student_statuses',
            'app.institution_statuses',
            'app.areas',
            'app.institution_student_absences',
            'app.institution_genders',
            'app.student_behaviours'
        ];

        parent::__construct();
    }

    // public function testCreate()
    // {
    //     $this->setInstitutionSession(1);
    //     $this->setStudentSession(3);

    //     $params = ['student_id' => '3333', 'user_id' => 3];
    //     $hash = $this->setUrlParams(['controller' => 'Institutions', 'action' => 'TransferRequests', 'add'], $params);

    //     $url = '/Institutions/TransferRequests/add?hash=' . $hash;

    //     $this->get($url);
    //     $this->assertResponseCode(200);

    //     $data = [
    //         $this->modelAlias => [
    //             'id' => 104,
    //             'start_date' => '2017-01-01',
    //             'end_date' => '2017-12-31',
    //             'requested_date' => '2017-10-01',
    //             'student_id' => 3,
    //             'status' => 0,
    //             'institution_id' => 13,
    //             'academic_period_id' => 26,
    //             'education_grade_id' => 74,
    //             'new_education_grade_id' => 74,
    //             'institution_class_id' => NULL,
    //             'previous_institution_id' => 1,
    //             'student_transfer_reason_id' => 575,
    //             'comment' => '',
    //             'type' => 2,
    //             'modified_user_id' => 2,
    //             'modified' => '2017-10-20 07:50:35',
    //             'created_user_id' => 2,
    //             'created' => '2017-10-20 07:43:25'
    //         ]
    //     ];

    //     $this->postData($url, $data);

    //     $table = TableRegistry::get('Institution.TransferRequests');
    //     $entity = $table->get($data['TransferRequests']['id']);
    //     $this->assertEquals($data['TransferRequests']['id'], $entity->id);
    // }

    // public function testRead()
    // {
    //     $this->setInstitutionSession(1);
    //     $this->setStudentSession(3);

    //     $url = '/Institutions/TransferRequests/view/'.$this->paramsEncode($this->primaryKey);

    //     $this->get($url);
    //     $this->assertResponseOk();
    //     $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    // }

    public function testUpdate()
    {
        // http://localhost:8888/core2/Dashboard/TransferApprovals/edit/eyJpZCI6MTA5LCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoiM3VsamtmOWNnNmp0ZW81cDcxbmNnbTZwcmEifQ.OGM5NmIzODY3ZWEwMzVhYTZlNzVjOTgxZGI2ZDlhNzEzNWZlMTRkOWY1ZDliZjU0NDY3NzNiM2U4ZDYxOTAxYw
    //     $this->setInstitutionSession(1);
        // $this->setStudentSession();

        $url = '/Dashboard/TransferApprovals/edit/'.$this->paramsEncode($this->primaryKey);

        $this->get($url);
        $this->assertResponseOk();
    //     $this->assertEquals(true, (count($this->viewVariable('data')) == 1));

    //     $data = [
    //         $this->modelAlias => [
    //             'id' => $this->primaryKey['id'],
    //             'previous_institution_id' => 476,
    //             'education_grade_id' => 74,
    //             'academic_period_id' => 26,
    //             'institution_id' =>  1,
    //             'student_id' => 13054,
    //             'new_education_grade_id' => 74,
    //             'student_transfer_reason_id' => 653
    //         ]
    //     ];

    //     $this->postData($url, $data);

    //     $table = TableRegistry::get('Institution.TransferRequests');
    //     $entity = $table->get($this->primaryKey);
    //     $this->assertEquals($data[$this->modelAlias]['student_transfer_reason_id'], $entity->student_transfer_reason_id);
    }

    // public function testDelete()
    // {
    //     $this->setInstitutionSession(1);
    //     $this->setStudentSession(3);

    //     $url = '/Institutions/TransferRequests/view/' . $this->paramsEncode($this->primaryKey);
    //     $this->get($url);
    //     $this->assertResponseOk();

    //     // $this->deleteData($url);

    //     // $table = TableRegistry::get('Institution.TransferRequests');
    //     // $entity = $table->find()->where($this->primaryKey)->first();
    //     // $this->assertEquals($entity, null);
    // }

    // public function testGetDataBetweenRequestedDateToTodayDate()
    // {
    //     $this->setInstitutionSession(1);
    //     $this->setStudentSession(4);
    //     $expected = 'InstitutionStudentAbsences';

    //     $params = ['student_id' => '4444', 'user_id' => 4];
    //     $hash = $this->setUrlParams(['controller' => 'Institutions', 'action' => 'TransferRequests', 'add'], $params);

    //     $url = '/Institutions/TransferRequests/add?hash=' . $hash;

    //     $this->get($url);
    //     $this->assertResponseCode(200);

    //     $data = [
    //         $this->modelAlias => [
    //             'id' => 100,
    //             'start_date' => '2017-01-01',
    //             'end_date' => '2017-12-31',
    //             'requested_date' => '2017-10-01',
    //             'student_id' => 4,
    //             'status' => 0,
    //             'institution_id' => 13,
    //             'academic_period_id' => 26,
    //             'education_grade_id' => 74,
    //             'new_education_grade_id' => 74,
    //             'institution_class_id' => NULL,
    //             'previous_institution_id' => 1,
    //             'student_transfer_reason_id' => 575,
    //             'comment' => '',
    //             'type' => 2,
    //             'modified_user_id' => 2,
    //             'modified' => '2017-10-20 07:50:35',
    //             'created_user_id' => 2,
    //             'created' => '2017-10-20 07:43:25'
    //         ]
    //     ];

    //     $this->postData($url, $data);

    //     $table = TableRegistry::get('Institution.TransferRequests');
    //     $dataBetweenDate = $table->getDataBetweenDate($data, $this->modelAlias);
    //     $this->assertTrue(array_key_exists($expected, $dataBetweenDate));
    // }

    // public function testAssociatedPage()
    // {
    //     $this->setInstitutionSession(1);
    //     $this->setStudentSession(4);

    //     $this->session([
    //         'Institution' => [
    //             'TransferRequests' => [
    //                 'associated' => [
    //                     'InstitutionStudentAbsences' => 1
    //                 ]
    //             ]
    //         ]
    //     ]);

    //     $params = ['student_id' => '4444', 'user_id' => 4];
    //     $hash = $this->setUrlParams(['controller' => 'Institutions', 'action' => 'TransferRequests', 'add'], $params);

    //     $url = '/Institutions/TransferRequests/add?hash=' . $hash;

    //     $this->get($url);
    //     $this->assertResponseCode(302); // redirected to associated page
    // }

}

<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AlertsFixture extends TestFixture
{
    public $import = ['table' => 'alerts'];
    public $records = array(
        array('id' => '1','name' => 'Attendance','process_name' => 'AlertAttendance','process_id' => NULL,'modified_user_id' => NULL,'modified' => '2017-05-05 16:39:10','created_user_id' => '1','created' => '2017-02-14 09:25:23'),
        array('id' => '2','name' => 'LicenseValidity','process_name' => 'AlertLicenseValidity','process_id' => NULL,'modified_user_id' => NULL,'modified' => '2017-05-05 16:39:17','created_user_id' => '1','created' => '2017-04-10 09:55:37'),
        array('id' => '3','name' => 'RetirementWarning','process_name' => 'AlertRetirementWarning','process_id' => NULL,'modified_user_id' => NULL,'modified' => '2017-05-05 16:39:21','created_user_id' => '1','created' => '2017-04-10 09:55:37'),
        array('id' => '4','name' => 'StaffEmployment','process_name' => 'AlertStaffEmployment','process_id' => NULL,'modified_user_id' => NULL,'modified' => '2017-05-05 16:39:23','created_user_id' => '1','created' => '2017-04-10 09:55:37'),
        array('id' => '5','name' => 'StaffLeave','process_name' => 'AlertStaffLeave','process_id' => NULL,'modified_user_id' => NULL,'modified' => '2017-05-05 16:39:25','created_user_id' => '1','created' => '2017-04-10 09:55:37'),
        array('id' => '6','name' => 'StaffType','process_name' => 'AlertStaffType','process_id' => NULL,'modified_user_id' => NULL,'modified' => '2017-05-05 16:39:27','created_user_id' => '1','created' => '2017-04-10 09:55:37'),
        array('id' => '7','name' => 'LicenseRenewal','process_name' => 'AlertLicenseRenewal','process_id' => NULL,'modified_user_id' => NULL,'modified' => '2017-05-05 16:39:12','created_user_id' => '1','created' => '2017-04-25 09:01:17')
    );
}

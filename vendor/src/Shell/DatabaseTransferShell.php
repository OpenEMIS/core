<?php
namespace App\Shell;

use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Date;
use Cake\Utility\Security;
use PDOException;

class DatabaseTransferShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        
        if (!empty($this->args[0])) {
            $exit = false;           
            
            $academicPeriodId = $this->args[0];

            $this->out('Initializing Transfer of data ('.Time::now().')');

            $systemProcessId = $this->SystemProcesses->addProcess('DatabaseTransfer', getmypid(), 'Archive.TransferLogs', $this->args[0]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);
            
            while (!$exit) {
                $recordToProcess = $this->getRecords($academicPeriodId);
                $this->out($recordToProcess);
                if ($recordToProcess) {
                    try {
                        $this->out('Dispatching event to update Database Transfer');
                        $this->out('End Update for Database Transfer Status ('. Time::now() .')');
                    } catch (\Exception $e) {
                        $this->out('Error in Database Transfer');
                        $this->out($e->getMessage());
                        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
                    }
                } else {
                    $this->out('No records to update ('.Time::now().')');
                    $exit = true;
                }
            }
            $this->out('End Update for Database Transfer Status ('. Time::now() .')');
            $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
        }else{
            $this->out('Error in Database Transfer');
        }
    }

    
    public function getRecords($academicPeriodId){
        $connection = ConnectionManager::get('default');

        $DataManagementConnections = TableRegistry::get('Archive.DataManagementConnections');
        $DataManagementConnectionsData = $DataManagementConnections->find('all')
            ->select([
                'DataManagementConnections.host','DataManagementConnections.db_name','DataManagementConnections.host','DataManagementConnections.username','DataManagementConnections.password','DataManagementConnections.db_name'
            ])
            ->first();
        if ( base64_encode(base64_decode($DataManagementConnectionsData['password'], true)) === $DataManagementConnectionsData['password']){
        $db_password = $this->decrypt($DataManagementConnectionsData['password'], Security::salt());
        }
        else {
        $db_password = $dbConnection['db_password'];
        }
        $connectiontwo = ConnectionManager::config($DataManagementConnectionsData['db_name'], [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => $DataManagementConnectionsData['host'],
            'username' => $DataManagementConnectionsData['username'],
            'password' => $db_password,
            'database' => $DataManagementConnectionsData['db_name'],
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
        ]);
        $archive_connection = ConnectionManager::get($DataManagementConnectionsData['db_name']);

        //institution_staff_attendances[START]

        $Tablecollection = $archive_connection->schemaCollection();
        $tableSchema = $Tablecollection->listTables();
        if (! in_array('institution_staff_attendances', $tableSchema)) {
              $archive_connection->execute("CREATE TABLE IF NOT EXISTS `institution_staff_attendances` (
                `id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
                `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to instututions.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `date` date NOT NULL,
                `time_in` time DEFAULT NULL,
                `time_out` time DEFAULT NULL,
                `comment` text COLLATE utf8mb4_unicode_ci,
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL,
                `absence_type_id` int(11) DEFAULT '1'
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the attendance records for staff';
              ");
        }

        $InstitutionStaffAttendancesResult = TableRegistry::get('Staff.InstitutionStaffAttendances');
        $InstitutionStaffAttendancesData = $InstitutionStaffAttendancesResult->find('all')
                    ->select([
                        'InstitutionStaffAttendances.id','InstitutionStaffAttendances.staff_id',
                        'InstitutionStaffAttendances.institution_id','InstitutionStaffAttendances.academic_period_id',
                        'InstitutionStaffAttendances.date','InstitutionStaffAttendances.time_in',
                        'InstitutionStaffAttendances.time_out','InstitutionStaffAttendances.comment',
                        'InstitutionStaffAttendances.modified_user_id','InstitutionStaffAttendances.modified',
                        'InstitutionStaffAttendances.created_user_id','InstitutionStaffAttendances.created',
                        'InstitutionStaffAttendances.absence_type_id'
                    ])
                    ->where([
                        'InstitutionStaffAttendances.academic_period_id' => $academicPeriodId
                    ])
                    ->toArray();

        if(!empty($InstitutionStaffAttendancesData)){
            foreach($InstitutionStaffAttendancesData AS $InstitutionStaffAttendances){
                if(isset($InstitutionStaffAttendances['date'])){
                    if ($InstitutionStaffAttendances['date'] instanceof Time || $InstitutionStaffAttendances['date'] instanceof Date) {
                        $date = $InstitutionStaffAttendances['date']->format('Y-m-d');
                    }else {
                        $date = date('Y-m-d', strtotime($InstitutionStaffAttendances['date']));
                    }
                }
                if(isset($InstitutionStaffAttendances['time_in'])){
                    if ($InstitutionStaffAttendances['time_in'] instanceof Time || $InstitutionStaffAttendances['time_in'] instanceof Date) {
                        $time_in = $InstitutionStaffAttendances['time_in']->format('H:i:s');
                    }else {
                        $time_in = date('H:i:s', strtotime($InstitutionStaffAttendances['time_in']));
                    }
                }
                if(isset($InstitutionStaffAttendances['time_out'])){
                    if ($InstitutionStaffAttendances['time_out'] instanceof Time || $InstitutionStaffAttendances['time_out'] instanceof Date) {
                        $time_out = $InstitutionStaffAttendances['time_out']->format('H:i:s');
                    }else {
                        $time_out = date('H:i:s', strtotime($InstitutionStaffAttendances['time_out']));
                    }
                }
                if(isset($InstitutionStaffAttendances['created'])){
                    if ($InstitutionStaffAttendances['created'] instanceof Time || $InstitutionStaffAttendances['created'] instanceof Date) {
                        $created = $InstitutionStaffAttendances['created']->format('Y-m-d H:i:s');
                    }else {
                        $created = date('Y-m-d H:i:s', strtotime($InstitutionStaffAttendances['created']));
                    }
                }
                if(isset($InstitutionStaffAttendances['modified'])){
                    if ($InstitutionStaffAttendances['modified'] instanceof Time || $InstitutionStaffAttendances['modified'] instanceof Date) {
                        $modified = $InstitutionStaffAttendances['modified']->format('Y-m-d H:i:s');
                    }else {
                        $modified = date('Y-m-d H:i:s', strtotime($InstitutionStaffAttendances['modified']));
                    }
                }
                
                if(!empty($InstitutionStaffAttendances)){
                    try{
                        $statement = $archive_connection->prepare('INSERT INTO institution_staff_attendances (id, 
                        staff_id, 
                        institution_id,
                        academic_period_id,
                        date,
                        time_in,
                        time_out,
                        comment,
                        modified_user_id,
                        modified,
                        created_user_id,
                        created,
                        absence_type_id)
                        
                        VALUES (:id, 
                        :staff_id, 
                        :institution_id,
                        :academic_period_id,
                        :date,
                        :time_in,
                        :time_out,
                        :comment,
                        :modified_user_id,
                        :modified,
                        :created_user_id,
                        :created,
                        :absence_type_id)');
    
                        $statement->execute([
                        'id' => $InstitutionStaffAttendances["id"],
                        'staff_id' => $InstitutionStaffAttendances["staff_id"],
                        'institution_id' => $InstitutionStaffAttendances["institution_id"],
                        'academic_period_id' => $InstitutionStaffAttendances["academic_period_id"],
                        'date' => isset($date) ? $date : NULL,
                        'time_in' => isset($time_in) ? $time_in : NULL,
                        'time_out' => isset($time_out) ? $time_out : NULL,
                        'comment' => $InstitutionStaffAttendances["comment"],
                        'modified_user_id' => $InstitutionStaffAttendances["modified_user_id"],
                        'modified' => isset($modified) ? $modified : NULL,
                        'created_user_id' => isset($InstitutionStaffAttendances["created_user_id"]) ? $InstitutionStaffAttendances["created_user_id"] : NULL,
                        'created' => isset($created) ? $created : NULL,
                        'absence_type_id' => $InstitutionStaffAttendances["absence_type_id"]
                        ]);
                    
                    }catch (PDOException $e) {
                    }
                    
                }
            }
            
        }
        $checkconnection = ConnectionManager::get($DataManagementConnectionsData['db_name']);
        $collection = $checkconnection->schemaCollection();
        $tableSchema = $collection->listTables();
        if (in_array('institution_staff_attendances', $tableSchema)) {
            $table_name = 'institution_staff_attendances';
        }
        $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW institution_staff_attendances_archive AS SELECT * FROM $table_name");
        $stmt1->execute();
        //institution_staff_attendances[END]
        //institution_staff_leave[Start]

        $TablecollectionOne = $archive_connection->schemaCollection();
        $tableSchemaOne = $TablecollectionOne->listTables();
        if (! in_array('institution_staff_leave', $tableSchemaOne)) {
              $archive_connection->execute("CREATE TABLE IF NOT EXISTS `institution_staff_leave` (
                `id` int(11) NOT NULL,
                `date_from` date NOT NULL,
                `date_to` date NOT NULL,
                `start_time` time DEFAULT NULL,
                `end_time` time DEFAULT NULL,
                `full_day` int(1) NOT NULL DEFAULT '1',
                `comments` text COLLATE utf8mb4_unicode_ci,
                `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `staff_leave_type_id` int(11) NOT NULL COMMENT 'links to staff_leave_types.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
                `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
                `number_of_days` decimal(5,1) NOT NULL,
                `file_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `file_content` longblob,
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of leave for a specific staff';
              ");
        }
        $InstitutionStaffLeaveReasult = TableRegistry::get('Institution.StaffLeave');
        $InstitutionStaffLeaveData = $InstitutionStaffLeaveReasult->find('all')
                    ->select([
                        'StaffLeave.id','StaffLeave.date_from',
                        'StaffLeave.date_to','StaffLeave.start_time',
                        'StaffLeave.end_time','StaffLeave.full_day',
                        'StaffLeave.comments','StaffLeave.staff_id',
                        'StaffLeave.staff_leave_type_id','StaffLeave.institution_id',
                        'StaffLeave.assignee_id','StaffLeave.academic_period_id',
                        'StaffLeave.status_id','StaffLeave.number_of_days',
                        'StaffLeave.file_name','StaffLeave.file_content',
                        'StaffLeave.modified_user_id','StaffLeave.modified',
                        'StaffLeave.created_user_id','StaffLeave.created'
                    ])
                    ->where([
                        'StaffLeave.academic_period_id' => $academicPeriodId
                    ])
                    ->toArray();
        if(!empty($InstitutionStaffLeaveData)){
            foreach($InstitutionStaffLeaveData AS $InstitutionStaffLeave){
                if(isset($InstitutionStaffLeave['modified'])){
                    if ($InstitutionStaffLeave['modified'] instanceof Time || $InstitutionStaffLeave['modified'] instanceof Date) {
                        $modified = $InstitutionStaffLeave['modified']->format('Y-m-d H:i:s');
                    }else {
                        $modified = date('Y-m-d H:i:s', strtotime($InstitutionStaffLeave['modified']));
                    }
                }
                if(isset($InstitutionStaffLeave['created'])){
                    if ($InstitutionStaffLeave['created'] instanceof Time || $InstitutionStaffLeave['created'] instanceof Date) {
                        $created = $InstitutionStaffLeave['created']->format('Y-m-d H:i:s');
                    }else {
                        $created = date('Y-m-d H:i:s', strtotime($InstitutionStaffLeave['created']));
                    }
                }
                if(isset($InstitutionStaffLeave['date_from'])){
                    if ($InstitutionStaffLeave['date_from'] instanceof Time || $InstitutionStaffLeave['date_from'] instanceof Date) {
                        $date_from = $InstitutionStaffLeave['date_from']->format('Y-m-d H:i:s');
                    }else {
                        $date_from = date('Y-m-d H:i:s', strtotime($InstitutionStaffLeave['date_from']));
                    }
                }
                if(isset($InstitutionStaffLeave['date_to'])){
                    if ($InstitutionStaffLeave['date_to'] instanceof Time || $InstitutionStaffLeave['date_to'] instanceof Date) {
                        $date_to = $InstitutionStaffLeave['date_to']->format('Y-m-d H:i:s');
                    }else {
                        $date_to = date('Y-m-d H:i:s', strtotime($InstitutionStaffLeave['date_to']));
                    }
                }
                try{
                    $statement = $archive_connection->prepare('INSERT INTO institution_staff_leave (id, 
                    date_from, 
                    date_to,
                    start_time,
                    end_time,
                    full_day,
                    comments,
                    staff_id,
                    staff_leave_type_id,
                    institution_id,
                    assignee_id,
                    academic_period_id,
                    status_id,
                    number_of_days,
                    file_name,
                    file_content,
                    modified_user_id,
                    modified,
                    created_user_id,
                    created
                    )
                    
                    VALUES (:id, 
                    :date_from, 
                    :date_to,
                    :start_time,
                    :end_time,
                    :full_day,
                    :comments,
                    :staff_id,
                    :staff_leave_type_id,
                    :institution_id,
                    :assignee_id,
                    :academic_period_id,
                    :status_id,
                    :number_of_days,
                    :file_name,
                    :file_content,
                    :modified_user_id,
                    :modified,
                    :created_user_id,
                    :created)');

                    $statement->execute([
                    'id' => $InstitutionStaffLeave["id"],
                    'date_from' => isset($date_from) ? $date_from : NULL,
                    'date_to' => isset($date_to) ? $date_to : NULL,
                    'start_time' => $InstitutionStaffLeave["start_time"],
                    'end_time' => $InstitutionStaffLeave["end_time"],
                    'full_day' => $InstitutionStaffLeave["full_day"],
                    'comments' => $InstitutionStaffLeave["comments"],
                    'staff_id' => $InstitutionStaffLeave["staff_id"],
                    'staff_leave_type_id' => $InstitutionStaffLeave["staff_leave_type_id"],
                    'institution_id' => $InstitutionStaffLeave["institution_id"],
                    'assignee_id' => isset($InstitutionStaffLeave["assignee_id"]) ? $InstitutionStaffLeave["assignee_id"] : NULL,
                    'academic_period_id' => $InstitutionStaffLeave["academic_period_id"],
                    'status_id' => $InstitutionStaffLeave["status_id"],
                    'number_of_days' => $InstitutionStaffLeave["number_of_days"],
                    'file_name' => $InstitutionStaffLeave["file_name"],
                    'file_content' => $InstitutionStaffLeave["file_content"],
                    'modified_user_id' => $InstitutionStaffLeave["modified_user_id"],
                    'modified' => isset($modified) ? $modified : NULL,
                    'created_user_id' => $InstitutionStaffLeave["created_user_id"],
                    'created' => isset($created) ? $created : NULL
                    ]);
                
                }catch (PDOException $e) {
                }
            }
            
        }
        $checkconnection = ConnectionManager::get($DataManagementConnectionsData['db_name']);
        $collection = $checkconnection->schemaCollection();
        $tableSchema = $collection->listTables();
        if (in_array('institution_staff_leave', $tableSchema)) {
            $table_name = 'institution_staff_leave';
        }
        $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW institution_staff_leave_archived AS SELECT * FROM $table_name");
        $stmt1->execute();

        //institution_staff_leave[END]

        $TablecollectionTwo = $archive_connection->schemaCollection();
        $tableSchemaTwo = $TablecollectionTwo->listTables();
        if (! in_array('assessment_item_results', $tableSchemaTwo)) {
              $archive_connection->execute("CREATE TABLE IF NOT EXISTS `assessment_item_results` (
                `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
                `marks` decimal(6,2) DEFAULT NULL,
                `assessment_grading_option_id` int(11) DEFAULT NULL,
                `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
                `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
                `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `assessment_period_id` int(11) NOT NULL COMMENT 'links to assessment_periods.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the assessment results for an individual student in an institution'
              PARTITION BY HASH (`assessment_id`)
              PARTITIONS 101");
        }

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
        $StudentAbsences = TableRegistry::get('Report.StudentAbsences');

        $InstitutionStudentAbsenceDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');

        $assessmentItemResultsData = $AssessmentItemResults->find('all')
                    ->select([
                        'AssessmentItemResults.id','AssessmentItemResults.marks','AssessmentItemResults.assessment_grading_option_id','AssessmentItemResults.student_id','AssessmentItemResults.assessment_id','AssessmentItemResults.education_subject_id','AssessmentItemResults.education_grade_id','AssessmentItemResults.academic_period_id','AssessmentItemResults.assessment_period_id','AssessmentItemResults.institution_id','AssessmentItemResults.modified_user_id','AssessmentItemResults.modified','AssessmentItemResults.created_user_id','AssessmentItemResults.created'
                    ])
                    ->where([
                        'AssessmentItemResults.academic_period_id' => $academicPeriodId
                    ])
                    ->toArray();
        if(!empty($assessmentItemResultsData)){
            foreach($assessmentItemResultsData AS $data){
                if(isset($data["modified_user_id"])){
                    $val = $data["modified_user_id"];
                }else{
                    $val = 'NULL';
                }
                if(isset($data['modified'])){
                    if ($data['modified'] instanceof Time || $data['modified'] instanceof Date) {
                        $modified = $data['modified']->format('Y-m-d H:i:s');
                    }else {
                        $modified = date('Y-m-d H:i:s', strtotime($data['modified']));
                    }
                }
                if(isset($data['created'])){
                    if ($data['created'] instanceof Time || $data['created'] instanceof Date) {
                        $created = $data['created']->format('Y-m-d');
                    }else {
                        $created = date('Y-m-d', strtotime($data['created']));
                    }
                }
    
                if(!empty($data && isset($data))){
                    try{
                        $statement = $archive_connection->prepare('INSERT INTO assessment_item_results (id, 
                        marks, 
                        assessment_grading_option_id,
                        student_id,
                        assessment_id,
                        education_subject_id,
                        education_grade_id,
                        academic_period_id,
                        assessment_period_id,
                        institution_id,
                        modified_user_id,
                        modified,
                        created_user_id,
                        created)
                        
                        VALUES (:id, 
                        :marks, 
                        :assessment_grading_option_id,
                        :student_id,
                        :assessment_id,
                        :education_subject_id,
                        :education_grade_id,
                        :academic_period_id,
                        :assessment_period_id,
                        :institution_id,
                        :modified_user_id,
                        :modified,
                        :created_user_id,
                        :created)');
    
                        $statement->execute([
                        'id' => $data["id"],
                        'marks' => $data["marks"],
                        'assessment_grading_option_id' => $data["assessment_grading_option_id"],
                        'student_id' => $data["student_id"],
                        'assessment_id' => $data["assessment_id"],
                        'education_subject_id' => $data["education_subject_id"],
                        'education_grade_id' => $data["education_grade_id"],
                        'academic_period_id' => $data["academic_period_id"],
                        'assessment_period_id' => $data["assessment_period_id"],
                        'institution_id' => $data["institution_id"],
                        'modified_user_id' => isset($data["modified_user_id"]) ? $data["modified_user_id"] : NULL,
                        'modified' => isset($modified) ? $modified : NULL,
                        'created_user_id' => $data["created_user_id"],
                        'created' => isset($created) ? $created : NULL,
                        ]);
                    
                    }catch (PDOException $e) {
                        
                    }
                    
                }
            }
        }
        
        $checkconnection = ConnectionManager::get($DataManagementConnectionsData['db_name']);
        $collection = $checkconnection->schemaCollection();
        $tableSchema = $collection->listTables();
        if (in_array('assessment_item_results', $tableSchema)) {
            $assessment_item_results_table_name = 'assessment_item_results';
        }
        $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW assessment_item_results_archived AS SELECT * FROM $assessment_item_results_table_name");
        $stmt1->execute();
        

        $classAttendanceRecordsData = $ClassAttendanceRecords->find('all')
                    ->select([
                        'ClassAttendanceRecords.institution_class_id','ClassAttendanceRecords.academic_period_id','ClassAttendanceRecords.year ','ClassAttendanceRecords.month','ClassAttendanceRecords.day_1','ClassAttendanceRecords.day_2','ClassAttendanceRecords.day_3','ClassAttendanceRecords.day_4','ClassAttendanceRecords.day_5','ClassAttendanceRecords.day_6','ClassAttendanceRecords.day_7','ClassAttendanceRecords.day_8','ClassAttendanceRecords.day_9','ClassAttendanceRecords.day_10','ClassAttendanceRecords.day_11','ClassAttendanceRecords.day_12','ClassAttendanceRecords.day_13','ClassAttendanceRecords.day_14','ClassAttendanceRecords.day_15','ClassAttendanceRecords.day_16','ClassAttendanceRecords.day_17','ClassAttendanceRecords.day_18','ClassAttendanceRecords.day_19','ClassAttendanceRecords.day_20','ClassAttendanceRecords.day_21','ClassAttendanceRecords.day_22','ClassAttendanceRecords.day_23','ClassAttendanceRecords.day_24','ClassAttendanceRecords.day_25','ClassAttendanceRecords.day_26','ClassAttendanceRecords.day_27','ClassAttendanceRecords.day_28','ClassAttendanceRecords.day_29','ClassAttendanceRecords.day_30'
                    ])
                    ->where([
                        'ClassAttendanceRecords.academic_period_id' => $academicPeriodId
                    ])
                    ->toArray();
        foreach($classAttendanceRecordsData AS $data){

            if(!empty($data && isset($data))){
                try{
                    $statement = $archive_connection->prepare('INSERT INTO institution_class_attendance_records (institution_class_id,
                    academic_period_id,
                    year,
                    month,
                    day_1,
                    day_2,
                    day_3,
                    day_4,
                    day_5,
                    day_6,
                    day_7,
                    day_8,
                    day_9,
                    day_10,
                    day_11,
                    day_12,
                    day_13,
                    day_14,
                    day_15,
                    day_16,
                    day_17,
                    day_18,
                    day_19,
                    day_20,
                    day_21,
                    day_22,
                    day_23,
                    day_24,
                    day_25,
                    day_26,
                    day_27,
                    day_28,
                    day_29,
                    day_30,
                    day_31)
                    
                    VALUES (:institution_class_id, 
                    :academic_period_id,
                    :year,
                    :month,
                    :day_1,
                    :day_2,
                    :day_3,
                    :day_4,
                    :day_5,
                    :day_6,
                    :day_7,
                    :day_8,
                    :day_9,
                    :day_10,
                    :day_11,
                    :day_12,
                    :day_13,
                    :day_14,
                    :day_15,
                    :day_16,
                    :day_17,
                    :day_18,
                    :day_19,
                    :day_20,
                    :day_21,
                    :day_22,
                    :day_23,
                    :day_24,
                    :day_25,
                    :day_26,
                    :day_27,
                    :day_28,
                    :day_29,
                    :day_30,
                    :day_31)');

                    $statement->execute([
                    'institution_class_id' => $data["institution_class_id"],
                    'academic_period_id' => $data["academic_period_id"],
                    'year' => $data["year"],
                    'month' => $data["month"],
                    'day_1' => $data["day_1"],
                    'day_2' => $data["day_2"],
                    'day_3' => $data["day_3"],
                    'day_4' => $data["day_4"],
                    'day_5' => $data["day_5"],
                    'day_6' => $data["day_6"],
                    'day_7' => $data["day_7"],
                    'day_8' => $data["day_8"],
                    'day_9' => $data["day_9"],
                    'day_10' => $data["day_10"],
                    'day_11' => $data["day_11"],
                    'day_12' => $data["day_12"],
                    'day_13' => $data["day_13"],
                    'day_14' => $data["day_14"],
                    'day_15' => $data["day_15"],
                    'day_16' => $data["day_16"],
                    'day_17' => $data["day_17"],
                    'day_18' => $data["day_18"],
                    'day_19' => $data["day_19"],
                    'day_20' => $data["day_20"],
                    'day_21' => $data["day_21"],
                    'day_22' => $data["day_22"],
                    'day_23' => $data["day_23"],
                    'day_24' => $data["day_24"],
                    'day_25' => $data["day_25"],
                    'day_26' => $data["day_26"],
                    'day_27' => $data["day_27"],
                    'day_28' => $data["day_28"],
                    'day_29' => $data["day_29"],
                    'day_30' => $data["day_30"],
                    'day_31' => isset($data["day_31"]) ? $data["day_31"] : 0,
                    ]);
                }catch (PDOException $e) {
                    
                }
                
            }
        }
        

        $Tablecollection = $archive_connection->schemaCollection();
        $tableSchema = $Tablecollection->listTables();
        if (! in_array('institution_student_absences', $tableSchema)) {
              $archive_connection->execute("CREATE TABLE IF NOT EXISTS `institution_student_absences` (
                `id` int(11) NOT NULL,
                `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id',
                `education_grade_id` int(11) NOT NULL DEFAULT '0',
                `date` date NOT NULL,
                `absence_type_id` int(11) NOT NULL COMMENT 'links to student_absence_reasons.id',
                `institution_student_absence_day_id` int(11) DEFAULT NULL COMMENT 'links to institution_student_absence_days.id',
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains absence records of students for day type attendance marking';
              ");
        }

        $studentAbsencesData = $StudentAbsences->find('all')
                    ->select([
                        'StudentAbsences.id','StudentAbsences.student_id','StudentAbsences.institution_id','StudentAbsences.academic_period_id','StudentAbsences.institution_class_id','StudentAbsences.date','StudentAbsences.absence_type_id','StudentAbsences.institution_student_absence_day_id','StudentAbsences.modified_user_id','StudentAbsences.modified','StudentAbsences.created_user_id','StudentAbsences.created'
                    ])
                    ->where([
                        'StudentAbsences.academic_period_id' => $academicPeriodId
                    ])
                    ->toArray();
        foreach($studentAbsencesData AS $data){
            if(isset($data['date'])){
                if ($data['date'] instanceof Time || $data['date'] instanceof Date) {
                    $date = $data['date']->format('Y-m-d');
                }else {
                    $date = date('Y-m-d', strtotime($data['date']));
                }
            }
            if(isset($data['created'])){
                if ($data['created'] instanceof Time || $data['created'] instanceof Date) {
                    $created = $data['created']->format('Y-m-d H:i:s');
                }else {
                    $created = date('Y-m-d H:i:s', strtotime($data['created']));
                }
            }
            if(isset($data['modified'])){
                if ($data['modified'] instanceof Time || $data['modified'] instanceof Date) {
                    $modified = $data['modified']->format('Y-m-d H:i:s');
                }else {
                    $modified = date('Y-m-d H:i:s', strtotime($data['modified']));
                }
            }
            if(!empty($data && isset($data))){
                try{
                    $statement = $archive_connection->prepare('INSERT INTO institution_student_absences (id, 
                    student_id,
                    institution_id,
                    academic_period_id,
                    institution_class_id,
                    date,
                    absence_type_id,
                    institution_student_absence_day_id,
                    modified_user_id,
                    modified,
                    created_user_id,
                    created)
                    
                    VALUES (:id, 
                    :student_id,
                    :institution_id,
                    :academic_period_id,
                    :institution_class_id,
                    :date,
                    :absence_type_id,
                    :institution_student_absence_day_id,
                    :modified_user_id,
                    :modified,
                    :created_user_id,
                    :created)');

                    $statement->execute([
                    'id' => $data["id"],
                    'student_id' => $data["student_id"],
                    'institution_id' => $data["institution_id"],
                    'academic_period_id' => $data["academic_period_id"],
                    'institution_class_id' => $data["institution_class_id"],
                    'date' => isset($date) ? $date : NULL,
                    'absence_type_id' => $data["absence_type_id"],
                    'institution_student_absence_day_id' => $data["institution_student_absence_day_id"],
                    'modified_user_id' => $data["modified_user_id"],
                    'modified' => isset($modified) ? $modified : NULL,
                    'created_user_id' => $data["created_user_id"],
                    'created' => isset($created) ? $created : NULL,
                    ]);
                }catch (PDOException $e) {
                    
                }
                
            }
        }
        $checkconnection = ConnectionManager::get($DataManagementConnectionsData['db_name']);
        $collection = $checkconnection->schemaCollection();
        $tableSchema = $collection->listTables();
        if (in_array('institution_student_absences', $tableSchema)) {
            $table_name = 'institution_student_absences';
        }
        $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW institution_student_absences_archived AS SELECT * FROM $table_name");
        $stmt1->execute();
        

        $institutionStudentAbsenceDetailsData = $InstitutionStudentAbsenceDetails->find('all')
                    ->select([
                        'StudentAbsencesPeriodDetails.student_id','StudentAbsencesPeriodDetails.institution_id','StudentAbsencesPeriodDetails.academic_period_id','StudentAbsencesPeriodDetails.institution_class_id','StudentAbsencesPeriodDetails.date','StudentAbsencesPeriodDetails.period','StudentAbsencesPeriodDetails.comment','StudentAbsencesPeriodDetails.absence_type_id','StudentAbsencesPeriodDetails.student_absence_reason_id','StudentAbsencesPeriodDetails.subject_id','StudentAbsencesPeriodDetails.modified_user_id','StudentAbsencesPeriodDetails.modified','StudentAbsencesPeriodDetails.created_user_id','StudentAbsencesPeriodDetails.created'
                    ])
                    ->where([
                        'StudentAbsencesPeriodDetails.academic_period_id' => $academicPeriodId
                    ])
                    ->toArray();
        foreach($institutionStudentAbsenceDetailsData AS $data){
            if(isset($data['date'])){
                if ($data['date'] instanceof Time || $data['date'] instanceof Date) {
                    $date = $data['date']->format('Y-m-d');
                }else {
                    $date = date('Y-m-d', strtotime($data['date']));
                }
            }
            if(isset($data['created'])){
                if ($data['created'] instanceof Time || $data['created'] instanceof Date) {
                    $created = $data['created']->format('Y-m-d H:i:s');
                }else {
                    $created = date('Y-m-d H:i:s', strtotime($data['created']));
                }
            }
            if(isset($data['modified'])){
                if ($data['modified'] instanceof Time || $data['modified'] instanceof Date) {
                    $modified = $data['modified']->format('Y-m-d H:i:s');
                }else {
                    $modified = date('Y-m-d H:i:s', strtotime($data['modified']));
                }
            }

            if(!empty($data && isset($data))){
                try{
                    $statement = $archive_connection->prepare('INSERT INTO institution_student_absence_details (student_id,
                        institution_id,
                        academic_period_id,
                        institution_class_id,
                        date,
                        period,
                        comment,
                        absence_type_id,
                        student_absence_reason_id,
                        subject_id,
                        modified_user_id,
                        modified,
                        created_user_id,
                        created)
                        
                        VALUES (:student_id,
                        :institution_id,
                        :academic_period_id,
                        :institution_class_id,
                        :date,
                        :period,
                        :comment,
                        :absence_type_id,
                        :student_absence_reason_id,
                        :subject_id,
                        :modified_user_id,
                        :modified,
                        :created_user_id,
                        :created)');

                        $statement->execute([
                        'student_id' => $data["student_id"],
                        'institution_id' => $data["institution_id"],
                        'academic_period_id' => $data["academic_period_id"],
                        'institution_class_id' => $data["institution_class_id"],
                        'date' => isset($date) ? $date : NULL,
                        'period' => $data["period"],
                        'comment' => $data["comment"],
                        'absence_type_id' => $data["absence_type_id"],
                        'student_absence_reason_id' => $data["student_absence_reason_id"],
                        'subject_id' => $data["subject_id"],
                        'modified_user_id' => $data["modified_user_id"],
                        'modified' => isset($modified) ? $modified : NULL,
                        'created_user_id' => isset($data["created_user_id"]) ? $data["created_user_id"] : NULL,
                        'created' => isset($created) ? $created : NULL,
                        ]);
                }catch (PDOException $e) {
                    
                }
                
            }
        }
        

        $StudentAttendanceMarkedRecordsData = $StudentAttendanceMarkedRecords->find('all')
                    ->select([
                        'StudentAttendanceMarkedRecords.institution_id','StudentAttendanceMarkedRecords.academic_period_id','StudentAttendanceMarkedRecords.institution_class_id','StudentAttendanceMarkedRecords.date','StudentAttendanceMarkedRecords.period','StudentAttendanceMarkedRecords.subject_id',
                    ])
                    ->where([
                        'StudentAttendanceMarkedRecords.academic_period_id' => $academicPeriodId
                    ])
                    ->toArray();
        foreach($StudentAttendanceMarkedRecordsData AS $data){
            if(isset($data['date'])){
                if ($data['date'] instanceof Time || $data['date'] instanceof Date) {
                    $date = $data['date']->format('Y-m-d');
                }else {
                    $date = date('Y-m-d', strtotime($data['date']));
                }
            }

            if(!empty($data && isset($data))){
                try{
                    $statement = $archive_connection->prepare('INSERT INTO student_attendance_marked_records (institution_id,
                        academic_period_id,
                        institution_class_id,
                        date,
                        period,
                        subject_id)
                        
                        VALUES (:institution_id,
                        :academic_period_id,
                        :institution_class_id,
                        :date,
                        :period,
                        :subject_id)');

                        $statement->execute([
                        'institution_id' => $data["institution_id"],
                        'academic_period_id' => $data["academic_period_id"],
                        'institution_class_id' => $data["institution_class_id"],
                        'date' => isset($date) ? $date : NULL,
                        'period' => $data["period"],
                        'subject_id' => $data["subject_id"],
                        ]);
                }catch (PDOException $e) {
                    
                }
                
            }
        }
       

        $StudentAttendanceMarkTypesData = $StudentAttendanceMarkTypes->find('all')
                    ->select([
                        'StudentAttendanceMarkTypes.name','StudentAttendanceMarkTypes.code','StudentAttendanceMarkTypes.education_grade_id','StudentAttendanceMarkTypes.academic_period_id','StudentAttendanceMarkTypes.student_attendance_type_id','StudentAttendanceMarkTypes.attendance_per_day','StudentAttendanceMarkTypes.modified_user_id','StudentAttendanceMarkTypes.modified',
                        'StudentAttendanceMarkTypes.created_user_id','StudentAttendanceMarkTypes.created',
                    ])
                    ->where([
                        'StudentAttendanceMarkTypes.academic_period_id' => $academicPeriodId
                    ])
                    ->toArray();
        foreach($StudentAttendanceMarkTypesData AS $data){
            if(isset($data['created'])){
                if ($data['created'] instanceof Time || $data['created'] instanceof Date) {
                    $created = $data['created']->format('Y-m-d');
                }else {
                    $created = date('Y-m-d', strtotime($data['created']));
                }
            }
            if(isset($data['modified'])){
                if ($data['modified'] instanceof Time || $data['modified'] instanceof Date) {
                    $modified = $data['modified']->format('Y-m-d H:i:s');
                }else {
                    $modified = date('Y-m-d H:i:s', strtotime($data['modified']));
                }
            }
            if(!empty($data && isset($data))){
                try{
                    $statement = $archive_connection->prepare('INSERT INTO student_attendance_mark_types (id,
                        name,
                        code,
                        education_grade_id,
                        academic_period_id,
                        student_attendance_type_id,
                        attendance_per_day,
                        modified_user_id,
                        modified,
                        created_user_id,
                        created)
                        
                        VALUES (:id,
                        :name,
                        :code,
                        :education_grade_id,
                        :academic_period_id,
                        :student_attendance_type_id,
                        :attendance_per_day,
                        :modified_user_id,
                        :modified,
                        :created_user_id,
                        :created)');

                        $statement->execute([
                        'id' => $data["id"],
                        'name' => $data["name"],
                        'code' => $data["code"],
                        'education_grade_id' => $data["education_grade_id"],
                        'academic_period_id' => $data["academic_period_id"],
                        'student_attendance_type_id' => $data["student_attendance_type_id"],
                        'attendance_per_day' => $data["attendance_per_day"],
                        'modified_user_id' => $data["modified_user_id"],
                        'modified' => isset($modified) ? $modified : NULL,
                        'created_user_id' => $data["created_user_id"],
                        'created' => isset($created) ? $created : NULL,
                        ]);
                }catch (PDOException $e) {
                    
                }
                
            }
        }
        //POCOR-6799[START]
        $InstitutionStaffAttendancesData->deleteAll(['academic_period_id' => $academicPeriodId]);
        $InstitutionStaffLeaveData->deleteAll(['academic_period_id' => $academicPeriodId]);
        $AssessmentItemResults->deleteAll(['academic_period_id' => $academicPeriodId]);
        $StudentAbsences->deleteAll(['academic_period_id' => $academicPeriodId]);
        $InstitutionStudentAbsenceDetails->deleteAll(['academic_period_id' => $academicPeriodId]);
        $StudentAttendanceMarkedRecords->deleteAll(['academic_period_id' => $academicPeriodId]);
        //POCOR-6799[END]
        $statement = $connection->execute('DELETE FROM student_attendance_mark_types WHERE academic_period_id ='.$academicPeriodId);
        return true;
    }

    public function decrypt($encrypted_string, $secretHash) {

        $iv = substr($secretHash, 0, 16);
        $data = base64_decode($encrypted_string);
        $decryptedMessage = openssl_decrypt($data, "AES-256-CBC", $secretHash, $raw_input = false, $iv);
        $decrypted = rtrim(
            $decryptedMessage
        );
        return $decrypted;
    }


    public function getRecordsbkp($academicPeriodId)
    {
        //get archive database connection
        $connection = ConnectionManager::get('default');

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
        $StudentAbsences = TableRegistry::get('Report.StudentAbsences');

        $InstitutionStudentAbsenceDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $StudentAttendanceMarkType = TableRegistry::get('Attendance.StudentAttendanceMarkTypesTable');
        //institution_student_absence_days -- couldn't find model regarding this table in master branch

        $allData = $AcademicPeriods->find('all')
                                    ->select([
                                        'AcademicPeriods.id','AcademicPeriods.parent_id','AcademicPeriods.name',
                                       'AssessmentItemResults.id','AssessmentItemResults.marks','AssessmentItemResults.assessment_grading_option_id','AssessmentItemResults.student_id','AssessmentItemResults.assessment_id','AssessmentItemResults.education_subject_id','AssessmentItemResults.education_grade_id','AssessmentItemResults.academic_period_id','AssessmentItemResults.assessment_period_id','AssessmentItemResults.institution_id','AssessmentItemResults.modified_user_id','AssessmentItemResults.modified','AssessmentItemResults.created_user_id','AssessmentItemResults.created',
                                        
                                        'ClassAttendanceRecords.institution_class_id','ClassAttendanceRecords.academic_period_id','ClassAttendanceRecords.year ','ClassAttendanceRecords.month','ClassAttendanceRecords.day_1','ClassAttendanceRecords.day_2','ClassAttendanceRecords.day_3','ClassAttendanceRecords.day_4','ClassAttendanceRecords.day_5','ClassAttendanceRecords.day_6','ClassAttendanceRecords.day_7','ClassAttendanceRecords.day_8','ClassAttendanceRecords.day_9','ClassAttendanceRecords.day_10','ClassAttendanceRecords.day_11','ClassAttendanceRecords.day_12','ClassAttendanceRecords.day_13','ClassAttendanceRecords.day_14','ClassAttendanceRecords.day_15','ClassAttendanceRecords.day_16','ClassAttendanceRecords.day_17','ClassAttendanceRecords.day_18','ClassAttendanceRecords.day_19','ClassAttendanceRecords.day_20','ClassAttendanceRecords.day_21','ClassAttendanceRecords.day_22','ClassAttendanceRecords.day_23','ClassAttendanceRecords.day_24','ClassAttendanceRecords.day_25','ClassAttendanceRecords.day_26','ClassAttendanceRecords.day_27','ClassAttendanceRecords.day_28','ClassAttendanceRecords.day_29','ClassAttendanceRecords.day_30',
                                        
                                        'StudentAbsences.id','StudentAbsences.student_id','StudentAbsences.institution_id','StudentAbsences.academic_period_id','StudentAbsences.institution_class_id','StudentAbsences.date','StudentAbsences.absence_type_id','StudentAbsences.institution_student_absence_day_id','StudentAbsences.modified_user_id','StudentAbsences.modified','StudentAbsences.created_user_id','StudentAbsences.created',
                                        
                                        'StudentAbsencesPeriodDetails.student_id','StudentAbsencesPeriodDetails.institution_id','StudentAbsencesPeriodDetails.academic_period_id','StudentAbsencesPeriodDetails.institution_class_id','StudentAbsencesPeriodDetails.date','StudentAbsencesPeriodDetails.period','StudentAbsencesPeriodDetails.comment','StudentAbsencesPeriodDetails.absence_type_id','StudentAbsencesPeriodDetails.student_absence_reason_id','StudentAbsencesPeriodDetails.subject_id','StudentAbsencesPeriodDetails.modified_user_id','StudentAbsencesPeriodDetails.modified',
                                        
                                        'StudentAttendanceMarkedRecords.institution_id','StudentAttendanceMarkedRecords.academic_period_id','StudentAttendanceMarkedRecords.institution_class_id','StudentAttendanceMarkedRecords.date','StudentAttendanceMarkedRecords.period','StudentAttendanceMarkedRecords.subject_id',
                                        
                                        'StudentAttendanceMarkTypesTable.education_grade_id','StudentAttendanceMarkTypesTable.academic_period_id','StudentAttendanceMarkTypesTable.student_attendance_type_id','StudentAttendanceMarkTypesTable.attendance_per_day','StudentAttendanceMarkTypesTable.modified_user_id','StudentAttendanceMarkTypesTable.modified',
                                        'StudentAttendanceMarkTypesTable.created_user_id','StudentAttendanceMarkTypesTable.created',
                                    ])
                                    ->leftJoin(
                                        ['AssessmentItemResults' => 'assessment_item_results'],
                                        [
                                            'AssessmentItemResults.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['ClassAttendanceRecords' => 'institution_class_attendance_records'],
                                        [
                                            'ClassAttendanceRecords.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['StudentAbsences' => 'institution_student_absences'],
                                        [
                                            'StudentAbsences.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['StudentAbsencesPeriodDetails' => 'institution_student_absence_details'],
                                        [
                                            'StudentAbsencesPeriodDetails.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['StudentAttendanceMarkedRecords' => 'student_attendance_marked_records'],
                                        [
                                            'StudentAttendanceMarkedRecords.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['StudentAttendanceMarkTypesTable' => 'student_attendance_mark_types'],
                                        [
                                            'StudentAttendanceMarkTypesTable.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->where([
                                        'AcademicPeriods.id' => $academicPeriodId
                                    ])
                                    ->limit(10)
                                    ->toArray();
         //get archive database connection
         $connection = ConnectionManager::get('prd_cor_arc');
        
        if(!empty($allData) && isset($allData)){
            foreach($allData as $data){

                if(!empty($data['AssessmentItemResults'] && isset($data['AssessmentItemResults']))){

                    $connection->execute('INSERT INTO assessment_item_results VALUES ("'.$data['AssessmentItemResults']["id"].'","'.$data['AssessmentItemResults']["marks"].'","'.$data['AssessmentItemResults']["assessment_grading_option_id"].'","'.$data['AssessmentItemResults']["student_id"].'","'.$data['AssessmentItemResults']["assessment_id"].'","'.$data['AssessmentItemResults']["education_subject_id"].'","'.$data['AssessmentItemResults']["education_grade_id"].'","'.$data['AssessmentItemResults']["academic_period_id"].'","'.$data['AssessmentItemResults']["assessment_period_id"].'","'.$data['AssessmentItemResults']["institution_id"].'","'.$data['AssessmentItemResults']["modified_user_id"].'","'.$data['AssessmentItemResults']["modified"].'","'.$data['AssessmentItemResults']["created_user_id"].'","'.$data['AssessmentItemResults']["created"].'")');
                }

                if(!empty($data['ClassAttendanceRecords'] && isset($data['ClassAttendanceRecords']))){

                    $connection->execute('INSERT INTO institution_class_attendance_records VALUES ("'.$data['ClassAttendanceRecords']["institution_class_id"].'","'.$data['ClassAttendanceRecords']["academic_period_id"].'","'.$data['ClassAttendanceRecords']["year"].'","'.$data['ClassAttendanceRecords']["month"].'","'.$data['ClassAttendanceRecords']["day_1"].'","'.$data['ClassAttendanceRecords']["day_2"].'","'.$data['ClassAttendanceRecords']["day_3"].'","'.$data['ClassAttendanceRecords']["day_4"].'","'.$data['ClassAttendanceRecords']["day_5"].'","'.$data['ClassAttendanceRecords']["day_6"].'","'.$data['ClassAttendanceRecords']["day_7"].'","'.$data['ClassAttendanceRecords']["day_8"].'","'.$data['ClassAttendanceRecords']["day_9"].'","'.$data['ClassAttendanceRecords']["day_10"].'","'.$data['ClassAttendanceRecords']["day_11"].'","'.$data['ClassAttendanceRecords']["day_12"].'","'.$data['ClassAttendanceRecords']["day_13"].'","'.$data['ClassAttendanceRecords']["day_14"].'","'.$data['ClassAttendanceRecords']["day_15"].'","'.$data['ClassAttendanceRecords']["day_16"].'","'.$data['ClassAttendanceRecords']["day_17"].'","'.$data['ClassAttendanceRecords']["day_18"].'","'.$data['ClassAttendanceRecords']["day_19"].'","'.$data['ClassAttendanceRecords']["day_20"].'","'.$data['ClassAttendanceRecords']["day_21"].'","'.$data['ClassAttendanceRecords']["day_22"].'","'.$data['ClassAttendanceRecords']["day_23"].'","'.$data['ClassAttendanceRecords']["day_24"].'","'.$data['ClassAttendanceRecords']["day_25"].'","'.$data['ClassAttendanceRecords']["day_26"].'","'.$data['ClassAttendanceRecords']["day_27"].'","'.$data['ClassAttendanceRecords']["day_28"].'","'.$data['ClassAttendanceRecords']["day_29"].'","'.$data['ClassAttendanceRecords']["day_30"].'","'.$data['ClassAttendanceRecords']["day_31"].'")');
                }

                if(!empty($data['StudentAbsences'] && isset($data['StudentAbsences']))){

                    $connection->execute('INSERT INTO institution_student_absences VALUES ("'.$data['StudentAbsences']["id"].'","'.$data['StudentAbsences']["student_id"].'","'.$data['StudentAbsences']["institution_id"].'","'.$data['StudentAbsences']["academic_period_id"].'","'.$data['StudentAbsences']["institution_class_id"].'","'.$data['StudentAbsences']["date"].'","'.$data['StudentAbsences']["absence_type_id"].'","'.$data['StudentAbsences']["institution_student_absence_day_id"].'","'.$data['StudentAbsences']["modified_user_id"].'","'.$data['StudentAbsences']["modified"].'","'.$data['StudentAbsences']["created_user_id"].'","'.$data['StudentAbsences']["created"].'")');
                }

                if(!empty($data['StudentAbsencesPeriodDetails'] && isset($data['StudentAbsencesPeriodDetails']))){

                    $connection->execute('INSERT INTO institution_student_absence_details VALUES ("'.$data['StudentAbsencesPeriodDetails']["student_id"].'","'.$data['StudentAbsencesPeriodDetails']["institution_id"].'","'.$data['StudentAbsencesPeriodDetails']["academic_period_id"].'","'.$data['StudentAbsencesPeriodDetails']["institution_class_id"].'","'.$data['StudentAbsencesPeriodDetails']["date"].'","'.$data['StudentAbsencesPeriodDetails']["period"].'","'.$data['StudentAbsencesPeriodDetails']["comment"].'","'.$data['StudentAbsencesPeriodDetails']["absence_type_id"].'","'.$data['StudentAbsencesPeriodDetails']["student_absence_reason_id"].'","'.$data['StudentAbsencesPeriodDetails']["subject_id"].'","'.$data['StudentAbsencesPeriodDetails']["modified_user_id"].'","'.$data['StudentAbsencesPeriodDetails']["modified"].'","'.$data['StudentAbsencesPeriodDetails']["created_user_id"].'","'.$data['StudentAbsencesPeriodDetails']["created"].'")');

                }

                if(!empty($data['StudentAttendanceMarkedRecords'] && isset($data['StudentAttendanceMarkedRecords']))){

                    $connection->execute('INSERT INTO student_attendance_marked_records VALUES ("'.$data['StudentAttendanceMarkedRecords']["institution_id"].'","'.$data['StudentAttendanceMarkedRecords']["academic_period_id"].'","'.$data['StudentAttendanceMarkedRecords']["institution_class_id"].'","'.$data['StudentAttendanceMarkedRecords']["date"].'","'.$data['StudentAttendanceMarkedRecords']["period"].'","'.$data['StudentAttendanceMarkedRecords']["subject_id"].'")');

                }

                if(!empty($data['StudentAttendanceMarkTypesTable'] && isset($data['StudentAttendanceMarkTypesTable']))){

                    $connection->execute('INSERT INTO student_attendance_mark_types VALUES ("'.$data['StudentAttendanceMarkTypesTable']["id"].'","'.$data['StudentAttendanceMarkTypesTable']["name"].'","'.$data['StudentAttendanceMarkTypesTable']["code"].'","'.$data['StudentAttendanceMarkTypesTable']["education_grade_id"].'","'.$data['StudentAttendanceMarkTypesTable']["academic_period_id"].'","'.$data['StudentAttendanceMarkTypesTable']["student_attendance_type_id"].'","'.$data['StudentAttendanceMarkTypesTable']["attendance_per_day"].'","'.$data['StudentAttendanceMarkTypesTable']["modified_user_id "].'","'.$data['StudentAttendanceMarkTypesTable']["modified "].'","'.$data['StudentAttendanceMarkTypesTable']["created_user_id"].'","'.$data['StudentAttendanceMarkTypesTable']["created"].'")');

                }
            }

            /** Deleting all academic period associated table's data according to the requirement */

            $AssessmentItemResults->deleteAll(['academic_period_id' => $academicPeriodId]);
            $ClassAttendanceRecords->deleteAll(['academic_period_id' => $academicPeriodId]);
            $StudentAbsences->deleteAll(['academic_period_id' => $academicPeriodId]);
            $InstitutionStudentAbsenceDetails->deleteAll(['academic_period_id' => $academicPeriodId]);
            $StudentAttendanceMarkedRecords->deleteAll(['academic_period_id' => $academicPeriodId]);
            $StudentAttendanceMarkType->deleteAll(['academic_period_id' => $academicPeriodId]);
        }
        /****************************************************************************************************************************************** */
       
        return true;
    }
}
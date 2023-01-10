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

class StudentAttendanceShell extends Shell
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
        if ( base64_encode(base64_decode($DataManagementConnectionsData->password, true)) === $DataManagementConnectionsData->password){
        $db_password = $this->decrypt($DataManagementConnectionsData->password, Security::salt());
        }
        else {
        $db_password = $dbConnection->db_password;
        }
        ConnectionManager::drop($DataManagementConnectionsData->db_name);
        $connectiontwo = ConnectionManager::config($DataManagementConnectionsData->db_name, [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => $DataManagementConnectionsData->host,
            'username' => $DataManagementConnectionsData->username,
            'password' => $db_password,
            'database' => $DataManagementConnectionsData->db_name,
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
        ]);
        $archive_connection = ConnectionManager::get($DataManagementConnectionsData->db_name);

        //institution_staff_attendances[START]

        $Tablecollection = $archive_connection->schemaCollection();
        $tableSchema = $Tablecollection->listTables();

        if (! in_array('institution_class_attendance_records', $tableSchema)) {
            $archive_connection->execute("CREATE TABLE IF NOT EXISTS `institution_class_attendance_records` (
                `institution_class_id` int(11) NOT NULL COMMENT 'link to institution_classes.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'link to academic_period.id',
                `year` int(4) NOT NULL,
                `month` int(2) NOT NULL COMMENT 'Jan = 1, Dec = 12',
                `day_1` int(1) NOT NULL DEFAULT '0',
                `day_2` int(1) NOT NULL DEFAULT '0',
                `day_3` int(1) NOT NULL DEFAULT '0',
                `day_4` int(1) NOT NULL DEFAULT '0',
                `day_5` int(1) NOT NULL DEFAULT '0',
                `day_6` int(1) NOT NULL DEFAULT '0',
                `day_7` int(1) NOT NULL DEFAULT '0',
                `day_8` int(1) NOT NULL DEFAULT '0',
                `day_9` int(1) NOT NULL DEFAULT '0',
                `day_10` int(1) NOT NULL DEFAULT '0',
                `day_11` int(1) NOT NULL DEFAULT '0',
                `day_12` int(1) NOT NULL DEFAULT '0',
                `day_13` int(1) NOT NULL DEFAULT '0',
                `day_14` int(1) NOT NULL DEFAULT '0',
                `day_15` int(1) NOT NULL DEFAULT '0',
                `day_16` int(1) NOT NULL DEFAULT '0',
                `day_17` int(1) NOT NULL DEFAULT '0',
                `day_18` int(1) NOT NULL DEFAULT '0',
                `day_19` int(1) NOT NULL DEFAULT '0',
                `day_20` int(1) NOT NULL DEFAULT '0',
                `day_21` int(1) NOT NULL DEFAULT '0',
                `day_22` int(1) NOT NULL DEFAULT '0',
                `day_23` int(1) NOT NULL DEFAULT '0',
                `day_24` int(1) NOT NULL DEFAULT '0',
                `day_25` int(1) NOT NULL DEFAULT '0',
                `day_26` int(1) NOT NULL DEFAULT '0',
                `day_27` int(1) NOT NULL DEFAULT '0',
                `day_28` int(1) NOT NULL DEFAULT '0',
                `day_29` int(1) NOT NULL DEFAULT '0',
                `day_30` int(1) NOT NULL DEFAULT '0',
                `day_31` int(1) NOT NULL DEFAULT '0',
                PRIMARY KEY (`institution_class_id`,`academic_period_id`,`year`,`month`),
                KEY `institution_class_id` (`institution_class_id`),
                KEY `academic_period_id` (`academic_period_id`),
                KEY `year` (`year`),
                KEY `month` (`month`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the whether the class attendance is marked for a month'");
        }

        $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');

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
        if (in_array('institution_class_attendance_records', $tableSchema)) {
            $table_name = 'institution_class_attendance_records';
        }
        $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW institution_class_attendance_records_archived AS SELECT * FROM institution_class_attendance_records");
        $stmt1->execute();

        // $classAttendanceRecordsData->deleteAll(['academic_period_id' => $academicPeriodId]);
        //institution_class_attendance_records[END]

        //institution_student_absences[START]

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
        $StudentAbsences = TableRegistry::get('Report.StudentAbsences');

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

        if (in_array('institution_student_absences', $tableSchema)) {
            $table_name = 'institution_student_absences';
        }
        $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW institution_student_absences_archived AS SELECT * FROM institution_student_absences");
        $stmt1->execute();

        // $studentAbsencesData->deleteAll(['academic_period_id' => $academicPeriodId]);
        //institution_student_absences[END]

        //institution_student_absence_details[START]
        if (! in_array('institution_student_absence_details', $tableSchema)) {
            $archive_connection->execute("CREATE TABLE IF NOT EXISTS `institution_student_absence_details` (
                `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id',
                `education_grade_id` int(11) NOT NULL DEFAULT '0',
                `date` date NOT NULL,
                `period` int(1) NOT NULL,
                `comment` text,
                `absence_type_id` int(11) NOT NULL COMMENT 'links to student_absence_reasons.id',
                `student_absence_reason_id` int(11) DEFAULT NULL COMMENT 'links to absence_types.id',
                `subject_id` int(11) NOT NULL DEFAULT '0',
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL,
                PRIMARY KEY (`student_id`,`institution_id`,`academic_period_id`,`institution_class_id`,`date`,`period`,`subject_id`),
                KEY `student_id` (`student_id`),
                KEY `institution_id` (`institution_id`),
                KEY `academic_period_id` (`academic_period_id`),
                KEY `institution_class_id` (`institution_class_id`),
                KEY `absence_type_id` (`absence_type_id`),
                KEY `student_absence_reason_id` (`student_absence_reason_id`),
                KEY `modified_user_id` (`modified_user_id`),
                KEY `created_user_id` (`created_user_id`),
                KEY `insti_stude_absen_detai_fk_edu_gra_id` (`education_grade_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains absence records of students for day type attendance marking'");
        }
        $InstitutionStudentAbsenceDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
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

        if (in_array('institution_student_absence_details', $tableSchema)) {
            $table_name = 'institution_student_absence_details';
        }
        $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW institution_student_absence_details_archived AS SELECT * FROM institution_student_absence_details");
        $stmt1->execute();

        // $institutionStudentAbsenceDetailsData->deleteAll(['academic_period_id' => $academicPeriodId]);
        //institution_student_absence_details[END]

        //student_attendance_marked_records[START]
        if (! in_array('student_attendance_marked_records', $tableSchema)) {
            $archive_connection->execute("CREATE TABLE IF NOT EXISTS `student_attendance_marked_records` (
                `institution_id` int(11) NOT NULL COMMENT 'links to instututions.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id',
                `education_grade_id` int(11) NOT NULL DEFAULT '0',
                `date` date NOT NULL,
                `period` int(1) NOT NULL,
                `subject_id` int(11) NOT NULL DEFAULT '0',
                `no_scheduled_class` tinyint(4) NOT NULL DEFAULT '0',
                PRIMARY KEY (`institution_id`,`academic_period_id`,`institution_class_id`,`education_grade_id`,`date`,`period`,`subject_id`),
                KEY `institution_id` (`institution_id`),
                KEY `academic_period_id` (`academic_period_id`),
                KEY `institution_class_id` (`institution_class_id`),
                KEY `stude_atten_marke_recor_fk_edu_gra_id` (`education_grade_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains attendance marking records'");
        }

        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
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

        if (in_array('student_attendance_marked_records', $tableSchema)) {
            $table_name = 'student_attendance_marked_records';
        }
        $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW student_attendance_marked_records_archived AS SELECT * FROM student_attendance_marked_records");
        $stmt1->execute();
        // $StudentAttendanceMarkedRecordsData->deleteAll(['academic_period_id' => $academicPeriodId]);

        //student_attendance_marked_records[END]

        //student_attendance_mark_types[START]

        if (! in_array('student_attendance_mark_types', $tableSchema)) {
            $archive_connection->execute("CREATE TABLE IF NOT EXISTS `student_attendance_mark_types` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `education_grade_id` int(11) DEFAULT NULL COMMENT 'links to education_grades.id',
                `academic_period_id` int(11) DEFAULT NULL COMMENT 'links to academic_periods.id',
                `student_attendance_type_id` int(11) NOT NULL COMMENT 'links to student_attendance_types.id',
                `attendance_per_day` int(1) NOT NULL,
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `education_grade_id` (`education_grade_id`),
                KEY `academic_period_id` (`academic_period_id`),
                KEY `student_attendance_type_id` (`student_attendance_type_id`),
                KEY `modified_user_id` (`modified_user_id`),
                KEY `created_user_id` (`created_user_id`)
               ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains different attendance marking for different academic periods for different programme'");
        }
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
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

        if (in_array('student_attendance_mark_types', $tableSchema)) {
            $table_name = 'student_attendance_mark_types';
        }
        $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW student_attendance_mark_types_archived AS SELECT * FROM student_attendance_mark_types");
        $stmt1->execute();
        // $StudentAttendanceMarkTypesData->deleteAll(['academic_period_id' => $academicPeriodId]);
        //student_attendance_mark_types[END]
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
}
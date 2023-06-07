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
        $this->loadModel('Archive.TransferLogs');
    }

    public function main()
    {
        
        if (!empty($this->args[0])) {
            $exit = false;           
            
            $academicPeriodId = $this->args[0];
            $pid = $this->args[1];

            $this->out('Initializing Transfer of data ('.Time::now().')');

            $systemProcessId = $this->SystemProcesses->addProcess('DatabaseTransfer', getmypid(), 'Archive.TransferLogs', $this->args[0]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);
            
            // while (!$exit) {
                $recordToProcess = $this->getRecords($academicPeriodId, $pid);
                $this->out($recordToProcess);
                if ($recordToProcess) {
                    try {
                        $this->out('Dispatching event to update Student Attendance Transfer');
                        $this->out('End Update for Student Attendance Transfer Status ('. Time::now() .')');
                    } catch (\Exception $e) {
                        $this->TransferLogs->updateAll(['process_status' => 3], [
                            'p_id' => $pid
                        ]);
                        $this->out('Error in Student Attendance Transfer');
                        $this->out($e->getMessage());
                        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
                    }
                } else {
                    $this->out('No records to update ('.Time::now().')');
                    $exit = true;
                }
            // }
            $this->out('End Update for Database Transfer Status ('. Time::now() .')');
            $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
        }else{
            $this->out('Error in Database Transfer');
        }
    }

    
    public function getRecords($academicPeriodId, $pid){
        //POCOR-7474-HINDOL get rid of unused connection to backup table and old comments
        $connection = ConnectionManager::get('default');

        $connection->execute("CREATE TABLE IF NOT EXISTS `institution_class_attendance_records_archived` (
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
        $connection->execute("INSERT INTO `institution_class_attendance_records_archived` SELECT * FROM `institution_class_attendance_records` WHERE academic_period_id = $academicPeriodId");
        $connection->execute("DELETE FROM institution_class_attendance_records WHERE academic_period_id = $academicPeriodId");
        $connection->execute("CREATE TABLE IF NOT EXISTS `institution_student_absences_archived` (
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
        $connection->execute("INSERT INTO `institution_student_absences_archived` SELECT * FROM `institution_student_absences` WHERE academic_period_id = $academicPeriodId");
        $connection->execute("DELETE FROM institution_student_absences WHERE academic_period_id = $academicPeriodId");

        $connection->execute("CREATE TABLE IF NOT EXISTS `institution_student_absence_details_archived` (
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
        $connection->execute("INSERT INTO `institution_student_absence_details_archived` SELECT * FROM `institution_student_absence_details` WHERE academic_period_id = $academicPeriodId");
        $connection->execute("DELETE FROM institution_student_absence_details WHERE academic_period_id = $academicPeriodId");
        // $StudentAttendanceMarkedRecordsData->deleteAll(['academic_period_id' => $academicPeriodId]);
        $connection->execute("CREATE TABLE IF NOT EXISTS `student_attendance_marked_records_archived` (
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
        $connection->execute("INSERT INTO `student_attendance_marked_records_archived` SELECT * FROM `student_attendance_marked_records` WHERE academic_period_id = $academicPeriodId");
        $connection->execute("DELETE FROM student_attendance_marked_records WHERE academic_period_id = $academicPeriodId");

        $connection->execute("CREATE TABLE IF NOT EXISTS `student_attendance_mark_types_archived` (
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
        $connection->execute("INSERT INTO `student_attendance_mark_types_archived` SELECT * FROM `student_attendance_mark_types` WHERE academic_period_id = $academicPeriodId");
        $connection->execute("DELETE FROM student_attendance_mark_types WHERE academic_period_id = $academicPeriodId");
        //student_attendance_mark_types[END]

        $this->TransferLogs->updateAll(['process_status' => 2], [
            'p_id' => $pid
        ]);
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
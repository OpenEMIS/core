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

class StaffAttendancesShell extends Shell
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

            $this->out('Initializing Staff Attendances of data ('.Time::now().')');

            $systemProcessId = $this->SystemProcesses->addProcess('DatabaseTransfer', getmypid(), 'Archive.TransferLogs', $this->args[0]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);
            
            // while (!$exit) {
                $recordToProcess = $this->getRecords($academicPeriodId);
                $this->out($recordToProcess);
                if ($recordToProcess) {
                    try {
                        $this->out('Dispatching event to update Staff Attendances Transfer');
                        $this->out('End Update for StaffAttendances Transfer Status ('. Time::now() .')');
                    } catch (\Exception $e) {
                        $this->out('Error in Staff Attendances Transfer');
                        $this->out($e->getMessage());
                        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
                    }
                } else {
                    $this->out('No records to update ('.Time::now().')');
                    $exit = true;
                }
            // }
            $this->out('End Update for StaffAttendances Transfer Status ('. Time::now() .')');
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

        ////institution_staff_attendances[END][START]

        $Tablecollection = $archive_connection->schemaCollection();
        $tableSchema = $Tablecollection->listTables();

        /*if (! in_array('institution_staff_attendances', $tableSchema)) {
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
        }*/

        // if (in_array('institution_staff_attendances', $tableSchema)) {
        //     $table_name = 'institution_staff_attendances';
        // }
        // $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW institution_staff_attendances_archive AS SELECT * FROM institution_staff_attendances");
        // $stmt1->execute();
        // $InstitutionStaffAttendancesData->deleteAll(['academic_period_id' => $academicPeriodId]);

        $connection->execute("CREATE TABLE IF NOT EXISTS `institution_staff_attendances_archive` (
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
          $connection->execute("INSERT INTO `institution_staff_attendances_archive` SELECT * FROM `institution_staff_attendances` WHERE academic_period_id = $academicPeriodId");
          $connection->execute("DELETE FROM institution_staff_attendances WHERE academic_period_id = $academicPeriodId");
        //institution_staff_attendances[END]

        //institution_staff_leave[START]

        /*if (! in_array('institution_staff_leave', $tableSchemaOne)) {
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
        }*/
        
        // if (in_array('institution_staff_leave', $tableSchema)) {
        //     $table_name = 'institution_staff_leave';
        // }
        // $stmt1 = $connection->prepare("CREATE OR REPLACE VIEW institution_staff_leave_archived AS SELECT * FROM institution_staff_leave");
        // $stmt1->execute();
        // $InstitutionStaffLeaveData->deleteAll(['academic_period_id' => $academicPeriodId]);

        $connection->execute("CREATE TABLE IF NOT EXISTS `institution_staff_leave_archived` (
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
          $connection->execute("INSERT INTO `institution_staff_leave_archived` SELECT * FROM `institution_staff_leave` WHERE academic_period_id = $academicPeriodId");
          $connection->execute("DELETE FROM institution_staff_leave WHERE academic_period_id = $academicPeriodId");
        //institution_staff_leave[END]
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
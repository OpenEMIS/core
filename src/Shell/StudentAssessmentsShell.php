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

class StudentAssessmentsShell extends Shell
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

        ////assessment_item_results[START]

        $Tablecollection = $archive_connection->schemaCollection();
        $tableSchema = $Tablecollection->listTables();

        if (! in_array('assessment_item_results', $tableSchema)) {
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

        $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
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

        $connection->execute("CREATE TABLE IF NOT EXISTS `assessment_item_results_archived` LIKE `assessment_item_results`");
        $connection->execute("INSERT INTO `assessment_item_results_archived` SELECT * FROM `assessment_item_results` WHERE academic_period_id = $academicPeriodId");
        $connection->execute("DELETE FROM assessment_item_results WHERE academic_period_id = $academicPeriodId");
        //assessment_item_results[END]
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
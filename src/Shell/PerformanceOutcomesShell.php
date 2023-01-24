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

class PerformanceOutcomesShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        
        if (!empty($this->args)) {
            $exit = false;           
            
            $fromAcademicPeriod = $this->args[0];
            $toAcademicPeriod = $this->args[1];

            $this->out('Initializing Performance Outcomes ('.Time::now().')');

            $systemProcessId = $this->SystemProcesses->addProcess('PerformanceOutcomes', getmypid(), 'Archive.PerformanceOutcomes', $this->args);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);
            
            // while (!$exit) {
                $recordToProcess = $this->getRecords($fromAcademicPeriod, $toAcademicPeriod);
                $this->out($recordToProcess);
                if ($recordToProcess) {
                    try {
                        $this->out('Dispatching event to for Performance Outcomes');
                        $this->out('End Update for Performance Outcomes ('. Time::now() .')');
                    } catch (\Exception $e) {
                        $this->out('Error in Performance Outcomes');
                        $this->out($e->getMessage());
                        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
                    }
                } else {
                    $this->out('No records to update ('.Time::now().')');
                    $exit = true;
                }
            // }
            $this->out('End Update for Performance Outcomes ('. Time::now() .')');
            $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
        }else{
            $this->out('Error in Performance Outcomes');
        }
    }

    
    public function getRecords($fromAcademicPeriod, $toAcademicPeriod){

        //OutcomeCriterias[START]
        $connection = ConnectionManager::get('default');
        $OutcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');
        $OutcomeTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
        $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
        $OutcomeCriteriasData = $OutcomeCriterias
        ->find('all')
        ->where(['academic_period_id' => $fromAcademicPeriod])
        ->toArray();
        foreach($OutcomeCriteriasData AS $OutcomeCriteriasValue){
            if(isset($OutcomeCriteriasValue['modified'])){
                if ($OutcomeCriteriasValue['modified'] instanceof Time || $OutcomeCriteriasValue['modified'] instanceof Date) {
                    $modified = $OutcomeCriteriasValue['modified']->format('Y-m-d H:i:s');
                }else {
                    $modified = date('Y-m-d H:i:s', strtotime($OutcomeCriteriasValue['modified']));
                }
            }else{
                $modified = date('Y-m-d H:i:s');
            }

            if(isset($OutcomeCriteriasValue['created'])){
                if ($OutcomeCriteriasValue['created'] instanceof Time || $OutcomeCriteriasValue['created'] instanceof Date) {
                    $created = $OutcomeCriteriasValue['created']->format('Y-m-d H:i:s');
                }else {
                    $created = date('Y-m-d H:i:s', strtotime($OutcomeCriteriasValue['created']));
                }
            }else{
                $created = date('Y-m-d H:i:s');
            }
            try{
                $statement = $connection->prepare('INSERT INTO outcome_criterias (
                code, 
                name,
                academic_period_id,
                outcome_template_id,
                education_grade_id,
                education_subject_id,
                outcome_grading_type_id,
                modified_user_id,
                modified,
                created_user_id,
                created)
                
                VALUES (
                :code, 
                :name,
                :academic_period_id,
                :outcome_template_id,
                :education_grade_id,
                :education_subject_id,
                :outcome_grading_type_id,
                :modified_user_id,
                :modified,
                :created_user_id,
                :created)');

                $statement->execute([
                'code' => $OutcomeCriteriasValue["code"],
                'name' => $OutcomeCriteriasValue["name"],
                'academic_period_id' => $toAcademicPeriod,
                'outcome_template_id' => $OutcomeCriteriasValue["outcome_template_id"],
                'education_grade_id' => $OutcomeCriteriasValue["education_grade_id"],
                'education_subject_id' => $OutcomeCriteriasValue["education_subject_id"],
                'outcome_grading_type_id' => $OutcomeCriteriasValue["outcome_grading_type_id"],
                'modified_user_id' => $OutcomeCriteriasValue["modified_user_id"],
                'modified' => $modified,
                'created_user_id' => $OutcomeCriteriasValue["created_user_id"],
                'created' => $created,
                ]);
            
            }catch (PDOException $e) {
                
            }
        }
        $ToAcademicPeriodsData = $AcademicPeriods
        ->find()
        ->select(['start_date', 'start_year','end_date'])
        ->where(['id' => $toAcademicPeriod])
        ->first();
        $from_start_date = $ToAcademicPeriodsData['start_date']->format('Y-m-d');
        $to_end_date = $ToAcademicPeriodsData['end_date']->format('Y-m-d');
        $from_start_date = "'".$from_start_date."'";
        $to_end_date = "'".$to_end_date."'";

        $statement2 = $connection->prepare("SELECT education_systems.academic_period_id,correct_grade.id AS correct_grade_id,institution_grades.* FROM `institution_grades`
        INNER JOIN education_grades wrong_grade ON wrong_grade.id = institution_grades.education_grade_id
        INNER JOIN education_grades correct_grade ON correct_grade.code = wrong_grade.code
        INNER JOIN education_programmes ON correct_grade.education_programme_id = education_programmes.id
        INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
        INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
        INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
        LEFT JOIN academic_periods ON institution_grades.start_date BETWEEN $from_start_date AND $to_end_date
        AND academic_periods.academic_period_level_id != -1
        AND education_systems.academic_period_id = academic_periods.id
        WHERE correct_grade.id != institution_grades.education_grade_id AND academic_periods.id=$toAcademicPeriod");

        $statement2->execute();
        $row = $statement2->fetchAll(\PDO::FETCH_ASSOC);

        foreach($row AS $rowData){
            $OutcomeCriterias->updateAll(
                ['education_grade_id' => $rowData['correct_grade_id']],    //field
                ['academic_period_id' => $toAcademicPeriod]
            );
        }
        //OutcomeCriterias[END]

        //outcome_templates[START]
        $OutcomeTemplatesData = $OutcomeTemplates
        ->find('all')
        ->where(['academic_period_id' => $fromAcademicPeriod])
        ->toArray();

        foreach($OutcomeTemplatesData AS $OutcomeTemplatesValue){
            if(isset($OutcomeTemplatesValue['modified'])){
                if ($OutcomeTemplatesValue['modified'] instanceof Time || $OutcomeTemplatesValue['modified'] instanceof Date) {
                    $modified = $OutcomeTemplatesValue['modified']->format('Y-m-d H:i:s');
                }else {
                    $modified = date('Y-m-d H:i:s', strtotime($OutcomeTemplatesValue['modified']));
                }
            }else{
                $modified = date('Y-m-d H:i:s');
            }

            if(isset($OutcomeTemplatesValue['created'])){
                if ($OutcomeTemplatesValue['created'] instanceof Time || $OutcomeTemplatesValue['created'] instanceof Date) {
                    $created = $OutcomeTemplatesValue['created']->format('Y-m-d H:i:s');
                }else {
                    $created = date('Y-m-d H:i:s', strtotime($OutcomeTemplatesValue['created']));
                }
            }else{
                $created = date('Y-m-d H:i:s');
            }
            try{
                $statement3 = $connection->prepare('INSERT INTO outcome_templates (
                code, 
                name,
                description,
                academic_period_id,
                education_grade_id,
                modified_user_id,
                modified,
                created_user_id,
                created)
                
                VALUES (
                :code, 
                :name,
                :description,
                :academic_period_id,
                :education_grade_id,
                :modified_user_id,
                :modified,
                :created_user_id,
                :created)');

                $statement3->execute([
                'code' => $OutcomeTemplatesValue["code"],
                'name' => $OutcomeTemplatesValue["name"],
                'description' => $OutcomeTemplatesValue["description"],
                'academic_period_id' => $toAcademicPeriod,
                'education_grade_id' => $OutcomeTemplatesValue["education_grade_id"],
                'modified_user_id' => $OutcomeTemplatesValue["modified_user_id"],
                'modified' => $modified,
                'created_user_id' => $OutcomeTemplatesValue["created_user_id"],
                'created' => $created,
                ]);
            
            }catch (PDOException $e) {
                echo "<pre>";print_r($e);die;
            }
        }

        foreach($row AS $rowData){
            $OutcomeTemplates->updateAll(
                ['education_grade_id' => $rowData['correct_grade_id']],    //field
                ['academic_period_id' => $toAcademicPeriod]
            );
        }

        //outcome_templates[END]
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
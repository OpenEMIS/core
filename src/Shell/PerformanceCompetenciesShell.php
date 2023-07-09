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

class PerformanceCompetenciesShell extends Shell
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
            $competency_criterias_value = $this->args[2];
            $competency_templates_value = $this->args[3];
            $competency_items_value = $this->args[4];

            $this->out('Initializing Performance Competencies ('.Time::now().')');

            $systemProcessId = $this->SystemProcesses->addProcess('PerformanceCompetencies', getmypid(), 'Archive.PerformanceCompetencies', $this->args);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);
            
            // while (!$exit) {
                $recordToProcess = $this->getRecords($fromAcademicPeriod, $toAcademicPeriod, $competency_criterias_value, $competency_templates_value, $competency_items_value);
                $this->out($recordToProcess);
                if ($recordToProcess) {
                    try {
                        $this->out('Dispatching event to for Performance Competencies');
                        $this->out('End Update for Performance Competencies ('. Time::now() .')');
                    } catch (\Exception $e) {
                        $this->out('Error in Performance Competencies');
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

    
    public function getRecords($fromAcademicPeriod, $toAcademicPeriod, $competency_criterias_value, $competency_templates_value, $competency_items_value){

        $connection = ConnectionManager::get('default');
        $CompetencyCriteriasTable = TableRegistry::get('Competency.CompetencyCriterias');
        $CompetencyTemplatesTable = TableRegistry::get('Competency.CompetencyTemplates');
        $CompetencyItemsTable = TableRegistry::get('Competency.CompetencyItems');
        $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
        
        //CompetencyTemplates[START]
        if(isset($competency_templates_value) && $competency_templates_value == 0){
            $CompetencyTemplatesData = $CompetencyTemplatesTable
            ->find('all')
            ->where(['academic_period_id' => $fromAcademicPeriod])
            ->toArray();

            foreach($CompetencyTemplatesData AS $CompetencyTemplatesValue){
                if(isset($CompetencyTemplatesValue['modified'])){
                    if ($CompetencyTemplatesValue['modified'] instanceof Time || $CompetencyTemplatesValue['modified'] instanceof Date) {
                        $modified = $CompetencyTemplatesValue['modified']->format('Y-m-d H:i:s');
                    }else {
                        $modified = date('Y-m-d H:i:s', strtotime($CompetencyTemplatesValue['modified']));
                    }
                }else{
                    $modified = date('Y-m-d H:i:s');
                }

                if(isset($CompetencyTemplatesValue['created'])){
                    if ($CompetencyTemplatesValue['created'] instanceof Time || $CompetencyTemplatesValue['created'] instanceof Date) {
                        $created = $CompetencyTemplatesValue['created']->format('Y-m-d H:i:s');
                    }else {
                        $created = date('Y-m-d H:i:s', strtotime($CompetencyTemplatesValue['created']));
                    }
                }else{
                    $created = date('Y-m-d H:i:s');
                }
                try{
                    $statement2 = $connection->prepare('INSERT INTO competency_templates (
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

                    $statement2->execute([
                    'code' => $CompetencyTemplatesValue["code"],
                    'name' => $CompetencyTemplatesValue["name"],
                    'description' => $CompetencyTemplatesValue["description"],
                    'academic_period_id' => $toAcademicPeriod,
                    'education_grade_id' => $CompetencyTemplatesValue["education_grade_id"],
                    'modified_user_id' => $CompetencyTemplatesValue["modified_user_id"],
                    'modified' => $modified,
                    'created_user_id' => $CompetencyTemplatesValue["created_user_id"],
                    'created' => $created,
                    ]);
                
                }catch (PDOException $e) {
                    echo "<pre>";print_r($e);die;
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

            $statement3 = $connection->prepare("SELECT education_systems.academic_period_id,correct_grade.id AS correct_grade_id,institution_grades.* FROM `institution_grades`
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

            $statement3->execute();
            $row = $statement3->fetchAll(\PDO::FETCH_ASSOC);
            if(!empty($row)){
                foreach($row AS $rowData){
                    $CompetencyTemplatesTable->updateAll(
                        ['education_grade_id' => $rowData['correct_grade_id']],    //field
                        ['education_grade_id' => $rowData['education_grade_id'], 'academic_period_id' => $toAcademicPeriod]
                    );
                }
            }
        }

        //CompetencyTemplates[END]

        $CompetencyTemplateData = $CompetencyTemplatesTable
            ->find('all')
            ->where(['academic_period_id' => $toAcademicPeriod])
            ->toArray();
            $arr = [];
            foreach($CompetencyTemplateData as $val){
                $arr[] = $val['id'];
            }
        $CompetencyItemsData = $CompetencyItemsTable
        ->find('all')
        ->where(['academic_period_id' => $toAcademicPeriod])
        ->toArray();
        $arr1 = [];
        foreach($CompetencyItemsData as $val){
            $arr1[] = $val['id'];
        }

        //competencyItems[STARTS]
        if(isset($competency_items_value) && $competency_items_value == 0){
            $CompetencyItemsTable = TableRegistry::get('Competency.CompetencyItems');
            $CompetencyItemsData = $CompetencyItemsTable
            ->find('all')
            ->where(['academic_period_id' => $fromAcademicPeriod])
            ->toArray();

            foreach($CompetencyItemsData AS $key => $CompetencyItemsValue){
                if(isset($CompetencyItemsValue['modified'])){
                    if ($CompetencyItemsValue['modified'] instanceof Time || $CompetencyItemsValue['modified'] instanceof Date) {
                        $modified = $CompetencyItemsValue['modified']->format('Y-m-d H:i:s');
                    }else {
                        $modified = date('Y-m-d H:i:s', strtotime($CompetencyItemsValue['modified']));
                    }
                }else{
                    $modified = date('Y-m-d H:i:s');
                }

                if(isset($CompetencyItemsValue['created'])){
                    if ($CompetencyItemsValue['created'] instanceof Time || $CompetencyItemsValue['created'] instanceof Date) {
                        $created = $CompetencyItemsValue['created']->format('Y-m-d H:i:s');
                    }else {
                        $created = date('Y-m-d H:i:s', strtotime($CompetencyItemsValue['created']));
                    }
                }else{
                    $created = date('Y-m-d H:i:s');
                }
                try{
                    $statement4 = $connection->prepare('INSERT INTO competency_items (
                    name,
                    academic_period_id,
                    competency_template_id,
                    modified_user_id,
                    modified,
                    created_user_id,
                    created)
                    
                    VALUES (
                    :name,
                    :academic_period_id,
                    :competency_template_id,
                    :modified_user_id,
                    :modified,
                    :created_user_id,
                    :created)');

                    $statement4->execute([
                    'name' => $CompetencyItemsValue["name"],
                    'academic_period_id' => $toAcademicPeriod,
                    'competency_template_id' => $arr[$key],
                    'modified_user_id' => $CompetencyItemsValue["modified_user_id"],
                    'modified' => $modified,
                    'created_user_id' => $CompetencyItemsValue["created_user_id"],
                    'created' => $created,
                    ]);
                
                }catch (PDOException $e) {
                    echo "<pre>";print_r($e);die;
                }
            }
        }
        //competencyItems[END]

        //competencycriterias[start]
        if(isset($competency_criterias_value) && $competency_criterias_value == 0){
            $CompetencyCriteriasData = $CompetencyCriteriasTable
            ->find('all')
            ->where(['academic_period_id' => $fromAcademicPeriod])
            ->toArray();
            foreach($CompetencyCriteriasData AS $key => $CompetencyCriteriasValue){
                if(isset($CompetencyCriteriasValue['modified'])){
                    if ($CompetencyCriteriasValue['modified'] instanceof Time || $CompetencyCriteriasValue['modified'] instanceof Date) {
                        $modified = $CompetencyCriteriasValue['modified']->format('Y-m-d H:i:s');
                    }else {
                        $modified = date('Y-m-d H:i:s', strtotime($CompetencyCriteriasValue['modified']));
                    }
                }else{
                    $modified = date('Y-m-d H:i:s');
                }

                if(isset($CompetencyCriteriasValue['created'])){
                    if ($CompetencyCriteriasValue['created'] instanceof Time || $CompetencyCriteriasValue['created'] instanceof Date) {
                        $created = $CompetencyCriteriasValue['created']->format('Y-m-d H:i:s');
                    }else {
                        $created = date('Y-m-d H:i:s', strtotime($CompetencyCriteriasValue['created']));
                    }
                }else{
                    $created = date('Y-m-d H:i:s');
                }
                try{
                    $statement = $connection->prepare('INSERT INTO competency_criterias (
                    code, 
                    name,
                    academic_period_id,
                    competency_item_id,
                    competency_template_id,
                    competency_grading_type_id,
                    modified_user_id,
                    modified,
                    created_user_id,
                    created)
                    
                    VALUES (
                    :code, 
                    :name,
                    :academic_period_id,
                    :competency_item_id,
                    :competency_template_id,
                    :competency_grading_type_id,
                    :modified_user_id,
                    :modified,
                    :created_user_id,
                    :created)');

                    $statement->execute([
                    'code' => $CompetencyCriteriasValue["code"],
                    'name' => $CompetencyCriteriasValue["name"],
                    'academic_period_id' => $toAcademicPeriod,
                    'competency_item_id' => $CompetencyCriteriasValue["competency_item_id"],
                    //'competency_item_id' => $arr1[$key],
                    'competency_template_id' => $CompetencyCriteriasValue["competency_template_id"],
                  //  'competency_template_id' => $arr[$key],
                    'competency_grading_type_id' => $CompetencyCriteriasValue["competency_grading_type_id"],
                    'modified_user_id' => $CompetencyCriteriasValue["modified_user_id"],
                    'modified' => $modified,
                    'created_user_id' => $CompetencyCriteriasValue["created_user_id"],
                    'created' => $created,
                    ]);
                
                }catch (PDOException $e) {
                    
                }
            }

            $templateId = $CompetencyTemplatesTable
                ->find('all')
                ->where(['academic_period_id' => $toAcademicPeriod])
                ->first()->id;
            $updateCriterias =  $CompetencyCriteriasTable->updateAll(
                                ['competency_template_id' => $templateId,],    //field
                                [
                                 'academic_period_id' => $toAcademicPeriod, 
                                ] //condition
                                );
             $itemId = $CompetencyItemsTable
                ->find('all')
                ->where(['academic_period_id' => $toAcademicPeriod])
                ->first()->id;
            $updateCriterias =  $CompetencyCriteriasTable->updateAll(
                                ['competency_item_id' => $itemId,],    //field
                                [
                                 'academic_period_id' => $toAcademicPeriod, 
                                ] //condition
                                );
        }
        
       
        //CompetencyCriterias[END] 

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
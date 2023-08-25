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
            //POCOR-7670 start (for updating education_grades in competency_template table)
            $statement3 = $connection->prepare("Select subq1.grade_id as wrong_grade,subq2.grade_id as correct_grade from
                        (SELECT academic_periods.id period_id,academic_periods.name period_name,academic_periods.code period_code,education_grades.id grade_id, education_grades.name grade_name, education_programmes.name programme_name FROM education_grades
                        INNER JOIN education_programmes ON education_grades.education_programme_id = education_programmes.id
                        INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
                        INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
                        INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
                        INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
                        where academic_period_id=$fromAcademicPeriod
                        ORDER BY academic_periods.order ASC,education_levels.order ASC,education_cycles.order ASC,education_programmes.order ASC,education_grades.order ASC)subq1
                        inner join
                        (SELECT academic_periods.id period_id,academic_periods.name period_name,academic_periods.code period_code,education_grades.id grade_id, education_grades.name grade_name, education_programmes.name programme_name FROM education_grades
                        INNER JOIN education_programmes ON education_grades.education_programme_id = education_programmes.id
                        INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
                        INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
                        INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
                        INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
                        where academic_period_id=$toAcademicPeriod
                        ORDER BY academic_periods.order ASC,education_levels.order ASC,education_cycles.order ASC,education_programmes.order ASC,education_grades.order ASC)subq2
                        on subq1.grade_name=subq2.grade_name");
            //POCOR-7670 end
            $statement3->execute();
            $row = $statement3->fetchAll(\PDO::FETCH_ASSOC);
            if(!empty($row)){
                foreach($row AS $rowData){
                    $CompetencyTemplatesTable->updateAll(
                        ['education_grade_id' => $rowData['correct_grade']],    //field
                        ['education_grade_id' => $rowData['wrong_grade'], 'academic_period_id' => $toAcademicPeriod]
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
        //POCOR-7670 start
        $PreviousCompetencyTemplateData = $CompetencyTemplatesTable
            ->find('all')
            ->where(['academic_period_id' => $fromAcademicPeriod])
            ->toArray();
        foreach($PreviousCompetencyTemplateData as $val) {
            $prev_arr[] = $val['id'];
        }
        $i=0;
        foreach ($CompetencyTemplateData as $val) {
            $arr[$prev_arr[$i]] = $val['id'];
            $i++;
        }
         //POCOR-7670 end
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
            foreach($arr as $key=>$value){// //POCOR-7670 
            $CompetencyItemsData = $CompetencyItemsTable
            ->find()
            ->where(['academic_period_id' => $fromAcademicPeriod,
                    'competency_template_id' => $key]  //POCOR-7670 
            )
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
                    'competency_template_id' => $value,
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
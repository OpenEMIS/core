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

            $this->out('Initializing Performance Outcomes (' . Time::now() . ')');

            $systemProcessId = $this->SystemProcesses->addProcess('PerformanceOutcomes', getmypid(), 'Archive.PerformanceOutcomes', $this->args);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);

            // while (!$exit) {
            $recordToProcess = $this->getRecords($fromAcademicPeriod, $toAcademicPeriod);
            $this->out($recordToProcess);
            if ($recordToProcess) {
                try {
                    $this->out('Dispatching event to for Performance Outcomes');
                    $this->out('End Update for Performance Outcomes (' . Time::now() . ')');
                } catch (\Exception $e) {
                    $this->out('Error in Performance Outcomes');
                    $this->out($e->getMessage());
                    $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
                }
            } else {
                $this->out('No records to update (' . Time::now() . ')');
                $exit = true;
            }
            // }
            $this->out('End Update for Performance Outcomes (' . Time::now() . ')');
            $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
        } else {
            $this->out('Error in Performance Outcomes');
        }
    }


    public function getRecords($fromAcademicPeriod, $toAcademicPeriod)
    {
        //Updated Education Id Start
        $connection = ConnectionManager::get('default');
        $OutcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');
        $OutcomeTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
        $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
        $statement1 = $connection->prepare("Select subq1.grade_id as wrong_grade_id,subq1.grade_name,subq1.period_name,subq1.programme_name ,  subq2.grade_id as correct_grade_id,subq2.grade_name ,subq2.period_name,subq2.programme_name from
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
                            on subq1.grade_name=subq2.grade_name and subq1.programme_name=subq2.programme_name;
                ");

        $statement1->execute();
        $row = $statement1->fetchAll(\PDO::FETCH_ASSOC);
        $data = [];
        foreach ($row as $rowData) {
            $data[$rowData['wrong_grade_id']] =  $rowData['correct_grade_id'];
        }
        //Updated Education Id End
       
        //outcome_templates[START]
        $OutcomeTemplatesData = $OutcomeTemplates
            ->find('all')
            ->where(['academic_period_id' => $fromAcademicPeriod])
            ->toArray();

        foreach ($OutcomeTemplatesData as $OutcomeTemplatesValue) {
            if (isset($OutcomeTemplatesValue['modified'])) {
                if ($OutcomeTemplatesValue['modified'] instanceof Time || $OutcomeTemplatesValue['modified'] instanceof Date) {
                    $modified = $OutcomeTemplatesValue['modified']->format('Y-m-d H:i:s');
                } else {
                    $modified = date('Y-m-d H:i:s', strtotime($OutcomeTemplatesValue['modified']));
                }
            } else {
                $modified = date('Y-m-d H:i:s');
            }

            if (isset($OutcomeTemplatesValue['created'])) {
                if ($OutcomeTemplatesValue['created'] instanceof Time || $OutcomeTemplatesValue['created'] instanceof Date) {
                    $created = $OutcomeTemplatesValue['created']->format('Y-m-d H:i:s');
                } else {
                    $created = date('Y-m-d H:i:s', strtotime($OutcomeTemplatesValue['created']));
                }
            } else {
                $created = date('Y-m-d H:i:s');
            }
            $newOutcomeTemplateId="";
            try {
                //to check if record already exist
                $statementa = $connection->prepare('SELECT id FROM outcome_templates WHERE
                            code = :code AND
                            name = :name AND
                            academic_period_id = :academic_period_id AND
                            education_grade_id = :education_grade_id');

                $statementa->execute([
                    'code' => $OutcomeTemplatesValue["code"],
                    'name' => $OutcomeTemplatesValue["name"],
                    'academic_period_id' => $toAcademicPeriod,
                    'education_grade_id' => $data[$OutcomeTemplatesValue["education_grade_id"]]
                ]);
                if ($statementa->rowCount() == 0) {
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
                        'education_grade_id' => $data[$OutcomeTemplatesValue["education_grade_id"]],
                        'modified_user_id' => $OutcomeTemplatesValue["modified_user_id"],
                        'modified' => $modified,
                        'created_user_id' => $OutcomeTemplatesValue["created_user_id"],
                        'created' => $created,
                    ]);
                    $newOutcomeTemplateId = $connection->execute('SELECT LAST_INSERT_ID()')->fetch('assoc')['LAST_INSERT_ID()'];
                }
                else{
                    $result = $statementa->fetch(\PDO::FETCH_ASSOC);
                    $newOutcomeTemplateId = $result['id'];
                }
            } catch (PDOException $e) {
                echo "<pre>";
                print_r($e);
                die;
            }
        
           
            //outcome_criteria[start]
            $OutcomeCriteriasData = $OutcomeCriterias
                ->find('all')
                ->where(['academic_period_id' => $fromAcademicPeriod, 'outcome_template_id' => $OutcomeTemplatesValue["id"]])
                ->toArray();

            foreach ($OutcomeCriteriasData as $key => $OutcomeCriteriasValue) {
                if (isset($OutcomeCriteriasValue['modified'])) {
                    if ($OutcomeCriteriasValue['modified'] instanceof Time || $OutcomeCriteriasValue['modified'] instanceof Date) {
                        $modified = $OutcomeCriteriasValue['modified']->format('Y-m-d H:i:s');
                    } else {
                        $modified = date('Y-m-d H:i:s', strtotime($OutcomeCriteriasValue['modified']));
                    }
                } else {
                    $modified = date('Y-m-d H:i:s');
                }

                if (isset($OutcomeCriteriasValue['created'])) {
                    if ($OutcomeCriteriasValue['created'] instanceof Time || $OutcomeCriteriasValue['created'] instanceof Date) {
                        $created = $OutcomeCriteriasValue['created']->format('Y-m-d H:i:s');
                    } else {
                        $created = date('Y-m-d H:i:s', strtotime($OutcomeCriteriasValue['created']));
                    }
                } else {
                    $created = date('Y-m-d H:i:s');
                }
                try {
                    $statementb = $connection->prepare('SELECT id FROM outcome_criterias WHERE
                            code = :code AND
                            name = :name AND
                            academic_period_id = :academic_period_id AND
                            education_grade_id = :education_grade_id AND 
                            education_subject_id = :education_subject_id AND
                            outcome_template_id = :outcome_template_id ');

                    $statementb->execute([
                        'code' => $OutcomeTemplatesValue["code"],
                        'name' => $OutcomeTemplatesValue["name"],
                        'academic_period_id' => $toAcademicPeriod,
                        'education_grade_id' => $data[$OutcomeCriteriasValue["education_grade_id"]],
                        'education_subject_id' => $OutcomeCriteriasValue["education_subject_id"],
                        'outcome_template_id' => $newOutcomeTemplateId
                    ]);
                    if ($statementb->rowCount() == 0) {
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
                            // 'outcome_template_id' => $OutcomeCriteriasValue["outcome_template_id"],
                            'outcome_template_id' => $newOutcomeTemplateId,
                            'education_grade_id' => $data[$OutcomeCriteriasValue["education_grade_id"]],
                            'education_subject_id' => $OutcomeCriteriasValue["education_subject_id"],
                            'outcome_grading_type_id' => $OutcomeCriteriasValue["outcome_grading_type_id"],
                            'modified_user_id' => $OutcomeCriteriasValue["modified_user_id"],
                            'modified' => $modified,
                            'created_user_id' => $OutcomeCriteriasValue["created_user_id"],
                            'created' => $created,
                        ]);
                    }
                } catch (PDOException $e) {
                    echo "<pre>";
                    print_R($e->getMessage());
                    exit;
                }
            }
            //outcome_criteria[END]
        }
        //outcome_templates[END]
          return true;
    }

    public function decrypt($encrypted_string, $secretHash)
    {

        $iv = substr($secretHash, 0, 16);
        $data = base64_decode($encrypted_string);
        $decryptedMessage = openssl_decrypt($data, "AES-256-CBC", $secretHash, $raw_input = false, $iv);
        $decrypted = rtrim(
            $decryptedMessage
        );
        return $decrypted;
    }
}

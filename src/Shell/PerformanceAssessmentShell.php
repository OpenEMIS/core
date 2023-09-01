<?php
namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;
use Cake\Utility\Text;

class PerformanceAssessmentShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Start Performance Assessment Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];

        $canCopy = $this->checkIfCanCopy($copyTo);
        if ($canCopy) {
            $this->copyProcess($copyFrom, $copyTo);
        }
        $this->out('End Performance Assessment Shell');
    }

    private function checkIfCanCopy($copyTo)
    {
        $canCopy = false;

        $AssessmentTable = TableRegistry::get('Assessment.Assessments');
        $count = $AssessmentTable->find()->where([$AssessmentTable->aliasField('academic_period_id') => $copyTo])->count();
        // can copy if no assessment created in current acedemic period before
        if ($count == 0) {
            $canCopy = true;
        }

        return $canCopy;
    }

    private function copyProcess($copyFrom, $copyTo)
    {
        try {
            $AssessmentTable = TableRegistry::get('Assessment.Assessments');
            $AssessmentItemsTable = TableRegistry::get('assessment_items');
            $AssessmentPeriodsTable = TableRegistry::get('Assessment.AssessmentPeriods');
            $connection = ConnectionManager::get('default'); 
            //POCOR-7723 start
            $assessment_res = $AssessmentTable->find()->where([$AssessmentTable->aliasField('academic_period_id')=>$copyFrom])->toArray();
            foreach($assessment_res as $key=>$assessmentData){   
                //Copy Assessment [Start]
                $statement1= $connection->prepare('INSERT INTO assessments(code, name, description,
                            excel_template_name, excel_template, type, academic_period_id, education_grade_id,
                            assessment_grading_type_id, modified_user_id, modified, created_user_id, created)
                            VALUES (:code, :name, :description, :excel_template_name, :excel_template, :type, :academic_period_id, :education_grade_id,
                            :assessment_grading_type_id, :modified_user_id, :modified, :created_user_id, :created)');

                $statement1->execute(array(
                                            'code' => $assessmentData->code,
                                            'name'=> $assessmentData->name,
                                            'description' => $assessmentData->description,
                                            'excel_template_name' => $assessmentData->excel_template_name,
                                            'excel_template' => $assessmentData->excel_template,
                                            'type' => $assessmentData->type,
                                            'academic_period_id'=>$copyTo,
                                            'education_grade_id' => $assessmentData->education_grade_id,
                                            'assessment_grading_type_id' => $assessmentData->assessment_grading_type_id,
                                            'modified_user_id' => $assessmentData->modified_user_id,
                                            'modified' => $assessmentData->modified,
                                            'created_user_id' => $assessmentData->created_user_id,
                                            'created'=> $assessmentData->created));
                $newAssessmentId = $connection->execute('SELECT LAST_INSERT_ID()')->fetch('assoc')['LAST_INSERT_ID()'];                         
                if($newAssessmentId!=0){
                    $assessment_item_result = $AssessmentItemsTable->find()
                                                  ->where([$AssessmentItemsTable->aliasField('assessment_id') => $assessmentData->id])
                                                  ->toArray();
                   
                    //Copy Assessment Item[Start]
                    if(!empty($assessment_item_result)){
                        foreach ($assessment_item_result as $key => $assessmentItemData) {
                            $statement2 = $connection->prepare('INSERT INTO assessment_items(id, weight, classification, 
                                    assessment_id, education_subject_id, modified_user_id, modified, created_user_id, created)
                                    VALUES (:id, :weight, :classification, :assessment_id, :education_subject_id, :modified_user_id, :modified, :created_user_id, :created)');
                                    
                            $statement2->execute(array(
                                            'id'=> Text::uuid(),
                                            'weight' => $assessmentItemData->weight,
                                            'classification'=> $assessmentItemData->classification,
                                            'assessment_id' => $newAssessmentId,
                                            'education_subject_id' => $assessmentItemData->education_subject_id,
                                            'modified_user_id' => $assessmentItemData->modified_user_id,
                                            'modified' => $assessmentItemData->modified,
                                            'created_user_id' => $assessmentItemData->created_user_id,
                                            'created'=> $assessmentItemData->created));
                        }
                    }
                    //Copy Assessment Item[End]
                    $assessment_period_result = $AssessmentPeriodsTable->find()
                                                ->where([$AssessmentPeriodsTable->aliasField('assessment_id') => $assessmentData->id])
                                                ->toArray();
                    //Copy Assessment Period[Start]
                    if (!empty($assessment_period_result)) {
                        foreach ($assessment_period_result as $key => $assessmentPeriodData) {
                            $statement3=$connection->prepare('INSERT INTO assessment_periods(code, name, 
                                    start_date, end_date, date_enabled, date_disabled, weight, academic_term, assessment_id,
                                    editable_student_statuses, modified_user_id, modified, created_user_id, created)
                                    VALUES (:code, :name, :start_date, :end_date, :date_enabled, :date_disabled, :weight, 
                                    :academic_term, :assessment_id, :editable_student_statuses,:modified_user_id, :modified, :created_user_id, :created)');

                            $statement3->execute(['code' => $assessmentPeriodData->code,
                                    'name' =>  $assessmentPeriodData->name,
                                    'start_date' =>  $assessmentPeriodData->start_date,
                                    'end_date' =>  $assessmentPeriodData->end_date,
                                    'date_enabled' =>  $assessmentPeriodData->date_enabled,
                                    'date_disabled' =>  $assessmentPeriodData->date_disabled,
                                    'weight' =>  $assessmentPeriodData->weight,
                                    'academic_term' =>  $assessmentPeriodData->academic_term,
                                    'assessment_id' => $newAssessmentId,
                                    'editable_student_statuses' =>  $assessmentPeriodData->editable_student_statuses,
                                    'modified_user_id' =>  $assessmentPeriodData->modified_user_id,
                                    'modified' =>  $assessmentPeriodData->modified,
                                    'created_user_id' =>  $assessmentPeriodData->created_user_id,
                                    'created' =>  $assessmentPeriodData->created
                                ]);
                        }
                    }
                    //Copy Assessment End[Start]
                }
            }
            //Copy Assessment [End]
             //POCOR-7723 end
            //To update latest education id POCOR-6423
            $statementLast = $connection->prepare("Select subq1.grade_id as wrong_grade,subq2.grade_id as correct_grade from
                            (SELECT academic_periods.id period_id,academic_periods.name period_name,academic_periods.code period_code,education_grades.id grade_id, education_grades.name grade_name, education_programmes.name programme_name FROM education_grades
                            INNER JOIN education_programmes ON education_grades.education_programme_id = education_programmes.id
                            INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
                            INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
                            INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
                            INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
                            where academic_period_id=$copyFrom
                            ORDER BY academic_periods.order ASC,education_levels.order ASC,education_cycles.order ASC,education_programmes.order ASC,education_grades.order ASC)subq1
                            inner join
                            (SELECT academic_periods.id period_id,academic_periods.name period_name,academic_periods.code period_code,education_grades.id grade_id, education_grades.name grade_name, education_programmes.name programme_name FROM education_grades
                            INNER JOIN education_programmes ON education_grades.education_programme_id = education_programmes.id
                            INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
                            INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
                            INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
                            INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
                            where academic_period_id=$copyTo
                            ORDER BY academic_periods.order ASC,education_levels.order ASC,education_cycles.order ASC,education_programmes.order ASC,education_grades.order ASC)subq2
                            on subq1.grade_name=subq2.grade_name");
            $statementLast->execute();
            $row = $statementLast->fetchAll(\PDO::FETCH_ASSOC);
            if(!empty($row)){
                    foreach($row AS $rowData){
                        $AssessmentTable->updateAll(
                            ['education_grade_id' => $rowData['correct_grade']],    //field
                            ['education_grade_id' => $rowData['wrong_grade'], 'academic_period_id' => $copyTo]);
                    }
            }
        //education grade updation end
        } catch (\Exception $e) {
            // pr($e->getMessage());
        echo $e;
        exit;
        }
    }
}
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
            $connection = ConnectionManager::get('default');     
            $assessment_res = $connection->execute('SELECT * FROM assessments WHERE academic_period_id="'.$copyFrom.'"');
            $assessment_data = $assessment_res->fetch('assoc');
            $connection->execute("INSERT INTO `assessments` (
                `code`, `name`, `description`, `excel_template_name`, `excel_template`, `type`,
                `academic_period_id`, `education_grade_id`, `created_user_id`, `created`)
                SELECT `code`, `name`, `description`, `excel_template_name`, `excel_template`, `type`, $copyTo, `education_grade_id`, `created_user_id`, NOW()
                FROM `assessments`
                WHERE `academic_period_id` = $copyFrom");

            $last_inserted_id_query = $connection->execute('select last_insert_id() as id;');
            $last_inserted_id = $last_inserted_id_query->fetch('assoc');

            if(isset($last_inserted_id['id']) && $last_inserted_id['id'] > 0){
                $assessment_id = $last_inserted_id['id'];
                $assessment_criteria_res = $connection->execute('SELECT * FROM assessment_items WHERE assessment_id='.$assessment_data['id']);
                $assessment_crieteria_data = $assessment_criteria_res ->fetchAll('assoc');
                if(!empty($assessment_crieteria_data)){
                    foreach($assessment_crieteria_data as $key => $value){
                        $ids = Text::uuid();
                        $weight =  $value['weight'];
                        $classification =  $value['classification'];
                        $education_subject_id =  $value['education_subject_id'];
                        $created_user_id =  $value['created_user_id'];
                        $connection->execute("INSERT INTO assessment_items (`id`,`weight`,`classification`,`assessment_id`,`education_subject_id`,`created_user_id`,`created`) VALUES('".$ids."', $weight, '".$classification."',$assessment_id,$education_subject_id,$created_user_id,NOW())");
    
                    }
                }
            }
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
            pr($e->getMessage());
        }
    }
}
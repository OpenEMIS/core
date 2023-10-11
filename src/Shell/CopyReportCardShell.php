<?php

namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;
use Cake\Utility\Text;

class CopyReportCardShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Start Report Card Copy Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];

        $canCopy = $this->checkIfCanCopy($copyTo);
        if ($canCopy) {
            $this->copyProcess($copyFrom, $copyTo);
        }
        $this->out('End Report Card Copy Shell');
    }
    private function checkIfCanCopy($copyTo)
    {
        $canCopy = false;

        $ReportCard = TableRegistry::get('ReportCard.ReportCards');
        $count = $ReportCard->find()->where([$ReportCard->aliasField('academic_period_id') => $copyTo])->count();
        // can copy if no assessment created in current acedemic period before
        if ($count == 0) {
            $canCopy = true;
        }

        return $canCopy;
    }
    private function copyProcess($copyFrom, $copyTo)
    {
        $ReportCardTable = TableRegistry::get('ReportCard.ReportCards');
        $ReportCardExcludedSecurityRolesTable = TableRegistry::get('ReportCard.ReportCardExcludedSecurityRoles');
        $ReportCardSubjectsTable = TableRegistry::get('ReportCard.ReportCardSubjects');
        $ReportCardData = $ReportCardTable->find()->where([$ReportCardTable->aliasField('academic_period_id') => $copyFrom])->toArray();
        $connection = ConnectionManager::get('default');
        try{
            $statement = $connection->prepare("Select subq1.grade_id as wrong_grade,subq2.grade_id as correct_grade from
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
            $statement->execute();
            $row = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $educationGradeList=[];
            foreach($row as $key=>$value){
                $educationGradeList[$value['wrong_grade']]=$value['correct_grade'];
            }
            
            foreach($ReportCardData as $key=>$ReportCard){
                //Report Card Copy Start
                $statement1 = $connection->prepare('INSERT INTO report_cards(code, name, description,start_date, end_date,generate_start_date,
                                        generate_end_date,principal_comments_required,homeroom_teacher_comments_required,teacher_comments_required,
                                        excel_template_name, excel_template,pdf_page_number,academic_period_id,education_grade_id,modified_user_id,
                                        modified, created_user_id, created)
                                        VALUES (:code, :name, :description,:start_date,:end_date,:generate_start_date,:generate_end_date,
                                        :principal_comments_required,:homeroom_teacher_comments_required,:teacher_comments_required,:excel_template_name, 
                                        :excel_template,:pdf_page_number,:academic_period_id,:education_grade_id,:modified_user_id,:modified,
                                        :created_user_id, :created)');

                $statement1->execute(array("code" => $ReportCard->code,
                                "name"=> $ReportCard->name,
                                "description"=> $ReportCard->description,
                                "start_date"=>  date("Y-m-d", strtotime($ReportCard->start_date)),
                                "end_date"=>  date("Y-m-d ", strtotime($ReportCard->end_date)),
                                "generate_start_date"=> date("Y-m-d H:i:s", strtotime($ReportCard->generate_start_date)),
                                "generate_end_date"=> date("Y-m-d H:i:s", strtotime($ReportCard->generate_end_date)),
                                "principal_comments_required"=> $ReportCard->principal_comments_required,
                                "homeroom_teacher_comments_required"=> $ReportCard->homeroom_teacher_comments_required,
                                "teacher_comments_required"=> $ReportCard->teacher_comments_required,
                                "excel_template_name"=> $ReportCard->excel_template_name,
                                "excel_template"=>$ReportCard->excel_template,
                                "pdf_page_number"=>$ReportCard->pdf_page_number,
                                "academic_period_id"=>$copyTo,
                                "education_grade_id"=> $educationGradeList[$ReportCard->education_grade_id],
                                "modified_user_id"=>$ReportCard->modified_user_id,
                                "modified" => date("Y-m-d H:i:s", strtotime($ReportCard->modified)),
                                "created_user_id"=>$ReportCard->created_user_id,
                                "created" => date("Y-m-d H:i:s", strtotime($ReportCard->created))
                ));

                $newReportCardId = $connection->execute('SELECT LAST_INSERT_ID()')->fetch('assoc')['LAST_INSERT_ID()'];

                if($newReportCardId != 0) {
                    //Report Card Excluded Security Roles Copy Start 
                    $ExistingSecurityRoleData = $ReportCardExcludedSecurityRolesTable->find()
                                            ->where([$ReportCardExcludedSecurityRolesTable->aliasField('report_card_id') => $ReportCard->id])
                                            ->toArray();
                    if(!empty($ExistingSecurityRoleData)){
                        foreach($ExistingSecurityRoleData as $security_role_key=>$SecurityRoleData){
                            $statement2 = $connection->prepare('INSERT INTO report_card_excluded_security_roles(report_card_id, security_role_id) 
                                                            VALUES (:report_card_id,:security_role_id)');
                            $statement2->execute(array(
                                "report_card_id" => $newReportCardId,
                                "security_role_id" => $SecurityRoleData->security_role_id,
                            ));
                        }
                    }
                    //Report Card Excluded Security Roles Copy End

                    //Report Card Subjects Start
                    $ExistingSubjectData = $ReportCardSubjectsTable->find()
                                            ->where([$ReportCardSubjectsTable->aliasField('report_card_id') => $ReportCard->id])
                                            ->toArray();
                    if(!empty($ExistingSubjectData)){
                        foreach ($ExistingSubjectData as $subject_key => $SubjectData) {
                            $statement3 = $connection->prepare('INSERT INTO report_card_subjects(id,report_card_id,education_subject_id,education_grade_id,
                                                            created_user_id,created) VALUES (:id,:report_card_id,:education_subject_id,:education_grade_id,
                                                            :created_user_id,:created)');
                            $statement3->execute(array(
                                "id" => Text::uuid(),
                                "report_card_id" => $newReportCardId,
                                "education_subject_id" => $SubjectData->education_subject_id,
                                "education_grade_id" => $educationGradeList[$SubjectData->education_grade_id],
                                "created_user_id" => $SubjectData->created_user_id,
                                "created" => date("Y-m-d H:i:s", strtotime($SubjectData->created)),
                            ));
                        } 
                    }
                    //Report Card Subjects End
                }
                //Report Card Copy End
            }
            
        } catch (\Exception $e) {
            $error = $e->getMessage();
            pr($error);
        }
    }
}
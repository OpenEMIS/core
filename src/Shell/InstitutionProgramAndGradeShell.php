<?php

namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;
use Cake\Utility\Text;

class InstitutionProgramAndGradeShell extends Shell
{

    public function initialize()
    {
        parent::initialize();
    }
    public function main()
    {
        $this->out('Start Institution Program, Grades and Subject Copy Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];
        $this->copyProcess($copyFrom, $copyTo);
        $this->out('End Institution Program, Grades and Subject Copy Shell');
    }
    public function copyProcess($copyFrom, $copyTo)
    {
        try {
            //start
            ini_set('memory_limit', '2G');
            $connection = ConnectionManager::get('default');
            $EducationLevels = TableRegistry::get('Education.EducationLevels');
            $EducationCycles = TableRegistry::get('Education.EducationCycles');
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            $Institutions = TableRegistry::get('Institution.Institutions');
            $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $currentDate = "'" . date('Y-m-d H:i:s') . "'";
            $institutionGradeSubjects = TableRegistry::get('institution_program_grade_subjects');
            $from_academic_period = $copyFrom;
            $to_academic_period = $copyTo;

            $InstitutionGradesdata = $InstitutionGrades->find('all')->toArray();
            $FromAcademicPeriodsData = $AcademicPeriods->find()->select(['start_date', 'start_year', 'id'])
                ->where(['id' => $from_academic_period])
                ->first();
            $ToAcademicPeriodsData = $AcademicPeriods->find()->select(['start_date', 'start_year', 'end_date'])
                ->where(['id' => $to_academic_period])
                ->first();
            $InstitutionGradesdatasToInsert = $InstitutionGrades->find('all')
                ->contain('EducationGrades')
                ->where(['academic_period_id' =>  $from_academic_period])
                ->toArray();
            
            //check if no data exist in new academic period
            $InstitutionGradesdatasAlreadyInserted = $InstitutionGrades->find('all')
                ->where(['academic_period_id' =>  $to_academic_period])
                ->toArray();
            
            if (!empty($InstitutionGradesdatasToInsert)) {
                $data= $this->updateEducationGrade($copyFrom, $copyTo);
             
                //Copy Institution Grade Data start
                foreach ($InstitutionGradesdatasToInsert as $key => $gradeData) {
                    $statementa = $connection->prepare('SELECT id FROM institution_grades WHERE
                            education_grade_id = :education_grade_id AND
                            academic_period_id = :academic_period_id AND
                            institution_id = :institution_id');

                    $statementa->execute([
                        'education_grade_id' => $data[$gradeData['education_grade_id']],
                        'academic_period_id' => $to_academic_period,
                        'institution_id' => $gradeData['institution_id'],
                    ]);
                    if ($statementa->rowCount() == 0) {
                       
                        // If no matching records exist, you can proceed with the INSERT.
                        $statementb = $connection->prepare('INSERT INTO institution_grades( education_grade_id, academic_period_id, 
                                            start_date, start_year, end_date, end_year, institution_id, modified_user_id, 
                                            modified, created_user_id, created) VALUES (:education_grade_id, :academic_period_id,
                                            :start_date,  :start_year, :end_date, :end_year, :institution_id, :modified_user_id,
                                            :modified, :created_user_id, :created)');
                        $statementb->execute([
                            'education_grade_id' => $data[$gradeData['education_grade_id']],
                            'academic_period_id' => $to_academic_period,
                            'start_date' => $ToAcademicPeriodsData['start_date']->format('Y-m-d'),
                            'start_year' => $ToAcademicPeriodsData['start_year'],
                            'end_date' => null,
                            'end_year' => null,
                            'institution_id' => $gradeData['institution_id'],
                            'modified_user_id' => 2,
                            'modified' => date('Y-m-d H:i:s'),
                            'created_user_id' => 2,
                            'created' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
                
                $from_start_date = $ToAcademicPeriodsData['start_date']->format('Y-m-d');
                $to_end_date = $ToAcademicPeriodsData['end_date']->format('Y-m-d');
                $to_start_year = $ToAcademicPeriodsData['start_year'];
                $from_start_date = "'" . $from_start_date . "'";
                $to_end_date = "'" . $to_end_date . "'";
                $final_from_start_date = $ToAcademicPeriodsData['start_date']->format('Y-m-d');
                

                // to insert data in institution_program_grade_subjects[START]
                $queryData = "SELECT subq3.new_inst_grade_id, subq3.new_ed_grade_id, subq2.subject_id, subq2.inst_id, '1', $currentDate
                            FROM (SELECT
                                    institutions.id institution_id,
                                    education_grades.id edu_grade_id,
                                    institution_grades.id old_institution_grade_id,
                                    institution_program_grade_subjects.institution_grade_id old_instit_grade_id,
                                    institution_program_grade_subjects.education_grade_subject_id subject_id,
                                    institution_program_grade_subjects.institution_id inst_id
                                    FROM institution_program_grade_subjects
                                    INNER JOIN institution_grades ON institution_grades.id = institution_program_grade_subjects.institution_grade_id
                                    INNER JOIN education_grades ON education_grades.id = institution_grades.education_grade_id
                                    INNER JOIN institutions ON institutions.id = institution_grades.institution_id
                                    INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id
                                    INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id
                                    INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id
                                    INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id
                                    INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
                                    WHERE academic_periods.id = $from_academic_period) subq2
                                    INNER JOIN (SELECT 
                                    subq.old_edu_grade_id old_ed_grade_id,
                                    subq1.new_edu_grade_id new_ed_grade_id,
                                    subq.old_institution_grade_id old_inst_grade_id,
                                    subq1.new_institution_grade_id new_inst_grade_id
                                            FROM(SELECT
                                                education_levels.name old_edu_level_name,
                                                education_cycles.name old_edu_cycle_name,
                                                education_programmes.code old_edu_programme_name,
                                                education_grades.id old_edu_grade_id,
                                                education_grades.code old_edu_grade_code,
                                                institution_grades.id old_institution_grade_id,
                                                institution_grades.institution_id old_institution_id
                                                FROM `institution_grades`
                                    INNER JOIN education_grades ON education_grades.id = institution_grades.education_grade_id
                                    INNER JOIN institutions ON institutions.id = institution_grades.institution_id
                                    INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id
                                    INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id
                                    INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id
                                    INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id
                                    INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
                                    WHERE academic_periods.id = $from_academic_period) subq
                                    INNER JOIN (SELECT 
                                                education_levels.name new_edu_level_name,
                                                education_cycles.name new_edu_cycle_name,
                                                education_programmes.code new_edu_programme_name,
                                                education_grades.id new_edu_grade_id,
                                                education_grades.code new_edu_grade_code,
                                                institution_grades.id new_institution_grade_id,
                                                institution_grades.institution_id new_institution_id
                                    FROM `institution_grades`
                                    INNER JOIN education_grades ON education_grades.id = institution_grades.education_grade_id
                                    INNER JOIN institutions ON institutions.id = institution_grades.institution_id
                                    INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id
                                    INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id
                                    INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id
                                    INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id
                                    INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
                                    WHERE academic_periods.id = $to_academic_period) subq1
                                    ON subq1.new_edu_level_name = subq.old_edu_level_name 
                                    AND subq1.new_edu_programme_name = subq.old_edu_programme_name
                                    AND subq1.new_edu_grade_code = subq.old_edu_grade_code 
                                    AND subq1.new_edu_cycle_name = subq.old_edu_cycle_name
                                    AND subq1.new_institution_id = subq.old_institution_id) subq3
                                    ON subq3.old_inst_grade_id = subq2.old_instit_grade_id";

                $result = $connection->execute($queryData)->fetchAll('assoc');
                foreach ($result as $key => $institutionGradeSubjectData) {
                    $statement = $connection->prepare("SELECT id FROM institution_program_grade_subjects 
                                                        WHERE 
                                                        institution_grade_id = :institution_grade_id AND 
                                                        education_grade_id = :education_grade_id AND 
                                                        education_grade_subject_id = :education_grade_subject_id AND 
                                                        institution_id = :institution_id");

                    $statement->execute([
                        'institution_grade_id' => $institutionGradeSubjectData['new_inst_grade_id'],
                        'education_grade_id' => $institutionGradeSubjectData['new_ed_grade_id'],
                        'education_grade_subject_id' => $institutionGradeSubjectData['subject_id'],
                        'institution_id' => $institutionGradeSubjectData['inst_id']
                    ]);

                    if ($statement->rowCount() == 0) {
                    $statement = $connection->prepare("INSERT INTO `institution_program_grade_subjects`
                                            (`institution_grade_id`, `education_grade_id`, `education_grade_subject_id`, 
                                            `institution_id`, `created_user_id`, `created`)
                                            VALUES (:institution_grade_id,:education_grade_id,:education_grade_subject_id,
                                            :institution_id, :created_user_id, :created)");
                    $statement->execute([
                        'institution_grade_id' => $institutionGradeSubjectData['new_inst_grade_id'],
                        'education_grade_id' => $institutionGradeSubjectData['new_ed_grade_id'],
                        'education_grade_subject_id' => $institutionGradeSubjectData['subject_id'],
                        'institution_id' => $institutionGradeSubjectData['inst_id'],
                        'created_user_id' => 2,
                        'created' => date('Y-m-d H:i:s')
                    ]);
                    }
                }
                //to insert data in institution_program_grade_subjects[END]

            }
        } catch (\Exception $e) {
            echo "<pre>";
            print_R($e);
            exit;
            // pr($e->getMessage());
        }
    }
    public function updateEducationGrade($copyFrom,$copyTo){
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $connection = ConnectionManager::get('default');
        $statement1 = $connection->prepare("Select subq1.grade_id as wrong_grade_id,subq1.grade_name,subq1.period_name,subq1.programme_name ,  subq2.grade_id as correct_grade_id,subq2.grade_name ,subq2.period_name,subq2.programme_name from
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
                            on subq1.grade_name=subq2.grade_name and subq1.programme_name=subq2.programme_name;
                ");

        $statement1->execute();
        $row = $statement1->fetchAll(\PDO::FETCH_ASSOC);
        $data=[];
        foreach ($row as $rowData) {
           $data[$rowData['wrong_grade_id']] =  $rowData['correct_grade_id'];
        }
        return $data;
    }
}

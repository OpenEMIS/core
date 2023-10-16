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

        $canCopy = $this->checkIfCanCopy($copyTo);
        if ($canCopy) {
            $this->copyProcess($copyFrom, $copyTo);
        }
        $this->out('End Institution Program, Grades and Subject Copy Shell');
    }
    private function checkIfCanCopy($copyTo)
    {
        $canCopy = false;
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $count = $InstitutionGrades->find()->where([$InstitutionGrades->aliasField('academic_period_id') => $copyTo])->count();
        if ($count == 0) {
            $canCopy = true;
        }
        return $canCopy;
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
            if (!empty($InstitutionGradesdatasToInsert)) {
                //Copy Institution Grade Data start

                foreach ($InstitutionGradesdatasToInsert as $key => $gradeData) {
                    $statement = $connection->prepare('INSERT INTO institution_grades( education_grade_id, academic_period_id, 
                                        start_date, start_year, end_date, end_year, institution_id, modified_user_id, 
                                        modified, created_user_id, created) VALUES (:education_grade_id, :academic_period_id,
                                        :start_date,  :start_year, :end_date, :end_year, :institution_id, :modified_user_id,
                                        :modified, :created_user_id, :created)');
                    $statement->execute([
                        'education_grade_id' => $gradeData['education_grade_id'],
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
                //Copy Institution Grade Data start
                //Updating education grade  start
                $from_start_date = $ToAcademicPeriodsData['start_date']->format('Y-m-d');
                $to_end_date = $ToAcademicPeriodsData['end_date']->format('Y-m-d');
                $to_start_year = $ToAcademicPeriodsData['start_year'];
                $from_start_date = "'" . $from_start_date . "'";
                $to_end_date = "'" . $to_end_date . "'";
                $final_from_start_date = $ToAcademicPeriodsData['start_date']->format('Y-m-d');
                $statement1 = $connection->prepare("SELECT education_systems.academic_period_id,correct_grade.id AS correct_grade_id,institution_grades.* FROM `institution_grades`
                INNER JOIN education_grades wrong_grade ON wrong_grade.id = institution_grades.education_grade_id
                INNER JOIN education_grades correct_grade ON correct_grade.code = wrong_grade.code
                INNER JOIN education_programmes ON correct_grade.education_programme_id = education_programmes.id
                INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
                INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
                INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
                LEFT JOIN academic_periods ON institution_grades.academic_period_id=academic_periods.id
                AND academic_periods.academic_period_level_id != -1
                AND education_systems.academic_period_id = academic_periods.id
                WHERE correct_grade.id != institution_grades.education_grade_id AND academic_periods.id=$to_academic_period");

                $statement1->execute();
                $row = $statement1->fetchAll('assoc');
                foreach ($row as $rowData) {
                    $InstitutionGrades->updateAll(
                        ['education_grade_id' => $rowData['correct_grade_id']],    //field
                        ['education_grade_id' => $rowData['education_grade_id'], 'academic_period_id' => $rowData['academic_period_id'], 'institution_id' => $rowData['institution_id'],  'start_date' => $final_from_start_date, 'start_year' => $to_start_year]
                    ); //updated for checking academic_period_also
                }
                //Updating education grade  end

                //to insert data in institution_program_grade_subjects[START]
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
                //to insert data in institution_program_grade_subjects[END]

            }
        } catch (\Exception $e) {
            echo "<pre>";
            print_r($e);
            exit;
        }
    }
}

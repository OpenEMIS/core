<?php
namespace App\Shell;

use Exception;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\I18n\Time;
use Cake\Utility\Text;

class UpdateSubjectStudentTotalMarkShell extends Shell {
    public function initialize() {
        parent::initialize();
        $this->loadModel('Institution.InstitutionSubjectStudents');
        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Assessment.AssessmentItemResults');
        $this->loadModel('Assessment.AssessmentGradingTypes');
        $this->loadModel('Assessment.AssessmentGradingOptions');
        $this->loadModel('Assessment.AssessmentPeriods');
        $this->loadModel('Assessment.Assessments');

        try {
            $connection = ConnectionManager::get('default');
            $connection->query("CREATE TABLE IF NOT EXISTS updated_students (`id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL, `student_id` int(11) NOT NULL, `education_subject_id` int(11) NOT NULL, `institution_class_id` int(11) NOT NULL, `institution_id` int(11) NOT NULL, `academic_period_id` int(11) NOT NULL, PRIMARY KEY (`student_id`, `education_subject_id`, `institution_class_id`, `institution_id`, `academic_period_id`), KEY `student_id` (`student_id`), KEY `education_subject_id` (`education_subject_id`), KEY `institution_class_id` (`institution_class_id`), KEY `institution_id` (`institution_id`), KEY `academic_period_id` (`academic_period_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $e) {
            $this->out($e->getMessage());
        }
        $this->loadModel('UpdatedStudents');
    }

    public function main() {
        $selectedPeriodId = !empty($this->args[0]) ? $this->args[0] : 0;
        $pid = getmypid();
        $PAGE_LIMIT = 5000;

        $this->out($pid.': Initialize Update Subject Student Total mark ('. Time::now() .')');

        // get all academic periods unless user specifies which academic period
        $academicPeriods = $this->AcademicPeriods->find()->extract('id');
        if ($selectedPeriodId != 0) {
            $academicPeriods = [$selectedPeriodId];
        }

        foreach ($academicPeriods as $periodId) {
            $this->out($pid.': Processing ACADEMIC PERIOD ID '. $periodId .' ('. Time::now() .')');

            $subquery = $this->UpdatedStudents->find();

            // get all institution_subject_student within selected academic period
            $subjectStudentsQuery = $this->InstitutionSubjectStudents->find()
                ->select([
                    $this->InstitutionSubjectStudents->aliasField('student_id'),
                    $this->InstitutionSubjectStudents->aliasField('education_subject_id'),
                    $this->InstitutionSubjectStudents->aliasField('academic_period_id'),
                    $this->InstitutionSubjectStudents->aliasField('institution_class_id'),
                    $this->InstitutionSubjectStudents->aliasField('institution_id'),
                ])
                ->where([
                    'NOT EXISTS ('.$this->UpdatedStudents->find().')',
                    $this->InstitutionSubjectStudents->aliasField('academic_period_id') => $periodId,
                    $this->InstitutionSubjectStudents->aliasField('status') => 1
                ]);

            $studentCount = $subjectStudentsQuery->count();
            $this->out($pid.': Total number records to process: '. $studentCount .' ('. Time::now() .')');

            if ($studentCount > 0) {
                $totalPages = ceil($studentCount / $PAGE_LIMIT);
                $this->out($pid.': Total number of pages to process: '. $totalPages .' pages with '. $PAGE_LIMIT .' records each');

                for ($page = 1; $page <= $totalPages; $page++) {
                    $this->out($pid.': Processing PAGE '.$page.' ('. Time::now() .')');
                    $offset = ($page-1)*$PAGE_LIMIT;
                    $studentPageData = $subjectStudentsQuery->limit($PAGE_LIMIT)->offset($offset)->toArray();

                    foreach ($studentPageData as $student) {
                        // calculate total marks from AssessmentItemResults for each student record
                        $query = $this->AssessmentItemResults->find();
                        $resultsQuery = $query
                            ->select([
                                'calculated_total' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)')
                            ])
                            ->matching('AssessmentPeriods')
                            ->matching('AssessmentGradingOptions.AssessmentGradingTypes')
                            ->where([
                                    $this->AssessmentItemResults->aliasField('student_id') => $student->student_id,
                                    $this->AssessmentItemResults->aliasField('academic_period_id') => $student->academic_period_id,
                                    $this->AssessmentItemResults->aliasField('education_subject_id') => $student->education_subject_id,
                                    $this->AssessmentGradingTypes->aliasField('result_type') => 'MARKS'
                            ])
                            ->group([
                                $this->AssessmentItemResults->aliasField('student_id'),
                                $this->AssessmentItemResults->aliasField('assessment_id'),
                                $this->AssessmentItemResults->aliasField('education_subject_id')
                            ])
                            ->first();

                        if (!empty($resultsQuery)) {
                            try {
                                $student->total_mark = $resultsQuery->calculated_total;
                                $this->InstitutionSubjectStudents->save($student);

                                // update updated_students table
                                $updatedStudent = [
                                    'id' => Text::uuid(),
                                    'student_id' => $student->student_id,
                                    'academic_period_id' => $student->academic_period_id,
                                    'education_subject_id' => $student->education_subject_id,
                                    'institution_class_id' => $student->institution_class_id,
                                    'institution_id' => $student->institution_id
                                ];
                                $updatedStudentEntity = $this->UpdatedStudents->newEntity($updatedStudent);
                                $this->UpdatedStudents->save($updatedStudentEntity);

                            } catch (\Exception $e) {
                                $this->out($pid.': Error encoutered saving ACADEMIC PERIOD ID '. $periodId .', STUDENT ID '. $student['student_id'] .' ('.Time::now() .')');
                                $this->out($e->getMessage());
                            }
                        }
                    }

                    $this->out($pid.': End processing PAGE '.$page.' ('. Time::now() .')');
                }
            }

            $this->out($pid.': End processing ACADEMIC PERIOD ID '. $periodId .' ('. Time::now() .')');
        }

        $this->out($pid.': End Update Subject Student Total mark ('. Time::now() .')');
    }
}

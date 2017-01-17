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

        // temporary table to log institution_subject_students that have been processed
        try {
            $connection = ConnectionManager::get('default');
            $connection->query("CREATE TABLE IF NOT EXISTS `updated_subject_students` (`id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL, `student_id` int(11) NOT NULL, `education_subject_id` int(11) NOT NULL, `institution_class_id` int(11) NOT NULL, `institution_id` int(11) NOT NULL, `academic_period_id` int(11) NOT NULL, PRIMARY KEY (`student_id`, `education_subject_id`, `institution_class_id`, `institution_id`, `academic_period_id`), KEY `student_id` (`student_id`), KEY `education_subject_id` (`education_subject_id`), KEY `institution_class_id` (`institution_class_id`), KEY `institution_id` (`institution_id`), KEY `academic_period_id` (`academic_period_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $e) {
            $this->out($e->getMessage());
        }
        $this->loadModel('UpdatedSubjectStudents');
    }

    public function main() {
        $pid = getmypid();
        $PAGE_LIMIT = 5000;
        $selectedPeriodId = !empty($this->args[0]) ? $this->args[0] : 0;
        $selectedInsitutionId = !empty($this->args[1]) ? $this->args[1] : 0;

        $this->out($pid.': Initialize Update Subject Student Total mark ('. Time::now() .')');

        if ($selectedPeriodId != 0 && $selectedInsitutionId != 0) {
            $this->out($pid.': Processing ACADEMIC PERIOD ID '.$selectedPeriodId.' and INSTITUTION ID '.$selectedInsitutionId.' ('. Time::now() .')');

            // get students that are not already processed in updated_subject_students
            $subjectStudentsQuery = $this->InstitutionSubjectStudents->find()
                ->select([
                    $this->InstitutionSubjectStudents->aliasField('student_id'),
                    $this->InstitutionSubjectStudents->aliasField('education_subject_id'),
                    $this->InstitutionSubjectStudents->aliasField('academic_period_id'),
                    $this->InstitutionSubjectStudents->aliasField('institution_class_id'),
                    $this->InstitutionSubjectStudents->aliasField('institution_id'),
                ])
                ->where([
                    'NOT EXISTS ('.
                        $this->UpdatedSubjectStudents->find()
                            ->where([
                                $this->UpdatedSubjectStudents->aliasField('student_id').' = '.$this->InstitutionSubjectStudents->aliasField('student_id'),
                                $this->UpdatedSubjectStudents->aliasField('education_subject_id').' = '.$this->InstitutionSubjectStudents->aliasField('education_subject_id'),
                                $this->UpdatedSubjectStudents->aliasField('academic_period_id').' = '.$this->InstitutionSubjectStudents->aliasField('academic_period_id'),
                                $this->UpdatedSubjectStudents->aliasField('institution_class_id').' = '.$this->InstitutionSubjectStudents->aliasField('institution_class_id'),
                                $this->UpdatedSubjectStudents->aliasField('institution_id').' = '.$this->InstitutionSubjectStudents->aliasField('institution_id'),
                            ])
                    .')',
                    $this->InstitutionSubjectStudents->aliasField('academic_period_id') => $selectedPeriodId,
                    $this->InstitutionSubjectStudents->aliasField('institution_id') => $selectedInsitutionId,
                    $this->InstitutionSubjectStudents->aliasField('status') => 1
                ]);

            $studentCount = $subjectStudentsQuery->count();
            $this->out($pid.': Records to process: '. $studentCount .' ('. Time::now() .')');
            $processedRecordCount = 0;
            $loop = ($studentCount > 0);

            while ($loop) {
                $studentPageData = $subjectStudentsQuery->limit($PAGE_LIMIT)->toArray();

                if (!empty($studentPageData)) {
                    foreach ($studentPageData as $student) {
                        // calculate total marks from AssessmentItemResults for each student record
                        $query = $this->AssessmentItemResults->find();
                        $itemResults = $query
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

                        if (!empty($itemResults)) {
                            try {
                                $student->total_mark = $itemResults->calculated_total;
                                $this->InstitutionSubjectStudents->save($student);

                            } catch (\Exception $e) {
                                $this->out($pid.': Error encoutered saving ACADEMIC PERIOD ID: '. $selectedPeriodId .', INSTITUTION ID: '. $student->institution_id .' STUDENT ID: '. $student->student_id .' ('.Time::now() .')');
                                $this->out($e->getMessage());
                            }
                        }

                        // insert into updated_subject_students
                        try {
                            $updatedStudent = [
                                'id' => Text::uuid(),
                                'student_id' => $student->student_id,
                                'academic_period_id' => $student->academic_period_id,
                                'education_subject_id' => $student->education_subject_id,
                                'institution_class_id' => $student->institution_class_id,
                                'institution_id' => $student->institution_id
                            ];

                            $updatedStudentEntity = $this->UpdatedSubjectStudents->newEntity($updatedStudent);
                            $this->UpdatedSubjectStudents->save($updatedStudentEntity);
                        } catch (\Exception $e) {
                            $this->out($e->getMessage());
                        }
                    }

                    $processedRecordCount += count($studentPageData);
                    $this->out($pid.': Total records processed: '.$processedRecordCount.' ('. Time::now() .')');

                } else {
                    $loop = false;
                }
            }

            $this->out($pid.': End processing ACADEMIC PERIOD ID '. $selectedPeriodId .' and INSTITUTION ID '.$selectedInsitutionId.' ('. Time::now() .')');
        }

        // remove file
        exec('rm tmp/shellFiles/'.$pid.'.txt');
        $this->out($pid.': End Update Subject Student Total mark ('. Time::now() .')');
    }
}

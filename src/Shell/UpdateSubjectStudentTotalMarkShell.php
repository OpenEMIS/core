<?php
namespace App\Shell;

use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\I18n\Time;
use Cake\Utility\Text;
use Cake\Filesystem\File;

class UpdateSubjectStudentTotalMarkShell extends Shell {
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionSubjectStudents');
        $this->loadModel('Institution.InstitutionStudents');
        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Assessment.AssessmentItemResults');
        $this->loadModel('Assessment.AssessmentGradingTypes');
        $this->loadModel('Assessment.AssessmentGradingOptions');
        $this->loadModel('Assessment.AssessmentPeriods');
        $this->loadModel('Assessment.Assessments');
        $this->loadModel('UpdatedSubjectStudents');
    }

    public function main()
    {
        $threadNum = $this->args[0];
        $selectedPeriodId = !empty($this->args[1]) ? $this->args[1] : 0;
        $selectedInsitutionId = !empty($this->args[2]) ? $this->args[2] : 0;

        $pid = getmypid();
        $PAGE_LIMIT = 2500;

        $this->out($pid.': Initialize Update Subject Student Total mark ('. Time::now() .')');

        if ($selectedPeriodId != 0 && $selectedInsitutionId != 0) {
            $this->out($pid.': Processing ACADEMIC PERIOD ID '.$selectedPeriodId.' and INSTITUTION ID '.$selectedInsitutionId.' ('. Time::now() .')');

            $studentCount = count($this->getInstitutionSubjectStudents($selectedPeriodId, $selectedInsitutionId));
            $this->out($pid.': Records to process: '. $studentCount .' ('. Time::now() .')');
            $processedRecordCount = 0;
            $loop = ($studentCount > 0);

            while ($loop) {
                $studentPageData = $this->getInstitutionSubjectStudents($selectedPeriodId, $selectedInsitutionId, $PAGE_LIMIT);

                if (!empty($studentPageData)) {
                    foreach ($studentPageData as $student) {
                        // calculate total marks from AssessmentItemResults for each student record
                        $query = $this->AssessmentItemResults->find();
                        $itemResults = $query
                            ->select([
                                'calculated_total' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)')
                            ])
                            ->matching('Assessments')
                            ->matching('AssessmentPeriods')
                            ->matching('AssessmentGradingOptions.AssessmentGradingTypes')
                            ->where([
                                    $this->AssessmentItemResults->aliasField('student_id') => $student->student_id,
                                    $this->AssessmentItemResults->aliasField('academic_period_id') => $student->academic_period_id,
                                    $this->AssessmentItemResults->aliasField('education_subject_id') => $student->education_subject_id,
                                    $this->AssessmentGradingTypes->aliasField('result_type') => 'MARKS',
                                    $this->Assessments->aliasField('education_grade_id') => $student->InstitutionStudents['education_grade_id']
                            ])
                            ->group([
                                $this->AssessmentItemResults->aliasField('student_id'),
                                $this->AssessmentItemResults->aliasField('assessment_id'),
                                $this->AssessmentItemResults->aliasField('education_subject_id')
                            ])
                            ->first();

                        if (!empty($itemResults)) {
                            try {
                                $this->InstitutionSubjectStudents->query()
                                    ->update()
                                    ->set([
                                        'total_mark' => $itemResults->calculated_total,
                                        'modified' => Time::now()
                                    ])
                                    ->where([
                                        'student_id' => $student->student_id,
                                        'academic_period_id' => $student->academic_period_id,
                                        'education_subject_id' => $student->education_subject_id,
                                        'institution_class_id' => $student->institution_class_id,
                                        'institution_id' => $student->institution_id
                                    ])
                                    ->execute();

                            } catch (\Exception $e) {
                                $this->out($pid.': Error encoutered saving ACADEMIC PERIOD ID: '. $selectedPeriodId .', INSTITUTION ID: '. $student->institution_id .', STUDENT ID: '. $student->student_id .' ('.Time::now() .')');
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
        $file = new File('webroot/shellFiles/thread'.$threadNum.'.log');
        $file->delete();

        $this->out($pid.': End Update Subject Student Total mark ('. Time::now() .')');
    }

    private function getInstitutionSubjectStudents($academicPeriodId, $insitutionId, $limit=0)
    {
        // get students that are not already processed in updated_subject_students
        $query = $this->InstitutionSubjectStudents->find()
            ->select([
                $this->InstitutionSubjectStudents->aliasField('student_id'),
                $this->InstitutionSubjectStudents->aliasField('education_subject_id'),
                $this->InstitutionSubjectStudents->aliasField('academic_period_id'),
                $this->InstitutionSubjectStudents->aliasField('institution_class_id'),
                $this->InstitutionSubjectStudents->aliasField('institution_id'),
                $this->InstitutionStudents->aliasField('education_grade_id')
            ])
            ->innerJoin(
                [$this->InstitutionStudents->alias() => $this->InstitutionStudents->table()],
                [
                    $this->InstitutionStudents->aliasField('institution_id = ') . $this->InstitutionSubjectStudents->aliasField('institution_id'),
                    $this->InstitutionStudents->aliasField('academic_period_id = ') . $this->InstitutionSubjectStudents->aliasField('academic_period_id'),
                    $this->InstitutionStudents->aliasField('student_id = ') . $this->InstitutionSubjectStudents->aliasField('student_id'),
                ]
            )
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
                $this->InstitutionSubjectStudents->aliasField('academic_period_id') => $academicPeriodId,
                $this->InstitutionSubjectStudents->aliasField('institution_id') => $insitutionId,
                //$this->InstitutionSubjectStudents->aliasField('status') => 1 //(Column is not exist on the table)
            ]);

        if ($limit != 0) {
            $query->limit($limit);
        }

        $subjectStudents = $query->toArray();
        return $subjectStudents;
    }
}

<?php
namespace App\Shell;

use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\I18n\Time;

class UpdateSubjectStudentTotalMarkShell extends Shell {
    public function initialize() {
        parent::initialize();
        $this->loadModel('Institution.InstitutionSubjectStudents');
        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Institution.Institutions');
        $this->loadModel('Institution.InstitutionClasses');
        $this->loadModel('Institution.InstitutionClassGrades');
        $this->loadModel('Assessment.AssessmentItemResults');
        $this->loadModel('Assessment.AssessmentItems');
        $this->loadModel('Assessment.AssessmentGradingTypes');
        $this->loadModel('Assessment.AssessmentGradingOptions');
        $this->loadModel('Assessment.AssessmentPeriods');
        $this->loadModel('Assessment.Assessments');

    }

    public function main() {
        $selectedPeriodId = !empty($this->args[0]) ? $this->args[0] : 0;
        $selectedAssessmentId = !empty($this->args[1]) ? $this->args[1] : 0;
        $selectedSubjectId = !empty($this->args[2]) ? $this->args[2] : 0;

        $PAGE_LIMIT = 1000;
        $pid = getmypid();

        $this->out($pid.': Initialize Update Subject Student Total mark ('. Time::now() .')');
        $academicPeriods = $this->AcademicPeriods->find()->extract('id');
        if ($selectedPeriodId != 0) {
            $academicPeriods = [$selectedPeriodId];
        }

        foreach ($academicPeriods as $periodId) {
            $this->out($pid.': Processing ACADEMIC PERIOD ID '. $periodId .' ('. Time::now() .')');

            $assessments = $this->Assessments->find()
                ->where([$this->Assessments->aliasField('academic_period_id') => $periodId])
                ->extract('id');
            if ($selectedAssessmentId != 0) {
                $assessments = [$selectedAssessmentId];
            }

            foreach ($assessments as $assessmentId) {
                $this->out($pid.': Processing ACADEMIC PERIOD ID '. $periodId .', ASSESSMENT ID '. $assessmentId .' ('. Time::now() .')');

                $educationSubjects = $this->AssessmentItems->find()
                    ->where([$this->AssessmentItems->aliasField('assessment_id') => $assessmentId])
                    ->order([$this->AssessmentItems->aliasField('education_subject_id')])
                    ->extract('education_subject_id');
                if ($selectedSubjectId != 0) {
                    $educationSubjects = [$selectedSubjectId];
                }

                foreach ($educationSubjects as $educationSubjectId) {
                    $this->out($pid.': Processing ACADEMIC PERIOD ID '. $periodId .', ASSESSMENT ID '. $assessmentId .', EDUCATION SUBJECT ID '. $educationSubjectId .' ('. Time::now() .')');

                    $query = $this->AssessmentItemResults->find();
                    $resultsQuery = $query
                        ->select([
                            $this->AssessmentItemResults->aliasField('student_id'),
                            $this->AssessmentItemResults->aliasField('education_subject_id'),
                            $this->AssessmentItemResults->aliasField('academic_period_id'),
                            'calculated_total' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)'),
                        ])
                        ->matching('AssessmentPeriods')
                        ->matching('AssessmentGradingOptions.AssessmentGradingTypes')
                        ->where([
                                $this->AssessmentItemResults->aliasField('academic_period_id') => $periodId,
                                $this->AssessmentItemResults->aliasField('assessment_id') => $assessmentId,
                                $this->AssessmentItemResults->aliasField('education_subject_id') => $educationSubjectId,
                                $this->AssessmentGradingTypes->aliasField('result_type') => 'MARKS'
                        ])
                        ->group([
                            $this->AssessmentItemResults->aliasField('student_id'),
                            $this->AssessmentItemResults->aliasField('assessment_id'),
                            $this->AssessmentItemResults->aliasField('education_subject_id')
                        ]);

                    $resultsCount = $resultsQuery->count();
                    $this->out($pid.': Total number records to save: '. $resultsCount);

                    if ($resultsCount > 0) {
                        $totalPages = ceil($resultsCount / $PAGE_LIMIT);
                        $this->out($pid.': Total number of pages to save: '. $totalPages .' pages with '. $PAGE_LIMIT .' records each');

                        for ($page = 1; $page <= $totalPages; $page++) {
                            $offset = ($page-1)*$PAGE_LIMIT;
                            $resultPageData = $resultsQuery->limit($PAGE_LIMIT)->offset($offset)->toArray();
                            $this->out($pid.': Processing PAGE '.$page.' with '. count($resultPageData) .' records ('. Time::now() .')');

                            $saveError = 0;
                            $updateEntities = [];
                            foreach ($resultPageData as $result) {
                                $subjectStudents = $this->InstitutionSubjectStudents->find()
                                    ->where([
                                        $this->InstitutionSubjectStudents->aliasField('student_id') => $result->student_id,
                                        $this->InstitutionSubjectStudents->aliasField('education_subject_id') => $result->education_subject_id,
                                        $this->InstitutionSubjectStudents->aliasField('academic_period_id') => $result->academic_period_id
                                    ])
                                    ->toArray();

                                foreach ($subjectStudents as $student) {
                                    $student->total_mark = $result->calculated_total;
                                    $updateEntities[] = $student;
                                }
                            }

                            try {
                                $this->out($pid.': Saving PAGE '. $page .' ('. Time::now() .')');
                                $this->InstitutionSubjectStudents->saveMany($updateEntities);

                            } catch (\Exception $e) {
                                $this->out($pid.': Error encoutered saving ACADEMIC PERIOD ID '. $periodId .', ASSESSMENT ID '. $assessmentId .', SUBJECT ID '. $educationSubjectId .' PAGE '. $page .' ('.Time::now() .')');
                                $this->out($e->getMessage());
                                $saveError = 1;
                            }

                            $this->out($pid.': End processing PAGE '.$page.' ('. Time::now() .')');
                        }
                    }

                    $this->out($pid.': End processing ACADEMIC PERIOD ID '. $periodId .', ASSESSMENT ID '. $assessmentId .', EDUCATION SUBJECT ID '. $educationSubjectId .' ('. Time::now() .')');
                }

                $this->out($pid.': End processing ACADEMIC PERIOD ID '. $periodId .', ASSESSMENT ID '. $assessmentId .' ('. Time::now() .')');
            }

            $this->out($pid.': End processing ACADEMIC PERIOD ID '. $periodId .' ('. Time::now() .')');
        }

    }
}

<?php

namespace Assessment\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Core\Configure;
use Cake\Log\Log;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;

class AssessmentItemResultsTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AssessmentGradingOptions', ['className' => 'Assessment.AssessmentGradingOptions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'add'],
            'OpenEMIS_Classroom' => ['add', 'edit', 'delete']
        ]);
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }
        $this->addBehavior('Import.ImportLink');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('student_id')
            ->requirePresence('assessment_id')
            ->requirePresence('education_subject_id')
            ->requirePresence('education_grade_id')
            ->requirePresence('academic_period_id')
            ->requirePresence('assessment_period_id')
            ->requirePresence('institution_id')
            ->allowEmpty('marks')
            ->add('marks', 'ruleCheckAssessmentMarks', [
                'rule' => ['checkAssessmentMarks']
            ]);
    }

    // public function implementedEvents()
    // {
    //     $events = parent::implementedEvents();
    //     $events['Model.InstitutionStudentRisks.calculateRiskValue'] = 'institutionStudentRiskCalculateRiskValue';
    //     return $events;
    // }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //POCOR-6824 start
        $institutionId = $entity->institution_id;
        $InstitutionClassId = $entity->institution_classes_id;
        $institutionClass = TableRegistry::get('institution_classes');
        $findclass =
            $institutionClass->find()->select([
                'id' => $institutionClass->aliasField('id')
            ])->where([
                $institutionClass->aliasField('institution_id') => $institutionId,
                $institutionClass->aliasField('id') => $InstitutionClassId
            ])->first();
        if ($findclass == null && $findclass['id'] != $InstitutionClassId) {
            $response[] = "No Institution class Id record Exist";
            $entity->errors($response);
            return false;
        } else { //POCOR-6824 end add if else condition
            //POCOR-6947
            $institutionStudents = TableRegistry::get('institution_students');
            $institutionStudentsData = $institutionStudents
                ->find()
                ->where([
                    $institutionStudents->aliasField('student_id') => $entity->student_id,
                    $institutionStudents->aliasField('education_grade_id') => $entity->education_grade_id,
                    $institutionStudents->aliasField('institution_id') => $entity->institution_id,
                    $institutionStudents->aliasField('academic_period_id') => $entity->academic_period_id
                ])->toArray();
            if (empty($institutionStudentsData)) {
                $response[] = "No academic records for this student";
                $entity->errors($response);
                return false;
            } else {
                if ($entity->isNew()) {
                    //POCOR-7536-KHINDOL
                    //AS the ID is not the KEY do shadow save and delete new entity
                    $assessmentItemResults = TableRegistry::get('assessment_item_results');
                    $previousAssessment = $assessmentItemResults->find()
                        ->where([
                            $assessmentItemResults->aliasField('student_id') => $entity->student_id,
                            $assessmentItemResults->aliasField('academic_period_id') => $entity->academic_period_id,
                            $assessmentItemResults->aliasField('education_grade_id') => $entity->education_grade_id,
                            $assessmentItemResults->aliasField('assessment_period_id') => $entity->assessment_period_id,
                            $assessmentItemResults->aliasField('assessment_id') => $entity->assessment_id,
                            $assessmentItemResults->aliasField('education_subject_id') => $entity->education_subject_id,
                        ])
                        ->order([ //POCOR-7580-KHINDOL
                            $assessmentItemResults->aliasField('created') => 'DESC',
                            $assessmentItemResults->aliasField('modified') => 'DESC',
                        ])
                        ->first();
                    if ($previousAssessment) {
                        $id = $previousAssessment->id;
                        $marks = $entity->marks;
                        $institution_classes_id = $entity->institution_classes_id;
                        $institution_id = $entity->institution_id;
                        $modified_user_id = $entity->created_user_id;
                        $modified = date('Y-m-d H:i:s');
                        $connection = ConnectionManager::get('default');
                        $sql = "UPDATE assessment_item_results SET 
                                   marks=$marks, 
                                   institution_classes_id=$institution_classes_id,
                                   institution_id=$institution_id,
                                   modified_user_id = $modified_user_id,
                                   modified = '$modified',
                                   created = '$modified'
                                   where id='$id'";
                        $connection->execute($sql);
                        $previousAssessment = $assessmentItemResults->find()
                            ->where([
                                $assessmentItemResults->aliasField('id') => $id,
                            ])
                            ->first();
                        //POCOR-7536-KHINDOL
                        $this->getAssessmentGrading($previousAssessment);
//                        $this->log('saved_old_entity', 'debug');
//                        $this->log($entity, 'debug');
                        $event->stopPropagation();
                    } else {
//                        $this->log('created_new_entity', 'debug');
//                        $this->log($entity, 'debug');
                        $entity->id = Text::uuid();
                    }
                }

                $this->getAssessmentGrading($entity);
            }
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // delete record if user removes the mark or grading
        $marks = $entity->marks;
        $grading = $entity->assessment_grading_option_id;
        if (is_null($marks) && is_null($grading)) {
            $this->delete($entity);
        }

        $listeners = [
            TableRegistry::get('Institution.InstitutionSubjectStudents')
        ];

        $this->dispatchEventToModels('Model.AssessmentResults.afterSave', [$entity], $this, $listeners);
    }

    public function findResults(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $controller = $options['_controller'];
        $session = $controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id'); //POCOR-6823


        $studentId = -1;
        if ($session->check('Student.Results.student_id')) {
            $studentId = $session->read('Student.Results.student_id');
        } else {
            $studentId = $session->read('Profile.StudentUser.primaryKey.id');
        }

        if ($options['user']['is_student'] == 1) {
            $studentId = $options['user']['id'];
        }

        //Start POCOR-6823
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $conditionsClassStudents = [
            $InstitutionClassStudents->aliasField('academic_period_id = ') => $academicPeriodId,
            $InstitutionClassStudents->aliasField('student_id = ') => $studentId,
            $InstitutionClassStudents->aliasField('institution_id = ') => $institutionId,
            $InstitutionClassStudents->aliasField('student_status_id = ') => 1,
        ];

        $ClassStudentsStatusUpdate = $InstitutionClassStudents
            ->find()
            ->where($conditionsClassStudents)
            ->all();

        $className = '';
        if (!$ClassStudentsStatusUpdate->isEmpty()) {
            $ClassStudents = $ClassStudentsStatusUpdate->first();
            $className = $ClassStudents->institution_class_id;
        }
        //End POCOR-6823

        return $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('marks'),
                $this->aliasField('assessment_grading_option_id'),
                $this->aliasField('student_id'),
                $this->aliasField('assessment_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('assessment_period_id'),
                $this->Assessments->aliasField('code'),
                $this->Assessments->aliasField('name'),
                $this->Assessments->aliasField('education_grade_id'),
                $this->EducationSubjects->aliasField('code'),
                $this->EducationSubjects->aliasField('name'),
                $this->AssessmentGradingOptions->aliasField('code'),
                $this->AssessmentGradingOptions->aliasField('name'),
                $this->AssessmentGradingOptions->aliasField('assessment_grading_type_id'),
                $this->AssessmentPeriods->aliasField('code'),
                $this->AssessmentPeriods->aliasField('name'),
                $this->AssessmentPeriods->aliasField('weight')
            ])
            ->innerJoinWith('Assessments')
            ->innerJoinWith('EducationSubjects')
            ->innerJoinWith('AssessmentGradingOptions')
            ->innerJoinWith('AssessmentPeriods')
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('student_id') => $studentId,
                // $this->aliasField('institution_classes_id ') => $className,  // POCOR-6823
                // $this->aliasField('institution_id') => $institutionId    //POCOR-6823
                // $this->aliasField('institution_id') => $institutionId    //POCOR-6989 : Commented to show data for all instituion 
            ])
            ->order([
                $this->aliasField('created') => 'DESC', //POCOR-6823
                $this->aliasField('modified') => 'DESC', //POCOR-6823
                $this->Assessments->aliasField('code'), $this->Assessments->aliasField('name')
            ])->all(); //->first(); //POCOR-6948 Comment Reason: taking one record of assessment instead of all records for student. 
    }

    /**
     *  Function to get the assessment results based academic period
     *
     * @param integer $academicPeriodId The academic period id
     *
     * @return array The assessment results group field - institution id, key field - student id
     *      value field - assessment item id with array containing marks, grade name and grade code
     */
    public function getAssessmentItemResults($academicPeriodId, $assessmentId, $subjectId, $studentId, $classId)
    {
        $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');

        $query = $this
            ->find()
            ->select([
                'grade_name' => 'AssessmentGradingOptions.name',
                'grade_code' => 'AssessmentGradingOptions.code',
                $this->aliasField('student_id'),
                $this->aliasField('assessment_period_id'),
//                $this->aliasField('marks'),
                $this->aliasField('academic_period_id'),//POCOR-6479 
                $this->aliasField('education_subject_id'),//POCOR-6479
                $this->aliasField('education_grade_id'),//POCOR-6479
                $this->aliasField('assessment_id'),//POCOR-6479 
            ])
            ->contain(['AssessmentGradingOptions'])
            ->innerJoin([$SubjectStudents->alias() => $SubjectStudents->table()], [
                $SubjectStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                $SubjectStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                $SubjectStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                $SubjectStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                $SubjectStudents->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id')
            ])
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('assessment_id') => $assessmentId,
                $this->aliasField('education_subject_id') => $subjectId,
                $this->aliasField('student_id') => $studentId
            ])//POCOR-6479 starts
            ->group([
                $this->aliasField('assessment_period_id')
            ])//POCOR-6479 ends
            ->hydrate(false);
        $results = $query->toArray();
        //
        $returnArray = [];
        //POCOR-6479 starts
        foreach ($results as $result) {
//            $assessmentItemResults = TableRegistry::get('assessment_item_results');
//            $assessmentItemResultsData = $assessmentItemResults->find()
//                ->select([
//                    $assessmentItemResults->aliasField('marks')
//                ])
//                ->order([
//                    $assessmentItemResults->aliasField('modified') => 'DESC',
//                    $assessmentItemResults->aliasField('created') => 'DESC'
//
//                ])
//                ->where([
//                    $assessmentItemResults->aliasField('student_id') => $result['student_id'],
//                    $assessmentItemResults->aliasField('academic_period_id') => $result['academic_period_id'],
//                    $assessmentItemResults->aliasField('education_grade_id') => $result['education_grade_id'],
//                    $assessmentItemResults->aliasField('assessment_period_id') => $result['assessment_period_id'],
//                    $assessmentItemResults->aliasField('education_subject_id') => $result['education_subject_id'],
//                ])
//                ->first();
//
//            $result['marks'] = $assessmentItemResultsData->marks;
            $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $options = ["student_id" => $result['student_id'],
//            "institution_id" => $entity->institution_id'],
//            "institution_class_id" => $entity->institution_class_id,
                "academic_period_id" => $result['academic_period_id'],
                "education_grade_id" => $result['education_grade_id'],
                "education_subject_id" => $result['education_subject_id'],
                "assessment_period_id" => $result['assessment_period_id'],
                'assessment_id' => $result['assessment_id']
            ];
            $marks = $ItemResults::getLastMark($options);
            $last_results = array_column($marks, 'marks');
            $sum_results = array_sum($last_results);
            $result['marks'] = round($sum_results, 2);
            $returnArray[$result['student_id']][$subjectId][$result['assessment_period_id']] = [
                'marks' => $result['marks'],
                'grade_name' => $result['grade_name'],
                'grade_code' => $result['grade_code']
            ];
        }//POCOR-6479 ends
        return $returnArray;
    }

    // result criteria for indexes will be hide for now.
    // public function institutionStudentIndexCalculateIndexValue(Event $event, ArrayObject $params)
    // {
    //     $institutionId = $params['institution_id'];
    //     $studentId = $params['student_id'];
    //     $academicPeriodId = $params['academic_period_id'];
    //     $criteriaName = $params['criteria_name'];

    //     $valueIndex = $this->getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName);

    //     return $valueIndex;
    // }

    // public function getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName)
    // {
    //     $results = $this
    //         ->find()
    //         ->where([
    //             $this->aliasField('institution_id') => $institutionId,
    //             $this->aliasField('student_id') => $studentId,
    //             $this->aliasField('academic_period_id') => $academicPeriodId,
    //         ])
    //         ->all();

    //     $getValueIndex = 0;
    //     foreach ($results as $key => $resultsObj) {
    //         $getValueIndex = $getValueIndex + $resultsObj->marks;
    //     }

    //     return $getValueIndex;
    // }

    public function getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName)
    {
        $results = $this
            ->find()
            ->contain(['Assessments', 'EducationSubjects'])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->all();

        $referenceDetails = [];
        foreach ($results as $key => $obj) {
            $assessmentName = $obj->assessment->name;
            $educationSubjectName = $obj->education_subject->name;
            $marks = !is_null($obj->marks) ? $obj->marks : 'null';

            $referenceDetails[$obj->assessment_id] = __($assessmentName) . ' - ' . __($educationSubjectName) . ' (' . $marks . ')';
        }

        // tooltip only receieved string to be display
        $reference = '';
        foreach ($referenceDetails as $key => $referenceDetailsObj) {
            $reference = $reference . $referenceDetailsObj . '<br/>';
        }

        return $reference;
    }

    private function getAssessmentGrading(Entity $entity)
    {
        if ($entity->has('marks') && !$entity->has('assessment_grading_option_id')) {
            $educationSubjectId = $entity->education_subject_id;
            $assessmentId = $entity->assessment_id;
            $assessmentPeriodId = $entity->assessment_period_id;

            $AssessmentItemsGradingTypes = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
            $assessmentItemsGradingTypeEntity = $AssessmentItemsGradingTypes
                ->find()
                ->contain('AssessmentGradingTypes.GradingOptions')
                ->where([
                    $AssessmentItemsGradingTypes->aliasField('education_subject_id') => $educationSubjectId,
                    $AssessmentItemsGradingTypes->aliasField('assessment_id') => $assessmentId,
                    $AssessmentItemsGradingTypes->aliasField('assessment_period_id') => $assessmentPeriodId
                ])
                ->first();

            if ($assessmentItemsGradingTypeEntity->has('assessment_grading_type')) {
                $assessmentGradingTypeEntity = $assessmentItemsGradingTypeEntity->assessment_grading_type;
                $resultType = $assessmentGradingTypeEntity->result_type;

                if (in_array($resultType, ['MARKS', 'DURATION'])) {
                    if ($assessmentGradingTypeEntity->has('grading_options') && !empty($assessmentGradingTypeEntity->grading_options)) {
                        foreach ($assessmentGradingTypeEntity->grading_options as $key => $gradingOptionObj) {
                            if ($entity->marks >= $gradingOptionObj->min && $entity->marks <= $gradingOptionObj->max) {
                                $entity->assessment_grading_option_id = $gradingOptionObj->id;
                            }
                        }
                    }
                }
            }
        }
    }

    public function getTotalMarks($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId, $institutionClassesId, $assessmentPeriodId, $institutionId)
    {
        $query = $this->find();
        $totalMarks = $query
            // ->select([
            //     'calculated_total' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)')
            // ])//POCOR-6479 comment code
            ->matching('Assessments')
            ->matching('AssessmentPeriods')
            ->matching('AssessmentGradingOptions.AssessmentGradingTypes')
            ->order([
                $this->aliasField('created') => 'DESC'
            ])
            ->where([
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('education_subject_id') => $educationSubjectId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                // $this->AssessmentGradingOptions->AssessmentGradingTypes->aliasField('result_type') => 'MARKS',//POCOR-6479 comment code
            ])
            ->group([
                // $this->aliasField('student_id'),//POCOR-6479 comment code
                // $this->aliasField('assessment_id'),//POCOR-6479 comment code
                $this->aliasField('assessment_period_id')//POCOR-6479 
            ])->toArray();
        //POCOR-6479 starts
        $sumMarks = [];
        foreach ($totalMarks as $result) {
            $assessmentItemResults = TableRegistry::get('assessment_item_results');
            $assessmentItemResultsData = $assessmentItemResults->find()
                ->select([
                    $assessmentItemResults->aliasField('marks')
                ])
                ->order([
                    $assessmentItemResults->aliasField('modified') => 'DESC',
                    $assessmentItemResults->aliasField('created') => 'DESC'

                ])
                ->where([
                    $assessmentItemResults->aliasField('student_id') => $result['student_id'],
                    $assessmentItemResults->aliasField('academic_period_id') => $result['academic_period_id'],
                    $assessmentItemResults->aliasField('education_grade_id') => $result['education_grade_id'],
                    $assessmentItemResults->aliasField('assessment_period_id') => $result['assessment_period_id'],
                    $assessmentItemResults->aliasField('education_subject_id') => $result['education_subject_id'],
                ])
                ->first();

            $sumMarks[] = $assessmentItemResultsData->marks * $result->_matchingData['AssessmentPeriods']->weight;
        }
        $sumMarks = array_sum($sumMarks);
        return $sumMarks;//POCOR-6479 ends
    }

//    /*
//    * Function is get the total mark of the subject
//    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
//    * return data
//    * @ticket POCOR-6776
//    */
//    public function getTotalMarksForSubject($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId, $institutionClassesId, $assessmentPeriodId, $institutionId)
//    {
//        $query = $this->find();
//        $totalMarks = $query
//            ->select([
//                'calculated_total' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)')
//            ])
//            ->matching('Assessments')
//            ->matching('AssessmentPeriods')
//            ->order([
//                $this->aliasField('created') => 'DESC'
//            ])
//            ->where([
//                $this->aliasField('student_id') => $studentId,
//                $this->aliasField('academic_period_id') => $academicPeriodId,
//                $this->aliasField('education_subject_id') => $educationSubjectId,
//                $this->aliasField('education_grade_id') => $educationGradeId,
//                $this->aliasField('education_grade_id') => $educationGradeId,
//                $this->aliasField('institution_id') => $institutionId, //POCOR-6835
//            ])
//            ->group([
//                $this->aliasField('student_id'),
//                $this->aliasField('assessment_id'),
//                $this->aliasField('education_subject_id')
//            ])
//            ->first();
//
//        return $totalMarks;
//    }
//
//    /*
//    * Function is get the total mark of the subject
//    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
//    * return data
//    * @ticket POCOR-7201
//    */
//    public function getTotalMarksForAssessment($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId, $institutionClassesId, $assessmentPeriodId, $institutionId)
//    {
//        $query = $this->find();
//        $totalMarks = $query
//            ->select([
//                'calculated_total' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)')
//            ])
//            ->matching('Assessments')
//            ->matching('AssessmentPeriods')
//            ->order([
//                $this->aliasField('created') => 'DESC'
//            ])
//            ->where([
//                $this->aliasField('student_id') => $studentId,
//                $this->aliasField('academic_period_id') => $academicPeriodId,
//                $this->aliasField('education_subject_id') => $educationSubjectId,
//                $this->aliasField('education_grade_id') => $educationGradeId,
//                $this->aliasField('education_grade_id') => $educationGradeId,
//                $this->aliasField('institution_id') => $institutionId,
//            ])
//            ->group([
//                $this->aliasField('student_id'),
//                $this->aliasField('assessment_id'),
//                $this->aliasField('education_subject_id')
//            ])
//            ->first();
//
//        return $totalMarks;
//    }
//

    /**
     * API to get student's subject assessment results data
     * @return json
     * @ticket POCOR-6806 starts
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     */
    public function findAssessmentGradesOptions(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $assessmentGradingOptionId = $options['assessment_grading_option_id'];
        $educationGradeId = $options['education_grade_id'];
        $educationSubjectId = $options['education_subject_id'];
        $studentId = $options['student_id'];
        $assessmentId = $options['assessment_id'];
        $assessmentPeriodId = $options['assessment_period_id'];
        $institutionId = $options['institution_id'];
        $optionalCondition = [];
        if (!empty($assessmentId)) {
            $optionalCondition[$this->aliasField('assessment_period_id')] = $assessmentId;
        }
        if (!empty($institutionId)) {
            $optionalCondition[$this->aliasField('institution_id')] = $institutionId;
        }
        if (!empty($assessmentPeriodId)) {
            $optionalCondition[$this->aliasField('assessment_period_id')] = $assessmentPeriodId;
        }
        if (!empty($assessmentGradingOptionId)) {
            $optionalCondition[$this->aliasField('assessment_grading_option_id')] = $assessmentGradingOptionId;
        }

        $getRecord = $this->find()
            ->select([
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'assessment_grading_option_id' => $this->aliasField('assessment_grading_option_id'),
                'assessment_id' => $this->aliasField('assessment_id '),
                'assessment_period_id' => $this->aliasField('assessment_period_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'education_subject_id' => $this->aliasField('education_subject_id'),
                'institution_id' => $this->aliasField('institution_id'),
                'marks' => $this->aliasField('marks'),
                'student_id' => $this->aliasField('student_id')
            ])
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('education_subject_id') => $educationSubjectId,
                $this->aliasField('student_id') => $studentId,
                $optionalCondition
            ])
            //->orWhere([$optionalCondition])
            ->hydrate(false)
            ->toArray();
        $response['result'] = $getRecord;
        $response['message'] = 'Successful Operation';
        $dataArr = array("data" => $response);
        echo json_encode($dataArr);
        exit;
    }
    /** POCOR-6806 ends */

    /**
     * Custom validation
     * This function will validate whether the mandatory fields has exist or not
     * @return json
     * @ticket - POCOR-6912
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     */
    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {//POCOR-5227 only `if` condition use for this issue, not affected poonam's work on POCOR-6912
            $url = $_SERVER['REQUEST_URI'];
            $url_components = parse_url($url);
            parse_str($url_components['query'], $params);
            $action = array_key_exists('_finder', $params);
            $actionName = strtok($params['_finder'], '[');//POCOR-6921- updated exact action name condition
            if ($primary && $actionName == 'AssessmentGradesOptions') {
                $param = preg_match_all('/\\[(.*?)\\]/', $params['_finder'], $matches);
                $paramsString = $matches[1];
                $paramsArray = explode(';', $paramsString[0]);
                if (empty($paramsArray[0]) || empty($paramsArray[1]) || empty($paramsArray[2]) || empty($paramsArray[3])) {
                    $response['result'] = [];
                    $response['message'] = "Mandatory field can't empty";
                    $dataArr = array("data" => $response);
                    echo json_encode($dataArr);
                    exit;
                }
            }
        }
    }

    /**POCOR-6912 ends*/

    /*
     * $assessmentItemResults = Cake\ORM\TableRegistry::get('Assessment.AssessmentItemResults');
     * $options = ["student_id" => 45, "academic_period_id" => 32, "education_grade_id" => 189, "education_subject_id" => 60];
     * $mark = $assessmentItemResults::getLastMark($options);
     */

    /**
     * @param $options
     * @return float|null
     */
    public static function getLastMark($options, $archive=false)
    {
//        echo('$options');
//        Log::write('debug', $options);
        $id = $options['id'];
        $student_id = self::getFromArray($options,'student_id');
        $academic_period_id = self::getFromArray($options,'academic_period_id');
        $education_grade_id = self::getFromArray($options,'education_grade_id');
        if($education_grade_id == null){
            $education_grade_id = self::getFromArray($options, 'grade_id');
        }
        $education_subject_id = self::getFromArray($options,'education_subject_id');
        $assessment_id = self::getFromArray($options, 'assessment_id');
        $assessment_period_id = self::getFromArray($options, 'assessment_period_id');
        $assessment_grading_option_id = self::getFromArray($options, 'assessment_grading_option_id');
        $institution_id = self::getFromArray($options, 'institution_id');
        $institution_classes_id = self::getFromArray($options, 'institution_class_id');
        if($institution_classes_id == null){
            $institution_classes_id = self::getFromArray($options, 'class_id');
        }
        if($institution_classes_id == null){
            $institution_classes_id = self::getFromArray($options, 'institution_classes_id');
        }
        $select = 'all_results.marks, ';
        if ($id) {
            $select = $select . 'all_results.id, ';
        }
        if ($student_id) {
            $select = $select . 'all_results.student_id, ';
        }
        if ($academic_period_id) {
            $select = $select . 'all_results.academic_period_id, ';
        }
        if ($education_grade_id) {
            $select = $select . 'all_results.education_grade_id, ';
        }
        if ($education_subject_id) {
            $select = $select . 'all_results.education_subject_id, ';
        }
        if ($assessment_id) {
            $select = $select . 'all_results.assessment_id, ';
        }
        if ($assessment_period_id) {
            $select = $select . 'all_results.assessment_period_id, ';
        }
        if ($assessment_grading_option_id) {
            $select = $select . 'all_results.assessment_grading_option_id, ';
        }
        if ($institution_id) {
            $select = $select . 'all_results.institution_id, ';
        }
        if ($institution_classes_id) {
            $select = $select . 'all_results.institution_classes_id, ';
        }
        $where = "WHERE 1 = 1";
        if ($id > 0) {
            $where = $where . " AND latest_grades.id = $id ";
        }
        if ($student_id > 0) {
            $where = $where . " AND latest_grades.student_id = $student_id ";
        }
        if ($academic_period_id > 0) {
            $where = $where . " AND latest_grades.academic_period_id = $academic_period_id ";
        }
        if ($education_grade_id > 0) {
            $where = $where . " AND latest_grades.education_grade_id = $education_grade_id ";
        }
        if ($education_subject_id > 0) {
            $where = $where . " AND latest_grades.education_subject_id = $education_subject_id ";
        }
        if ($assessment_id > 0) {
            $where = $where . " AND latest_grades.assessment_id = $assessment_id ";
        }
        if ($assessment_period_id > 0) {
            $where = $where . " AND latest_grades.assessment_period_id = $assessment_period_id ";
        }
        if ($assessment_grading_option_id > 0) {
            $where = $where . " AND latest_grades.assessment_grading_option_id = $assessment_grading_option_id ";
        }
        if ($institution_id > 0) {
            $where = $where . " AND latest_grades.institution_id = $institution_id ";
        }
        if ($institution_classes_id > 0) {
            $where = $where . " AND latest_grades.institution_classes_id = $institution_classes_id";
        }

        $select = rtrim($select, ', ');
        $assessment_item_results_table_name = 'assessment_item_results';
        $connection_name = 'default';
        if($archive){
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($assessment_item_results_table_name);
            $assessment_item_results_table_name = $archiveTableAndConnection[0];
            $connection_name = $archiveTableAndConnection[1];
        }
        $sql = "SELECT $select
FROM $assessment_item_results_table_name all_results
INNER JOIN
(
    SELECT latest_grades.student_id
        ,latest_grades.assessment_id
        ,latest_grades.education_subject_id
        ,latest_grades.assessment_period_id
        ,MAX(latest_grades.created) latest_created
    FROM $assessment_item_results_table_name latest_grades
    $where    
    GROUP BY latest_grades.student_id
        ,latest_grades.assessment_id
        ,latest_grades.education_subject_id
        ,latest_grades.assessment_period_id
) latest_grades
ON latest_grades.student_id = all_results.student_id
AND latest_grades.assessment_id = all_results.assessment_id
AND latest_grades.education_subject_id = all_results.education_subject_id
AND latest_grades.assessment_period_id = all_results.assessment_period_id
AND latest_grades.latest_created = all_results.created

GROUP BY all_results.student_id
    ,all_results.assessment_id
    ,all_results.education_subject_id
    ,all_results.assessment_period_id";
//        Log::write('debug', 'marks_sql');
//        Log::write('debug', $sql);
//        echo $sql;
        $connection = ConnectionManager::get($connection_name);
        $marks = $connection->execute($sql)->fetchAll('assoc');
        if (isset($marks)) {
            return $marks;
        }
        return null;

    }

    private static function getFromArray($options, $field)
    {
        return isset($options[$field]) ? $options[$field] : null;
    }

    public static function getLastMarkForInstitutionResults($options)
    {
//        return $options;
        $academic_period_id = $options['academic_period_id'];
        $education_grade_id = $options['education_grade_id'];
        $education_subject_id = $options['education_subject_id'];
        $student_id = $options['student_id'];
        $sql = "SELECT assessment_item_results.marks
FROM assessment_item_results
INNER JOIN
(
    SELECT assessment_item_results.student_id
        ,assessment_item_results.assessment_id
        ,assessment_item_results.education_subject_id
        ,assessment_item_results.assessment_period_id
        ,MAX(assessment_item_results.created) latest_created
    FROM assessment_item_results
    WHERE assessment_item_results.academic_period_id = $academic_period_id
    AND assessment_item_results.education_grade_id = $education_grade_id
    AND assessment_item_results.education_subject_id = $education_subject_id
    AND assessment_item_results.student_id = $student_id    
    GROUP BY assessment_item_results.student_id
        ,assessment_item_results.assessment_id
        ,assessment_item_results.education_subject_id
        ,assessment_item_results.assessment_period_id
) latest_grades
ON latest_grades.student_id = assessment_item_results.student_id
AND latest_grades.assessment_id = assessment_item_results.assessment_id
AND latest_grades.education_subject_id = assessment_item_results.education_subject_id
AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id
AND latest_grades.latest_created = assessment_item_results.created

GROUP BY assessment_item_results.student_id
    ,assessment_item_results.assessment_id
    ,assessment_item_results.education_subject_id
    ,assessment_item_results.assessment_period_id";
        $connection = ConnectionManager::get('default');
        $marks = $connection->execute($sql)->fetch('assoc');
        if (isset($marks['marks'])) {
            return floatval($marks['marks']);
        }
        return null;

    }
}

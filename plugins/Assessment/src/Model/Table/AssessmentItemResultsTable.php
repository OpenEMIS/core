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
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Cake\Core\Configure;
use Cake\Log\Log;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use Cake\ORM\Table; //POCOR-8224
use Cake\Utility\Inflector; //POCOR-8224
use Cake\Http\Session;
use Cake\Http\ServerRequest;

class AssessmentItemResultsTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config): void
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
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

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //POCOR-6824 start
        $institutionId = $entity->institution_id;
        $InstitutionClassId = $entity->institution_classes_id;
        $institutionClass = self::getDynamicTableInstance('Institution.InstitutionClasses'); //POCOR-8224
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
            $institutionStudents = self::getDynamicTableInstance('Institution.InstitutionStudents'); //POCOR-8224
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
                    //POCOR-7536-KH
                    //AS the ID is not the KEY do shadow save and delete new entity
                    $assessmentItemResults = self::getDynamicTableInstance('Assessment.AssessmentItemResults'); //POCOR-8224
                    $previousAssessment = $assessmentItemResults->find()
                        ->where([
                            $assessmentItemResults->aliasField('student_id') => $entity->student_id,
                            $assessmentItemResults->aliasField('academic_period_id') => $entity->academic_period_id,
                            $assessmentItemResults->aliasField('education_grade_id') => $entity->education_grade_id,
                            $assessmentItemResults->aliasField('assessment_period_id') => $entity->assessment_period_id,
                            $assessmentItemResults->aliasField('assessment_id') => $entity->assessment_id,
                            $assessmentItemResults->aliasField('education_subject_id') => $entity->education_subject_id,
                            $assessmentItemResults->aliasField('institution_classes_id') => $entity->institution_classes_id,//POCOR-9184
                        ])
                        ->order([ //POCOR-7580-KH
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
                        //POCOR-7536-KH
                        $this->getAssessmentGrading($previousAssessment);
                        $event->stopPropagation();
                    } else {
                        $entity->id = Text::uuid();
                    }
                }

                $this->getAssessmentGrading($entity);
            }
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // delete record if user removes the mark or grading
        $marks = $entity->marks;
        $grading = $entity->assessment_grading_option_id;
        if (is_null($marks) && is_null($grading)) {
            $this->delete($entity);
        }

        $listeners = [
            self::getDynamicTableInstance('Institution.InstitutionSubjectStudents') //POCOR-8224
        ];

        $this->dispatchEventToModels('Model.AssessmentResults.afterSave', [$entity], $this, $listeners);
    }

        public function findResults(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        //$controller = $options['_controller'];
        // Ensure $controller and $controller->request are set
        // if (isset($controller) && isset($controller->request)) {
        //     $session = $controller->request->session();
        //     $institutionId = $session->read('Institution.Institutions.id'); // POCOR-6823
        // }
        // //$session = $controller->request->session();
        // //$institutionId = $session->read('Institution.Institutions.id'); //POCOR-6823
        // $studentId = -1;
        // if ($session->check('Student.Results.student_id')) {
        //     $studentId = $session->read('Student.Results.student_id');
        // } else {
        //     $studentId = $session->read('Profile.StudentUser.primaryKey.id');
        // }

        // if ($options['user']['is_student'] == 1) {
        //     $studentId = $options['user']['id'];
        // }
        
        //POCOR-9637 -- start
        $controller = $options['_controller'] ?? null;
        $studentId = $options['student_id'] ?? null;
        $institutionId = $options['institution_id'] ?? null;
        //POCOR-9637 -- end

        //Start POCOR-6823
        $InstitutionClassStudents = self::getDynamicTableInstance('Institution.InstitutionClassStudents'); //POCOR-8224
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

        if (!$academicPeriodId || !$studentId) {
            throw new \Exception("Missing required params");
        }

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
            ]);
            // ->all(); //POCOR-9637 commenting because it returned resultset instead of query object.
           //->first(); //POCOR-6948 Comment Reason: taking one record of assessment instead of all records for student.
    }

    /**
     * POCOR-8224 refactured
     * Retrieves assessment item results for a student.
     *
     * @param int $academicPeriodId
     * @param int $assessmentId
     * @param int $subjectId
     * @param int $studentId
     * @param int $classId
     * @return array
     * @throws \Exception
     */
    public function getAssessmentItemResults($academicPeriodId, $assessmentId, $subjectId, $studentId, $classId): array
    {
        $SubjectStudents = self::getDynamicTableInstance('institution_subject_students');


        $query = $this->find('all')
            ->select([
                'grade_name' => 'AssessmentGradingOptions.name',
                'grade_code' => 'AssessmentGradingOptions.code',
                $this->aliasField('student_id'),
                $this->aliasField('assessment_period_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('assessment_id'),
            ])
            ->contain(['AssessmentGradingOptions'])
            ->innerJoin([$SubjectStudents->getAlias() => $SubjectStudents->getTable()], [
                $SubjectStudents->aliasField('student_id') . ' = ' . $this->aliasField('student_id'),
                $SubjectStudents->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id'),
                $SubjectStudents->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id'),
                $SubjectStudents->aliasField('education_grade_id') . ' = ' . $this->aliasField('education_grade_id'),
                $SubjectStudents->aliasField('education_subject_id') . ' = ' . $this->aliasField('education_subject_id')
            ])
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('assessment_id IS') => $assessmentId,
                $this->aliasField('education_subject_id IS') => $subjectId,
                $this->aliasField('student_id IS') => $studentId,
            ])
            ->group([$this->aliasField('assessment_period_id')])
            ->disableHydration();
        $results = $query->toArray();
        // Step 2: Fetch marks for students using getMarksForClass
        if(!empty($classId)){
            //can get institution
        }
        $options = [
            "academic_period_id" => $academicPeriodId,
//            "institution_id" => $this->aliasField('institution_id'),
            "class_id" => $classId,
            "assessment_id" => $assessmentId,
            "education_subject_id" => $subjectId,
            "student_id" => $studentId
        ];
        $marks = self::getMarksForClass($options);
        // Step 3: Calculate simple marks using getMarksWithSimpleMarks
        if(!is_array($marks)){
            $marks = [];
        }
        $marksWithSimpleMarks = self::getMarksWithSimpleMarks($marks);
        // Step 4: Group marks per student and subject using getMarksPerStudentPerSubjectArray
        $marksPerStudent = self::getMarksPerStudentPerSubjectArray($marksWithSimpleMarks);
        // Step 5: Process the results and add marks
        $returnArray = [];
        foreach ($results as $result) {
            $studentId = $result['student_id'];
            $assessmentPeriodId = $result['assessment_period_id'];
            $marks = $marksPerStudent[$studentId][$subjectId][$assessmentPeriodId] ?? [];
            // Sum the marks and round
            $totalMarks = array_sum(array_column($marks, 'simple_mark'));
            $result['marks'] = round($totalMarks, 2);
            // Structure the return array
            $returnArray[$studentId][$subjectId][$assessmentPeriodId] = [
                'marks' => $result['marks'],
                'grade_name' => $result['grade_name'],
                'grade_code' => $result['grade_code']
            ];
        }
        return $returnArray;
    }

    // result criteria for indexes will be hide for now.
    // public function institutionStudentIndexCalculateIndexValue(EventInterface $event, ArrayObject $params)
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

            $AssessmentItemsGradingTypes = self::getDynamicTableInstance('Assessment.AssessmentItemsGradingTypes'); //POCOR-8224
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

    /**
     * Evaluates the grading type and determines the appropriate grading option for a given assessment mark.
     *
     * This method checks the grading type (MARKS, GRADES, DURATION) associated with the assessment item
     * and matches the mark against the defined grading options. It sets the `assessment_grading_option_id`
     * and attaches the related `assessment_grading_option` and `assessment_grading_type` entities to the provided entity.
     *
     * POCOR-9143: Ensures grading logic is applied consistently across all assessments.
     *
     * @param \Cake\ORM\Entity $entity Entity containing `marks`, `assessment_id`, `assessment_period_id`, and `education_subject_id`
     * @return \Cake\ORM\Entity The updated entity with `assessment_grading_option`, `assessment_grading_option_id`, and `assessment_grading_type` set
     *
<<<<<<< HEAD
     *
=======

>>>>>>> 30c1e730a8ff7bbb59a0ed44166ad027a97a39da
     */
    public static function evaluateGradingForMarks(Entity $entity): Entity
    {
        $educationSubjectId = $entity->education_subject_id;
        $assessmentId = $entity->assessment_id;
        $assessmentPeriodId = $entity->assessment_period_id;

        $AssessmentItemsGradingTypes = self::getDynamicTableInstance('Assessment.AssessmentItemsGradingTypes');
        $assessmentItemsGradingTypeEntity = $AssessmentItemsGradingTypes
            ->find()
            ->contain('AssessmentGradingTypes.GradingOptions')
            ->where([
                $AssessmentItemsGradingTypes->aliasField('education_subject_id') => $educationSubjectId,
                $AssessmentItemsGradingTypes->aliasField('assessment_id') => $assessmentId,
                $AssessmentItemsGradingTypes->aliasField('assessment_period_id') => $assessmentPeriodId
            ])
            ->first();

        if ($assessmentItemsGradingTypeEntity?->assessment_grading_type) {
            $gradingType = $assessmentItemsGradingTypeEntity->assessment_grading_type;
            $entity->set('assessment_grading_type', $gradingType);

            if (in_array($gradingType->result_type, ['MARKS', 'DURATION']) &&
                !empty($gradingType->grading_options)) {
                foreach ($gradingType->grading_options as $gradingOptionObj) {
                    if ($entity->marks >= $gradingOptionObj->min && $entity->marks <= $gradingOptionObj->max) {
                        $entity->set('assessment_grading_option', $gradingOptionObj);
                        $entity->set('assessment_grading_option_id', $gradingOptionObj->id);
                        break;
                    }
                }
            }
        }

        return $entity;
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
            $assessmentItemResults = self::getDynamicTableInstance('Assessment.AssessmentItemResults'); //POCOR-8224
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
            ->disableHydration() // POCOR-8533
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
    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, $primary)
    {
        if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {//POCOR-5227 only `if` condition use for this issue, not affected poonam's work on POCOR-6912
            $url = $_SERVER['REQUEST_URI'];
            $url_components = parse_url($url);
            parse_str($url_components['query'], $params);
            $action = isset($params['_finder']);
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
     * $assessmentItemResults = Cake\ORM\TableRegistry::getTableLocator()->get('Assessment.AssessmentItemResults');
     * $options = ["student_id" => 45, "academic_period_id" => 32, "education_grade_id" => 189, "education_subject_id" => 60];
     * $mark = $assessmentItemResults::getLastMark($options);
     */

    /**
     * @param $options
     * @param $archive
     * @return array
     * @throws \Exception //POCOR-8224
     */
    public static function getClassAssessmentItemResults($options, $archive=false) //POCOR-8224
    {
        $marks = self::getMarksForClass($options, $archive);
        //POCOR-8224 start
        if (!is_array($marks)) {
            $marks = [];
        }
        //POCOR-8224 end
        $marksWithSubjectClassificationWeight = self::getMarksWithSimpleMarks($marks);
        $marksPerStudent = self::getMarksPerStudentPerSubjectArray($marksWithSubjectClassificationWeight);
        return $marksPerStudent;
    }

    /**
     * POCOR-8224
     * @param $options
     * @return array
     */
    public static function getClassExemptions($options): array
    {
        $exemptions_array = self::getLastExemptions($options);

        $exemptions = [];
        foreach ($exemptions_array as $exemption) {
            $student_id = $exemption['student_id'];
            $education_subject_id = $exemption['education_subject_id'];
            $assessment_period_id = $exemption['assessment_period_id'];
            if (!isset($exemptions[$student_id])) {
                $exemptions[$student_id] = [];
            }
            if (!isset($exemptions[$student_id][$education_subject_id])) {
                $exemptions[$student_id][$education_subject_id] = [];
            }
            if (isset($assessment_period_id)) {
                //POCOR-9042 starts
                if($exemption['type'] == 1){
                    $exemptions[$student_id][$education_subject_id][$assessment_period_id] = 'EXEMPT';
                }else{
                    $exemptions[$student_id][$education_subject_id][$assessment_period_id] = 'UNASSIGN';
                }
                //POCOR-9042 ends
            }
        }
        return $exemptions;
    }


    /**
     * @param array $params
     * @param bool $archive
     * @return array|null // POCOR-8224
     * @throws \Exception // POCOR-8224
     */

    private static function getMarksForClass(array $params, bool|null $archive = false): ?array //POCOR-8224
    {
        $academic_period_id = self::getFromArray($params, 'academic_period_id');
        $institution_id = self::getFromArray($params, 'institution_id');
        $institution_class_id = self::getFromArray($params, 'class_id');
        if (!$institution_class_id) {
            $institution_class_id = self::getFromArray($params, 'institution_class_id');
        }
        if (!$institution_class_id) {
            $institution_class_id = self::getFromArray($params, 'institution_classes_id');

        }
        $assessment_id = self::getFromArray($params, 'assessment_id');
        $education_grade_id = self::getFromArray($params, 'grade_id');
        $student_id = self::getFromArray($params, 'student_id');
        $education_subject_id = self::getFromArray($params, 'education_subject_id');
        if (!$education_subject_id) {
            $education_subject_id = -1;
        }
        $assessment_period_id = self::getFromArray($params, 'assessment_period_id');
        if (!$assessment_period_id) {
            $assessment_period_id = -1;
        }
        $assessment_grading_option_id = self::getFromArray($params, 'assessment_grading_option_id');
        if (!$assessment_grading_option_id) {
            $assessment_grading_option_id = -1;
        }
        if (!$education_grade_id) {
            $education_grade_id = -1;
        }
        if (!$academic_period_id) {
            $academic_period_id = -1;
        }
        if (!$institution_id) {
            $institution_id = -1;
        }
        if (!$assessment_id ) {
            $assessment_id = -1;
        }
        if (!$student_id) {
            $student_id = -1;
        }
        $id = -1;
        $options = ["student_id" => $student_id,
            "institution_id" => $institution_id,
            "institution_class_id" => $institution_class_id,
            "academic_period_id" => $academic_period_id,
            "education_grade_id" => $education_grade_id,
            "education_subject_id" => $education_subject_id,
            "id" => $id,
            'assessment_grading_option_id' => $assessment_grading_option_id,
            "assessment_period_id" => $assessment_period_id,
            'assessment_id' => $assessment_id,
            'archive' => $archive];
        $marks = self::getLastMark($options);
        return $marks;
    }

    /**
     * @param array $marksWithSubjectClassificationWeight
     * @return array
     */
    private static function getMarksPerStudentPerSubjectArray(array $marksWithSubjectClassificationWeight): array
    {
        $marksPerStudent = [];
        foreach ($marksWithSubjectClassificationWeight as $record) {

            $studentId = $record['student_id'];
            $assessment_period_id = $record['assessment_period_id'];
            $subject_id = $record['education_subject_id'];
            //POCOR-8224 start
            if(!isset($marksPerStudent[$studentId])){
                $marksPerStudent[$studentId] = [];
            }
            if(!isset($marksPerStudent[$studentId][$subject_id])){
                $marksPerStudent[$studentId][$subject_id] = [];
            }
            //POCOR-8224 end
            $marksPerStudent[$studentId][$subject_id][$assessment_period_id][] = $record;
        }
        return $marksPerStudent;
    }

    /**
     * @param array $marks
     * @return array
     */
    private static function getMarksWithSimpleMarks(array $marks): array //POCOR-8224
    {
        $new_marks = [];
        foreach ($marks as $mark) {
            if (is_numeric($mark['marks'])) { //POCOR-8224
                $simple_mark = floatval($mark['marks']);
            } else { //POCOR-8224
                $simple_mark = $mark['marks'];
            }
            $mark['simple_mark'] = $simple_mark;
            $new_marks[] = $mark;
        }
//        }
        return $new_marks;
    }

    /**
     * //POCOR-8224 refactured
     * @param $options
     * @return |null
     * @throws \Exception
     */
    
    public static function getLastMark($options): false|array //POCOR-8224
    {
        $id = self::getFromArray($options, 'id');
        $studentId = self::getFromArray($options, 'student_id');
        $archive = self::getFromArray($options, 'archive');
        $academicPeriodId = self::getFromArray($options, 'academic_period_id');
        $educationGradeId = self::getGradeId($options);
        $educationSubjectId = self::getFromArray($options, 'education_subject_id');
        $assessmentId = self::getFromArray($options, 'assessment_id');
        $assessmentPeriodId = self::getFromArray($options, 'assessment_period_id');
        $assessmentGradingOptionId = self::getFromArray($options, 'assessment_grading_option_id');
        $institutionId = self::getFromArray($options, 'institution_id');
        $institutionClassesId = self::getClassId($options);

        $selectFields = self::buildSelectFields($options);
        //POCOR-9568
        $whereConditions = [
            'id' => $id,
            'student_id' => $studentId,
            'academic_period_id' => $academicPeriodId,
            'education_grade_id' => $educationGradeId,
            'education_subject_id' => $educationSubjectId,
            'assessment_id' => $assessmentId,
            'assessment_period_id' => $assessmentPeriodId,
            'assessment_grading_option_id' => $assessmentGradingOptionId,
        ];

        if (!empty($institutionId)) {
            $whereConditions['institution_id'] = $institutionId;
        }

        $lastMarkWhere = self::buildWhereClauses($whereConditions); //POCOR-9568

        // $institutionClassStudentsWhere = self::buildInstitutionClassStudentsWhere(
        //     $academicPeriodId,
        //     $educationGradeId,
        //     $institutionId,
        //     $institutionClassesId
        // );

        list($tableName, $connectionName) = self::getArchiveTableAndConnection(
            $archive,
            'assessment_item_results'
        );

        $sql = sprintf(
            "SELECT %s
            FROM %s all_results
            INNER JOIN (
                SELECT
                    latest_grades.student_id,
                    latest_grades.assessment_id,
                    latest_grades.education_subject_id,
                    latest_grades.assessment_period_id,
                    latest_grades.institution_id,
                    MAX(latest_grades.created) latest_created
                FROM %s latest_grades
                %s
                GROUP BY
                    latest_grades.student_id,
                    latest_grades.assessment_id,
                    latest_grades.education_subject_id,
                    latest_grades.assessment_period_id,
                    latest_grades.institution_id
            ) latest_grades
            ON latest_grades.student_id = all_results.student_id
            AND latest_grades.assessment_id = all_results.assessment_id
            AND latest_grades.education_subject_id = all_results.education_subject_id
            AND latest_grades.assessment_period_id = all_results.assessment_period_id
            AND latest_grades.institution_id = all_results.institution_id
            AND latest_grades.latest_created = all_results.created
           
            GROUP BY
                all_results.student_id,
                all_results.assessment_id,
                all_results.education_subject_id,
                all_results.assessment_period_id,
                all_results.institution_id",
            $selectFields,
            $tableName,
            $tableName,
            $lastMarkWhere
        );

        $connection = ConnectionManager::get($connectionName);
        $marks = $connection->execute($sql)->fetchAll('assoc');
        return $marks ?: [];
    }


    /** //POCOR-8224
     * // POCOR-7586 refactured to include prev school
     * @param $options
     * @return array
     */
    public static function getLastExemptions($options): array
    {
        $institution_class_id = self::getFromArray($options, 'institution_class_id');
        $institution_id = self::getFromArray($options, 'institution_id');
        $academic_period_id = self::getFromArray($options, 'academic_period_id');

        $assessment_id = self::getFromArray($options, 'assessment_id');
        $education_subject_id = self::getFromArray($options, 'education_subject_id');
        $student_id = self::getFromArray($options, 'student_id');
        $assessment_period_id = self::getFromArray($options, 'assessment_period_id');

        $exemptions_table = self::getDynamicTableInstance('assessment_item_student_exemptions');

        $where = [];

        if ($education_subject_id > 0) {
            $where[] = 'assessment_items.education_subject_id = ' . (int)$education_subject_id;
        }

        if ($assessment_id > 0) {
            $where[] = 'assessment_items.assessment_id = ' . (int)$assessment_id;
        }

        if ($assessment_period_id > 0) {
            $where[] = $exemptions_table->aliasField('assessment_period_id') . ' = ' . (int)$assessment_period_id;
        }

        // Get students from current class/institution
        $studentIds = [];

        if ($student_id > 0) {
            $studentIds = [$student_id];
        } elseif ($institution_class_id > 0 || $institution_id > 0) {
            $studentIds = self::getStudentIdsByClassOrInstitution($institution_id, $institution_class_id, $academic_period_id);
        }

        if (!empty($studentIds)) {
            $where[] = $exemptions_table->aliasField('student_id') . ' IN (' . implode(',', array_map('intval', $studentIds)) . ')';
        } else {
            return []; // No students to fetch exemptions for
        }

        // Query without filtering exemptions by class/grade/institution
        $exemptions_array = $exemptions_table->find('all')
            ->select([
                'student_id' => $exemptions_table->aliasField('student_id'),
                'education_subject_id' => 'assessment_items.education_subject_id',
                'assessment_period_id' => $exemptions_table->aliasField('assessment_period_id'),
                'assessment_id' => $exemptions_table->aliasField('assessment_id'),
                'type' => $exemptions_table->aliasField('type')
            ])
            ->innerJoin(['assessment_items' => 'assessment_items'],
                [$exemptions_table->aliasField('assessment_id') . ' = assessment_items.assessment_id AND ' .
                    $exemptions_table->aliasField('education_subject_id') . ' = assessment_items.education_subject_id'])
            ->where($where)
            ->disableHydration()
            ->toArray();

        return $exemptions_array;
    }

    // POCOR-7586
    private static function getStudentIdsByClassOrInstitution($institutionId, $institutionClassId, $academicPeriodId)
    {
        $connection = ConnectionManager::get('default');
        $params = [];

        $where = ['academic_period_id = :period'];
        $params['period'] = $academicPeriodId;

        if ($institutionClassId > 0) {
            $where[] = 'institution_class_id = :class';
            $params['class'] = $institutionClassId;
        } elseif ($institutionId > 0) {
            $where[] = 'institution_id = :institution';
            $params['institution'] = $institutionId;
        }

        $sql = 'SELECT student_id FROM institution_class_students WHERE ' . implode(' AND ', $where);
        $rows = $connection->execute($sql, $params)->fetchAll('assoc');
        return array_column($rows, 'student_id');
    }

    /**
     * @param $options
     * @param $field
     * //POCOR-8224
     */
    private static function getFromArray($options, $field)
    {
        return $options[$field] ?? null;
    }

    /**
     * @param $options
     * //POCOR-8224
     */
    private static function getGradeId($options)
    {
        return self::getFromArray($options, 'education_grade_id') ?: self::getFromArray($options, 'grade_id');
    }

    /**
     * @param $options
     * //POCOR-8224
     *
     */
    private static function getClassId($options)
    {
        return self::getFromArray($options, 'institution_class_id')
            ?: self::getFromArray($options, 'class_id')
                ?: self::getFromArray($options, 'institution_classes_id');
    }

    /**
     * @param $options
     * @return string
     * POCOR-8224
     */
    private static function buildSelectFields($options): string
    {
        $fields = ['marks'];
        $possibleFields = [
            'id', 'student_id', 'academic_period_id', 'education_grade_id',
            'education_subject_id', 'assessment_id', 'assessment_period_id',
            'assessment_grading_option_id', 'institution_id', 'institution_classes_id'
        ];

        foreach ($possibleFields as $field) {
            if (self::getFromArray($options, $field)) {
                $fields[] = "all_results.$field";
            }
        }

        return implode(', ', $fields);
    }

    /**
     * @param $conditions
     * @return string
     * POCOR-8224
     */
    private static function buildWhereClauses($conditions): string
    {
        $clauses = ["WHERE 1 = 1"];
        foreach ($conditions as $key => $value) {
            if ($value > 0) {
                $clauses[] = "AND latest_grades.$key = $value";
            }
        }
        return implode(' ', $clauses);
    }

    /**
     * @param $academicPeriodId
     * @param $educationGradeId
     * @param $institutionId
     * @param $institutionClassesId
     * @return string
     * POCOR-8224
     */
    private static function buildInstitutionClassStudentsWhere($academicPeriodId, $educationGradeId, $institutionId, $institutionClassesId): string
    {
        $clauses = ['INNER JOIN institution_class_students ON institution_class_students.student_id = all_results.student_id'];

        if ($academicPeriodId > 0) {
            $clauses[] = "AND institution_class_students.academic_period_id = $academicPeriodId";
        }
        if ($educationGradeId > 0) {
            $clauses[] = "AND institution_class_students.education_grade_id = $educationGradeId";
        }
        if ($institutionId > 0) {
            $clauses[] = "AND institution_class_students.institution_id = $institutionId";
        }
        if ($institutionClassesId > 0) {
            $clauses[] = "AND institution_class_students.institution_class_id = $institutionClassesId";
        }

        return implode(' ', $clauses);
    }

    /**
     * @throws \Exception
     * POCOR-8224
     */
    private static function getArchiveTableAndConnection($archive, $tableName): array
    {
        if ($archive) {
            return ArchiveConnections::getArchiveTableAndConnection($tableName);
        }
        return [$tableName, 'default'];
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

    /**
     * POCOR-8224 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $alias, array $options = []): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($alias, $options);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $alias);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($alias);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias, $options);
    }

   public function getAssessmentItemResultsReport($academicPeriodId, $assessmentIds = null, $subjectIds = null, $studentIds = null, $classIds = null): array
    {
        $SubjectStudents = self::getDynamicTableInstance('institution_subject_students');
        $ClassStudents = self::getDynamicTableInstance('institution_class_students');

        // Step 1: Fetch raw results with class_id
        $query = $this->find('all')
            ->select([
                'grade_name' => 'AssessmentGradingOptions.name',
                'grade_code' => 'AssessmentGradingOptions.code',
                $this->aliasField('student_id'),
                $this->aliasField('assessment_period_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('assessment_id'),
                'institution_class_id' => $ClassStudents->aliasField('institution_class_id')
            ])
            ->contain(['AssessmentGradingOptions'])
            ->innerJoin([$SubjectStudents->getAlias() => $SubjectStudents->getTable()], [
                $SubjectStudents->aliasField('student_id') . ' = ' . $this->aliasField('student_id'),
                $SubjectStudents->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id'),
                $SubjectStudents->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id'),
                $SubjectStudents->aliasField('education_grade_id') . ' = ' . $this->aliasField('education_grade_id'),
                $SubjectStudents->aliasField('education_subject_id') . ' = ' . $this->aliasField('education_subject_id')
            ])
            ->leftJoin([$ClassStudents->getAlias() => $ClassStudents->getTable()], [
                $ClassStudents->aliasField('student_id') . ' = ' . $this->aliasField('student_id'),
                $ClassStudents->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id') // Ensures same period
            ])
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->disableHydration();

        if (!empty($assessmentIds)) {
            $query->where([$this->aliasField('assessment_id') . ' IN' => $assessmentIds]);
        }

        if (!empty($subjectIds)) {
            $query->where([$this->aliasField('education_subject_id') . ' IN' => $subjectIds]);
        }

        if (!empty($studentIds)) {
            $query->where([$this->aliasField('student_id') . ' IN' => $studentIds]);
        }

        $results = $query->toArray(); //Now we fetch results

        // Step 2: Preload marks per class
        $marksPerClass = [];

        foreach ($classIds as $classId) {
            $marks = self::getMarksForClass([
                "academic_period_id" => $academicPeriodId,
                "class_id" => $classId
            ]);
            if (!is_array($marks)) {
                $marks = [];
            }

            $marksWithSimpleMarks = self::getMarksWithSimpleMarks($marks);
            $marksPerStudent = self::getMarksPerStudentPerSubjectArray($marksWithSimpleMarks);
            $marksPerClass[$classId] = $marksPerStudent;
        }

        // Step 3: Process result rows
        $returnArray = [];

        foreach ($results as $result) {
            $studentId = $result['student_id'];
            $subjectId = $result['education_subject_id'];
            $assessmentPeriodId = $result['assessment_period_id'];
            $classId = $result['institution_class_id'];

            if (empty($classId)) {
                continue; // Skip if classId is not present
            }

            $marks = $marksPerClass[$classId][$studentId][$subjectId][$assessmentPeriodId] ?? [];

            $totalMarks = array_sum(array_column($marks, 'simple_mark'));
            $result['marks'] = round($totalMarks, 2);

            $returnArray[$studentId][$subjectId][$assessmentPeriodId] = [
                'marks' => $result['marks'],
                'grade_name' => $result['grade_name'],
                'grade_code' => $result['grade_code'],
                'assessments' => $marks //Include assessments here
            ];
        }


        return $returnArray;
    }

    //POCOR-9477
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (!empty($data['student_id'])) {

            $AssessmentItemResults = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemResults');
            $existing = $AssessmentItemResults->find()
                ->select(['marks', 'assessment_grading_option_id'])
                ->where([
                    'student_id' => $data['student_id'],
                    'assessment_id' => $data['assessment_id'],
                    'education_subject_id' => $data['education_subject_id'],
                    'education_grade_id' => $data['education_grade_id'],
                    'academic_period_id' => $data['academic_period_id'],
                    'institution_classes_id' => $data['institution_classes_id'],
                    'institution_id' => $data['institution_id'],
                    'assessment_period_id' => $data['assessment_period_id'],
                ])
                ->enableHydration(false)
                ->first();
            if ($existing) {
                $data['_old_marks'] = $existing['marks'];
                $data['_old_grade_option'] = $existing['assessment_grading_option_id'];
            }
        }
    }

    //POCOR-9477
    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $oldMarks = $entity->get('_old_marks');
        $oldOption = $entity->get('_old_grade_option');
    }

    //POCOR-9444
    public function auditAssessmentItemResultsReport($academicPeriodId, $assessmentIds = null, $subjectIds = null, $studentIds = null, $classIds = null): array
    {
        $SubjectStudents = self::getDynamicTableInstance('institution_subject_students');
        $ClassStudents = self::getDynamicTableInstance('institution_class_students');

        //Fetch raw results with class_id
        $query = $this->find('all')
            ->select([
                'grading_option_name' => 'AssessmentGradingOptions.name',
                'grading_option_code' => 'AssessmentGradingOptions.code',
                'assessment_name' => 'Assessments.name',
                'assessment_period_name' => 'AssessmentPeriods.name',
                $this->aliasField('student_id'),
                $this->aliasField('assessment_period_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('assessment_id'),
                'institution_class_id' => $ClassStudents->aliasField('institution_class_id')
            ])
            ->contain(['AssessmentGradingOptions','AssessmentPeriods','Assessments'])
            ->innerJoin([$SubjectStudents->getAlias() => $SubjectStudents->getTable()], [
                $SubjectStudents->aliasField('student_id') . ' = ' . $this->aliasField('student_id'),
                $SubjectStudents->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id'),
                $SubjectStudents->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id'),
                $SubjectStudents->aliasField('education_grade_id') . ' = ' . $this->aliasField('education_grade_id'),
                $SubjectStudents->aliasField('education_subject_id') . ' = ' . $this->aliasField('education_subject_id')
            ])
            ->leftJoin([$ClassStudents->getAlias() => $ClassStudents->getTable()], [
                $ClassStudents->aliasField('student_id') . ' = ' . $this->aliasField('student_id'),
                $ClassStudents->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id')
            ])->where([
                $this->aliasField('academic_period_id IN') => $academicPeriodId
            ])
            ->disableHydration();

        if (!empty($assessmentIds)) {
            $query->where([$this->aliasField('assessment_id') . ' IN' => $assessmentIds]);
        }

        if (!empty($subjectIds)) {
            $query->where([$this->aliasField('education_subject_id') . ' IN' => $subjectIds]);
        }

        if (!empty($studentIds)) {
            $query->where([$this->aliasField('student_id') . ' IN' => $studentIds]);
        }
        if (!empty($classIds)) {
            $query->where([$this->aliasField('institution_classes_id') . ' IN' => $classIds]);
        }

        $results = $query->toArray();
        $marksPerClass = [];

        foreach ($results as $row) {
            $classId = $row['institution_class_id'];
            $academicPeriodIdRow = $row['academic_period_id'];

            if (!$classId || !$academicPeriodIdRow) {
                continue;
            }

            if (isset($marksPerClass[$academicPeriodIdRow][$classId])) {
                continue;
            }

            $marks = self::getMarksForClass([
                'academic_period_id' => $academicPeriodIdRow,
                'class_id' => $classId
            ]);

            if (!is_array($marks)) {
                $marks = [];
            }

            $marksWithSimpleMarks = self::getMarksWithSimpleMarks($marks);
            $marksPerStudent = self::getMarksPerStudentPerSubjectArray($marksWithSimpleMarks);

            $marksPerClass[$academicPeriodIdRow][$classId] = $marksPerStudent;
        }


        //Process result rows
        $returnArray = [];
        foreach ($results as $result)
        {
            $studentId = $result['student_id'];
            $subjectId = $result['education_subject_id'];
            $assessmentPeriodId = $result['assessment_period_id'];
            $classId = $result['institution_class_id'];
            $academicPeriodIdRow = $result['academic_period_id'];

            if (empty($classId) || empty($academicPeriodIdRow)) {
                continue;
            }

            $marks =
                $marksPerClass[$academicPeriodIdRow][$classId][$studentId][$subjectId][$assessmentPeriodId]
                ?? [];

            $totalMarks = array_sum(array_column($marks, 'simple_mark'));

            $returnArray[$studentId][$academicPeriodIdRow][$subjectId][$assessmentPeriodId] = [
                'marks' => round($totalMarks, 2),
                'grade_name' => $result['grade_name'],
                'grade_code' => $result['grade_code'],
                'assessments' => $marks
            ];
        }

        return $returnArray;
    }

}

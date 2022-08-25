<?php
namespace Assessment\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Core\Configure;

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
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }

        $this->getAssessmentGrading($entity);
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
        }else{
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
                $this->aliasField('institution_id') => $institutionId    //POCOR-6823
            ])
            ->order([
                $this->aliasField('created') => 'DESC', //POCOR-6823
                $this->aliasField('modified') => 'DESC', //POCOR-6823
                $this->Assessments->aliasField('code'), $this->Assessments->aliasField('name')
            ])->first();
    }

    /**
     *  Function to get the assessment results based academic period
     *
     *  @param integer $academicPeriodId The academic period id
     *
     *  @return array The assessment results group field - institution id, key field - student id
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
                $this->aliasField('marks'),
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

            $result['marks'] = $assessmentItemResultsData->marks;

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

    public function getTotalMarks($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId,$institutionClassesId, $assessmentPeriodId, $institutionId)
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
                    
                    $sumMarks[] = $assessmentItemResultsData->marks*$result->_matchingData['AssessmentPeriods']->weight; 
            }
            $sumMarks = array_sum($sumMarks);
            return $sumMarks;//POCOR-6479 ends
    }

    /*
    * Function is get the total mark of the subject
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return data
    * @ticket POCOR-6776
    */
    public function getTotalMarksForSubject($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId,$institutionClassesId, $assessmentPeriodId, $institutionId)
    {   
        $query = $this->find();
        $totalMarks = $query
            ->select([
                'calculated_total' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)')
            ])
            ->matching('Assessments')
            ->matching('AssessmentPeriods')
            ->order([
                $this->aliasField('created') => 'DESC'
            ])
            ->where([
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('education_subject_id') => $educationSubjectId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('education_grade_id') => $educationGradeId,
            ])
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('assessment_id'),
                $this->aliasField('education_subject_id')
            ])
            ->first();

        return $totalMarks;
    }

    /** 
    * API to get student's subject assessment results data
    * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
    * @return json
    * @ticket POCOR-6806 starts 
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
        if(!empty($assessmentId)){
            $optionalCondition[$this->aliasField('assessment_period_id')] = $assessmentId;
        }
        if(!empty($institutionId)){
            $optionalCondition[$this->aliasField('institution_id')] = $institutionId;
        }
        if(!empty($assessmentPeriodId)){
            $optionalCondition[$this->aliasField('assessment_period_id')] = $assessmentPeriodId;
        }
        if(!empty($assessmentGradingOptionId)){
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
        echo json_encode($dataArr);exit;
    }
    /** POCOR-6806 ends */ 

    /**
    * Custom validation
    * This function will validate whether the mandatory fields has exist or not
    * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
    * @return json
    * @ticket - POCOR-6912 
    */
    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        
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
                echo json_encode($dataArr);exit;
            }
        }
    }
    /**POCOR-6912 ends*/ 
}

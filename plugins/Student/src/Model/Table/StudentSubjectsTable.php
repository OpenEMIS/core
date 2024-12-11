<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StudentSubjectsTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        $this->setTable('institution_subject_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);

        $this->addBehavior('Restful.RestfulAccessControl');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Subjects' =>['id','institution_subject_id','institution_id']
            ]
        ]);
        // $this->addBehavior('Student.StudentTab', [
        //     'appliedAction' => ['StudentSubjects' =>['id']
        //     ]
        // ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        //$contentHeader = $this->controller->viewVars['contentHeader'];
        $contentHeader = $this->controller->viewBuilder()->getVars()['contentHeader'];
        list($studentName, $module) = explode(' - ', $contentHeader);
        $module = __('Subjects');
        $contentHeader = $studentName . ' - ' . $module;
        $this->controller->set('contentHeader', $contentHeader);
        $this->controller->Navigation->substituteCrumb(__('Student Subjects'), $module);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['student_status_id']['visible'] = false;
        $this->fields['total_mark']['visible'] = false;//POCOR-8435
        $this->fields['outcome_result']['visible'] = false;//POCOR-8435
        $this->field('academic_period_id', ['type' => 'integer', 'order' => 0]);
        $this->field('institution_id', ['type' => 'integer', 'after' => 'academic_period_id']);
        $this->field('total_mark', ['after' => 'institution_subject_id']);//POCOR-8435
        $this->field('result_type', [  'after' => 'institution_subject_id']);//POCOR-8435
        $this->field('final_result', [  'after' => 'result_type']);

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['controls'] = ['name' => 'Student.Subjects/controls', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];

        if (!empty($this->request->getQuery('institution_subject_id'))) {
            $action = 'view';
            $hasAllSubjectsPermission = $this->AccessControl->check(['Institutions', 'AllSubjects', $action]);

            $hasMySubjectsPermission = $this->AccessControl->check(['Institutions', 'Subjects', $action]);
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Subjects',
                'view',
                $this->paramsEncode(['id' => $this->request->getQuery('institution_subject_id'), 'institution_id'=>$this->request->getQuery('institution_id')]),
                 $encodedQueryString,
            ];

            if ($hasAllSubjectsPermission) {
                return $this->controller->redirect($url);
            }

            if ($hasMySubjectsPermission) {
                $userId = $this->Auth->user('id');

                $institutionSubjectStaffTable = TableRegistry::get('Institution.InstitutionSubjectStaff');
                $subjectsTeaching = $institutionSubjectStaffTable->find()
                    ->find('list', ['keyField' => 'subject', 'valueField' => 'subject'])
                    ->select (['subject' => $institutionSubjectStaffTable->aliasField('institution_subject_id'),
                                'id' => $institutionSubjectStaffTable->aliasField('id')])
                    ->where([
                        $institutionSubjectStaffTable->aliasField('staff_id') => $userId,
                    ])
                    ->toArray();

                if (!empty($subjectsTeaching) && array_key_exists($this->request->getQuery('institution_subject_id'),$subjectsTeaching)) {
                    return $this->controller->redirect($url);
                }
            }
            $this->Alert->error('security.noAccess');
        }


   		// Start POCOR-5188
		if($this->request->getParam('controller') == 'Students'){
			$is_manual_exist = $this->getManualUrl('Institutions','Subjects','Students - Academic');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}
		}elseif($this->request->getParam('controller') == 'Directories'){
			$is_manual_exist = $this->getManualUrl('Directory','Subjects','Students - Academic');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}

		}
		// End POCOR-5188

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End
        $queryString = $this->getQueryString();
        //POCOR-8413 starts
        $studentId = $queryString['student_id'];
        if(empty($studentId)){
            $studentId = $this->Session->read('Student.Students.id');
            if(empty($studentId)){
                $studentId = 0;
            }
        }//POCOR-8413 ends
        $encodedQueryString = $this->paramsEncode($queryString);
        // Institution and Grade filter
        $InstitutionStudents = TableRegistry::get('Institution.Students');
        $institutionQuery = $InstitutionStudents->find()
            ->contain(['Institutions', 'StudentStatuses', 'EducationGrades'])
            ->where([
                $InstitutionStudents->aliasField('student_id') => $studentId,
                $InstitutionStudents->aliasField('academic_period_id') => $selectedAcademicPeriod
            ])
            ->order([$InstitutionStudents->aliasField('created') => 'DESC'])
            ->toArray();

        $institutionOptions = [];
        $gradeOptions = [];
        foreach ($institutionQuery as $key => $obj) {
            // only get the latest student status of each institution
            $institutionId = $obj->institution_id;
            if (!isset($institutionOptions[$institutionId])) {
                $institutionOptions[$institutionId] = $obj->institution_student_status;
            }

            // default grade options when no institution is selected
            if ($obj->has('education_grade')) {
                $gradeOptions[$obj->education_grade_id] = $obj->education_grade->name;
            }
        }

        $institutionOptions = ['-1' => __('All Institutions')] + $institutionOptions;
        $selectedInstitution = !is_null($this->request->getQuery('institution_id')) ? $this->request->getQuery('institution_id') : -1;
        $this->controller->set(compact('institutionOptions', 'selectedInstitution'));

        if ($selectedInstitution != -1) {
            $where[$this->aliasField('institution_id')] = $selectedInstitution;

            // get available grades with student status in the selected institution
            $gradeOptions = $InstitutionStudents->find('list', [
                'keyField' => 'education_grade_id',
                'valueField' => 'education_grade_student_status'
            ])
            ->contain(['StudentStatuses', 'EducationGrades'])
            ->where([
                $InstitutionStudents->aliasField('student_id') => $studentId,
                $InstitutionStudents->aliasField('academic_period_id') => $selectedAcademicPeriod,
                $InstitutionStudents->aliasField('institution_id') => $selectedInstitution
            ])
            ->order([$InstitutionStudents->aliasField('created') => 'DESC'])
            ->toArray();
        }

        $gradeOptions = ['-1' => __('All Grades')] + $gradeOptions;
        $selectedGrade = !is_null($this->request->getQuery('education_grade_id')) ? $this->request->getQuery('education_grade_id') : -1;
        $this->controller->set(compact('gradeOptions', 'selectedGrade'));

        if ($selectedGrade != -1) {
            $where['ClassGrades.education_grade_id'] = $selectedGrade;
        }
        // End


        $userData = $this->Session->read();
        $session = $this->request->getSession();//POCOR-6267
        if ($userData['Auth']['User']['is_guardian'] == 1) {
            //$sId = $userData['Student']['ExaminationResults']['student_id'];//POCOR-6267
            //$studentId = $this->ControllerAction->paramsDecode($sId)['id'];//POCOR-6267
            $studentId = $session->read('Student.Students.id');//POCOR-8323
        } else {
            $studentId = $userData['Auth']['User']['id'];
        }

        /*POCOR-6267*/
        if ($userData['Auth']['User']['is_guardian'] == 1) {
            if (!empty($studentId)) {
                $where[$this->aliasField('student_id')] = $studentId;
            }
        } /*POCOR-6267*/else {
            if(!empty($userData['System']['User']['roles']) & !empty($userData['Student']['Students']['id'])) {

            } else {
                if (!empty($studentId)) {
                    $where[$this->aliasField('student_id')] = $studentId;
                }
            }
        }
        //POCOR-6468
        if(isset($userData['Institution']['StudentUser']['primaryKey']['id'])){
            $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');//POCOR-6468 starts
            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $InstitutionClassStudentsQuery = $InstitutionClassStudents->find()
                                ->where([
                                    $InstitutionClassStudents->aliasField('student_id') => $userData['Institution']['StudentUser']['primaryKey']['id'],
                                    $InstitutionClassStudents->aliasField('academic_period_id') => $selectedAcademicPeriod,
                                    $InstitutionClassStudents->aliasField('institution_id') => $selectedInstitution,
                                    $InstitutionClassStudents->aliasField('student_status_id') => $enrolledStatus,
                                ])
                                ->first();
            if($InstitutionClassStudentsQuery){
                $where[$this->aliasField('institution_class_id')] = $InstitutionClassStudentsQuery->institution_class_id;
            }
        }
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        //POCOR-6468
      //  $where[$this->aliasField('student_status_id')] = $enrolledStatus; //POCOR-7111

        $query
            ->matching('InstitutionClasses.ClassGrades')
            ->innerJoin(//POCOR-6468
                [$InstitutionClassStudents->getAlias() => $InstitutionClassStudents->getTable()],
                [
                    $InstitutionClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $InstitutionClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $InstitutionClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $InstitutionClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id')
                ]
            )//POCOR-6468
            //POCOR-6832
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code IN' => ['TRANSFERRED', 'CURRENT', 'GRADUATED', 'PROMOTED', 'REPEATED']]);
            })
            //POCOR-6832
            ->where($where)
            ->group([
                $this->aliasField('education_subject_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_id')
            ]);
    }

    /**
     * get total marks from a common query
     * @param Event $event
     * @param Entity $entity
     * @return float
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * This function is commented as not to show total mark (POCOR-8435)
     */  
    // public function onGetTotalMark(Event $event, Entity $entity)
    // {
    //     // POCOR-7896 start
    //     $sum_results = $entity->total_mark;
    //     return round($sum_results, 2);
    //     // POCOR-7896 end
    // }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view'])) {
            $institutionId = $entity->institution_class->institution_id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Subjects',
                0 => 'view',
                1 =>$encodedQueryString,
                2 =>$this->paramsEncode(['id' => $entity->id]),
                //3 =>$this->paramsEncode(['id' => $entity->institution_class]),
               3 => $this->paramsEncode(['id' => $entity->institution_subject->id]),

            ];

            if ($this->controller->getName() == 'Directories') {
                $queryString['institution_subject_id'] = $entity->institution_subject->id;
                $encodedQueryString = $this->paramsEncode($queryString);
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'StudentSubjects',
                    'index',
                    'type' => 'student',
                    'institution_id' => $queryString['institution_id'],
                    'institution_subject_id' => $entity->institution_subject->id,
                    $encodedQueryString,
                ];
            }

            $buttons['view']['url'] = $url;
        }
        return $buttons;
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options = ['type' => 'student'];
        //$tabElements = $this->controller->getAcademicTabElements($options);
        $tabElements = $this->getAcademicTabElements($options);
        if ($this->controller->getName() == 'GuardianNavs' || $this->controller->getName() == 'Directories') {//POCOR-8323
            $tabElements = $this->controller->getAcademicTabElements($options);
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Subjects');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'institution_id') {
            return __('Institution');
        // } elseif ($field == 'total_mark') {POCOR-8435
        //     return __('	Total Mark');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

        /**
     * Retrieves the "result type" for the given entity based on its associated grade and subject.
     * 
     * This method queries the `EducationGradesSubjects` table to find the "result type" 
     * corresponding to the provided `education_grade_id` and `education_subject_id` of the entity.
     * The retrieved "result type" is then assigned to both the `$entity` and returned in the `$attr` array.
     * 
     * @param Event $event The event object that triggered this method.
     * @param Entity $entity The entity whose "result type" needs to be fetched and updated.
     * 
     * @return void
     */
    public function onGetResultType(Event $event, Entity $entity)
    {
        $EducationGradeSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
        $result = $EducationGradeSubjects
            ->find()
            ->where([
                $EducationGradeSubjects->aliasField('education_grade_id') => $entity->education_grade_id,
                $EducationGradeSubjects->aliasField('education_subject_id') => $entity->education_subject_id,
            ])
            ->first();

        $attr['value'] = $result->result_type;
        $entity->result_type = $result->result_type;
    }

    /**
     * Determines and returns the final result for the given entity based on its "result type."
     * 
     * If the "result type" is "Outcomes," the method returns the `outcome_result` value of the entity.
     * Otherwise, it calculates and returns the `total_mark` rounded to two decimal places.
     * 
     * @param Event $event The event object that triggered this method.
     * @param Entity $entity The entity whose final result needs to be calculated and returned.
     * 
     * @return float|string The final result, either as a rounded total mark or the outcome result.
     */
    public function onGetFinalResult(Event $event, Entity $entity)
    {
        if ($entity->result_type == "Outcomes") {
            return $entity->outcome_result;
        } else {
            return round($entity->total_mark, 2);
        }
    }
     //POCOR-8435 start
    /**
     * Fetches or updates a student's subject outcome result based on the provided criteria.
     * 
     * This method searches for a record in the `InstitutionSubjectStudents` table based on the given 
     * options (`student_id`, `academic_period_id`, `education_subject_id`, `institution_id`, and `education_grade_id`).
     * If an `outcome_result` is provided in the options, the method updates the record's `outcome_result` field.
     * Finally, it retrieves and returns the updated record.
     * 
     * @param Query $query The query object used to build and execute the database query.
     * @param array $options An associative array containing the search criteria and optional outcome result:
     *  - `student_id` (int): The student's ID.
     *  - `academic_period_id` (int): The academic period's ID.
     *  - `education_subject_id` (int): The education subject's ID.
     *  - `institution_id` (int): The institution's ID.
     *  - `education_grade_id` (int): The education grade's ID.
     *  - `outcome_result` (mixed): (Optional) The new outcome result to update.
     * 
     * @return Query The query object containing the final fetched record.
     */
    public function findStudentSubjectOutcomeResult(Query $query, array $options)
    {
        $StudentSubjectData = $query->find('all')->where([
            'student_id' => $options['student_id'],
            'academic_period_id' => $options['academic_period_id'],
            'education_subject_id' => $options['education_subject_id'],
            'institution_id' => $options['institution_id'],
            'education_grade_id' => $options['education_grade_id']
        ])->first();

        // Update the outcome result if provided in the options
        if ($options['outcome_result']) {
            $this->updateAll(['outcome_result' => $options['outcome_result']], ['id' => $StudentSubjectData->id]);
        }

        return $query->find('all')->where(['id' => $StudentSubjectData->id]);
    }
    //POCOR-8435 end
}

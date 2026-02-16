<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;

class IndividualPromotionTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_students');
        parent::initialize($config);

        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->addBehavior('OpenEmis.Section');
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }

        $this->toggle('index', false);
        $this->toggle('view', false);
        $this->toggle('edit', false);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Promotion' =>['id']
            ]
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.reconfirm'] = 'reconfirm';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona)
    {
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
        $Navigation->substituteCrumb('Individual Promotion', 'Students', $url);
        $Navigation->addCrumb('Individual Promotion / Repeat');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->add('effective_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id',  ['excludeFirstDay' => true]],
                'provider' => 'table',
                'on' => function ($context) {
                    $fromAcademicPeriodId = $context['data']['from_academic_period_id'];
                    $toAcademicPeriodId = $context['data']['academic_period_id'];
                    return $fromAcademicPeriodId == $toAcademicPeriodId;
                }
            ]);
        return $validator;
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $hash = $this->request->getQuery('hash');

        if (empty($hash)) {
            // if value is empty, redirect back to the list page
            $event->stopPropagation();
            return $this->controller->redirect(['action' => 'Students', 'index']);
        } else {
            $params = $this->getUrlParams([$this->controller->getName(), $this->getAlias(), 'add'], $hash);
            $extra['params'] = $params; // student_id and user_id in extra
            $extra['redirect'] = [ // url to redirect to StudentUser view
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentUser',
                '0' => 'view',
                //'1' => $this->paramsEncode(['id' => $params['user_id']]),
                '1' => $encodedQueryString,
                'id' => $params['student_id']
            ];
            // back/cancel button
            $extra['toolbarButtons']['back']['url'] = $extra['redirect'];
        }

        $studentId = $extra['params']['student_id'];
        $studentEntity = $this->get($studentId);

        // check transfer requests
        $WorkflowModelsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
        $StudentTransfersTable = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentTransfers');
        $pendingTransferStatuses = $StudentTransfersTable->getStudentTransferWorkflowStatuses('PENDING');

        $conditions = [
            'student_id' => $studentEntity->student_id,
            'status_id IN ' => $pendingTransferStatuses,
            'previous_education_grade_id' => $studentEntity->education_grade_id,
            'previous_institution_id' => $studentEntity->institution_id,
            'previous_academic_period_id' => $studentEntity->academic_period_id
        ];

        $transferCount = $StudentTransfersTable->find()
            ->where($conditions)
            ->count();

        if ($transferCount) {
            $this->Alert->error('IndividualPromotion.pendingTransfer', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($extra['redirect']);
        } else {
            // check withdraw requests
            $StudentWithdrawTable = TableRegistry::getTableLocator()->get('Institution.StudentWithdraw');
            $pendingWithdrawStatus = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentWithdraw', 'PENDING');

            $conditions = [
                'student_id' => $studentEntity->student_id,
                'status_id IN ' => $pendingWithdrawStatus,
                'education_grade_id' => $studentEntity->education_grade_id,
                'institution_id' => $studentEntity->institution_id,
                'academic_period_id' => $studentEntity->academic_period_id,
            ];

            $withdrawCount = $StudentWithdrawTable->find()
             ->where($conditions)
             ->count();

            if ($withdrawCount) {
                $this->Alert->error('IndividualPromotion.pendingWithdraw', ['reset' => true]);
                $event->stopPropagation();
                return $this->controller->redirect($extra['redirect']);
            }
        }

        // populate request data for request
        $this->request->getData()[$this->getAlias()]['institution_id'] = $studentEntity->institution_id;
        $this->request->getData()[$this->getAlias()]['id'] = $studentId;

        $this->setupFields($studentEntity);
    }

    private function setupFields(Entity $data)
    {
        $institutionId = $this->getInstitutionID();
        $this->fields = [];
        $this->field('student_id', ['entity' => $data]);
        $this->field('from_academic_period_id', ['entity' => $data]);
        $this->field('from_education_grade_id', ['entity' => $data]);
        $this->field('student_status_id', ['entity' => $data]);
        $this->field('academic_period_id', ['entity' => $data]);
        $this->field('education_grade_id', ['entity' => $data]);
        $this->field('institution_class_id', ['entity' => $data]);
        $this->field('effective_date', ['entity' => $data]);
        $this->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);

        // sections
        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Promote From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Promote To')]);

        $this->setFieldOrder([
            'student_id',
            'existing_information_header', 'from_academic_period_id', 'from_education_grade_id',
            'new_information_header', 'student_status_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'effective_date']);
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        switch ($this->action) {
            case 'add':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Next');
                $buttons[1]['url'] = $encodedQueryString;
                break;

            case 'reconfirm':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Confirm');
                $buttons[1]['url'] = $this->url('add');
                $buttons[2]['url'] = $encodedQueryString;
                break;
        }
    }

    public function onUpdateFieldStudentId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $studentId = $attr['entity']->student_id;

        $attr['type'] = 'readonly';
        $attr['value'] = $studentId;
        $attr['attr']['value'] = $this->Users->get($studentId)->name_with_id;
        return $attr;
    }

    public function onUpdateFieldFromAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                $academicPeriodId = $attr['entity']->from_academic_period_id;
                break;

            default:
                $academicPeriodId = $attr['entity']->academic_period_id;
                break;
        }

        $attr['type'] = 'readonly';
        $attr['value'] = $academicPeriodId;
        $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
        return $attr;
    }

    public function onUpdateFieldFromEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                $educationGradeId = $attr['entity']->from_education_grade_id;
                break;

            default:
                $educationGradeId = $attr['entity']->education_grade_id;
                break;
        }

        $attr['type'] = 'readonly';
        $attr['value'] = $educationGradeId;
        $attr['attr']['value'] = $this->EducationGrades->get($educationGradeId)->programme_grade_name;
        return $attr;
    }

    public function onUpdateFieldStudentStatusId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $statusNames = $this->StudentStatuses->find('list')->toArray();

        switch ($action) {
            case 'reconfirm':
                $studentStatusId = $attr['entity']->student_status_id;
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = __($statusNames[$studentStatusId]);
                break;

            default:
                $statusOptions = [];
                $statusCodes = $this->StudentStatuses->findCodeList();
                $educationGradeId = $attr['entity']->education_grade_id;
                $nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, false);

                if (count($nextGrades) != 0) {
                    $statusOptions[$statusCodes['PROMOTED']] = __($statusNames[$statusCodes['PROMOTED']]);
                }

                $statusOptions[$statusCodes['REPEATED']] = __($statusNames[$statusCodes['REPEATED']]);

                $attr['options'] = $statusOptions;
                $attr['onChangeReload'] = true;
                break;
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                $academicPeriodId = $attr['entity']->academic_period_id;

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
                break;

            default:
                $fromPeriodId = $attr['entity']->academic_period_id;
                $fromPeriod = $this->AcademicPeriods->get($fromPeriodId);

                // only current and later academic periods will be shown
                $condition = [$this->AcademicPeriods->aliasField('order').' <= ' => $fromPeriod->order];
                $periodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition, 'isEditable' => true]);

                $attr['type'] = 'select';
                $attr['options'] = $periodOptions;
                $attr['onChangeReload'] = true;
                break;
        }

        return $attr;
    }

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                $educationGradeId = $attr['entity']->education_grade_id;
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->EducationGrades->get($educationGradeId)->programme_grade_name;
                break;

            default:
                if (!empty($request->getData()[$this->getAlias()]['student_status_id']) && !empty($request->getData()[$this->getAlias()]['academic_period_id'])) {
                    $studentStatusId = $request->getData()[$this->getAlias()]['student_status_id'];
                    $toAcademicPeriodId = $request->getData()[$this->getAlias()]['academic_period_id'];

                    $institutionId = $attr['entity']->institution_id;
                    $today = date('Y-m-d');


                    // list of grades available in the institution
                    $InstitutionGrades = $this->Institutions->InstitutionGrades;
                    $listOfInstitutionGrades = $InstitutionGrades
                        ->find('list', [
                            'keyField' => 'education_grade_id',
                            'valueField' => 'education_grade.programme_grade_name'])
                        ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                        ->where([
                            'EducationSystems.academic_period_id' => $toAcademicPeriodId,
                            $InstitutionGrades->aliasField('institution_id') => $institutionId,
                            'OR' => [
                                [
                                    $InstitutionGrades->aliasField('end_date IS NULL'),
                                    // $InstitutionGrades->aliasField('start_date <= ') => $today
                                ],
                                [
                                    $InstitutionGrades->aliasField('end_date IS NOT NULL'),
                                    $InstitutionGrades->aliasField('start_date <= ') => $today,
                                    $InstitutionGrades->aliasField('end_date >= ') => $today
                                ]
                            ]
                        ])
                        ->order(['EducationProgrammes.order', 'EducationGrades.order'])
                        ->toArray();


                    $statuses = $this->StudentStatuses->findCodeList();
                    $fromGradeId = $attr['entity']->education_grade_id;
                    $institutionId = $request->getData()[$this->getAlias()]['institution_id'];
                    $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
                    // PROMOTED status
                    if ($studentStatusId == $statuses['PROMOTED']) {
                        $fromAcademicPeriodId = $attr['entity']->academic_period_id;
                        $selectedPeriodId = $request->getData()[$this->getAlias()]['academic_period_id'];
                        $gradeData = $this->EducationGrades->get($fromGradeId);
                        $stageId = $gradeData->education_stage_id;
                        $gradeOrder = $gradeData->order;

                        // list of grades available to repeat
                        $query = $this->EducationGrades
                                ->find()
                                ->select([
                                    $this->EducationGrades->aliasField('order'),
                                    $this->EducationGrades->aliasField('education_programme_id')
                                ])
                                ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                                ->where([
                                    'EducationSystems.academic_period_id' => $selectedPeriodId,
                                    $this->EducationGrades->aliasField('education_stage_id') => $stageId
                                ])->first();
                        if(!empty($query)) {
                            $gradeOrder = $query->order;
                            $programeId = $query->education_programme_id;
                        }
                            $institutionId = $this->getInstitutionID();
                            $query = $this->EducationGrades
                                    ->find('list', [
                                        'keyField' => 'id',
                                        'valueField' => 'programme_grade_name'
                                    ])
                                    ->LeftJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()],[
                                        $this->EducationGrades->aliasField('id').' = ' . $InstitutionGrades->aliasField('education_grade_id')
                                    ])
                                    ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                                    ->where([
                                        'EducationSystems.academic_period_id' => $selectedPeriodId,
                                        $this->EducationGrades->aliasField('order >') => $gradeOrder,
                                        $this->EducationGrades->aliasField('education_programme_id') => $programeId,
                                        $InstitutionGrades->aliasField('institution_id') => $institutionId
                                    ]);
                        $listOfGrades = $query->toArray();
                        $options = ['' => '-- Select --'] + $listOfGrades;
                        $attr['type'] = 'select';
                        $attr['options'] = !empty($options)? $options: [];
                        $attr['onChangeReload'] = true;
                        break;
                    } elseif ($studentStatusId == $statuses['REPEATED']) {
                        $fromAcademicPeriodId = $request->getData()[$this->getAlias()]['from_academic_period_id'];
                        $selectedPeriodId = $request->getData()[$this->getAlias()]['academic_period_id'];
                        $gradeData = $this->EducationGrades->get($fromGradeId);
                        $stageId = $gradeData->education_stage_id;
                        $gradeOrder = $gradeData->order;

                        // list of grades available to repeat
                        $query = $this->EducationGrades
                                ->find()
                                ->select([
                                    $this->EducationGrades->aliasField('order'),
                                    $this->EducationGrades->aliasField('education_programme_id')
                                ])
                                ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                                ->where([
                                    'EducationSystems.academic_period_id' => $selectedPeriodId,
                                    $this->EducationGrades->aliasField('education_stage_id') => $stageId
                                ])->first();
                        if(!empty($query)) {
                            $gradeOrder = $query->order;
                            $programeId = $query->education_programme_id;
                        }
                        //when from academic period same as selected academic period
                        if ($fromAcademicPeriodId == $selectedPeriodId) {
                            $query = $this->EducationGrades
                                    ->find('list', [
                                        'keyField' => 'id',
                                        'valueField' => 'programme_grade_name'
                                    ])
                                    ->LeftJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()],[
                                        $this->EducationGrades->aliasField('id').' = ' . $InstitutionGrades->aliasField('education_grade_id')
                                    ])
                                    ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                                    ->where([
                                        'EducationSystems.academic_period_id' => $selectedPeriodId,
                                        $this->EducationGrades->aliasField('order <=') => $gradeOrder,
                                        $this->EducationGrades->aliasField('education_programme_id') => $programeId,
                                        $InstitutionGrades->aliasField('institution_id') => $institutionId
                                    ]); //POCOR-7330 same grade if status is repeat
                            $listOfGrades = $query->toArray();
                            if (empty($listOfGrades)) {
                                $query = $this->EducationGrades
                                        ->find('list', [
                                            'keyField' => 'id',
                                            'valueField' => 'programme_grade_name'
                                        ])
                                        ->LeftJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()],[
                                            $this->EducationGrades->aliasField('id').' = ' . $InstitutionGrades->aliasField('education_grade_id')
                                        ])
                                        ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                                        ->where([
                                            'EducationSystems.academic_period_id' => $selectedPeriodId,
                                            $this->EducationGrades->aliasField('order <=') => $gradeOrder,
                                            $this->EducationGrades->aliasField('education_programme_id <') => $programeId,
                                            $InstitutionGrades->aliasField('institution_id') => $institutionId
                                        ]);
                            }
                        } else {
                            $query = $this->EducationGrades
                                    ->find('list', [
                                        'keyField' => 'id',
                                        'valueField' => 'programme_grade_name'
                                    ])
                                    ->LeftJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()],[
                                            $this->EducationGrades->aliasField('id').' = ' . $InstitutionGrades->aliasField('education_grade_id')
                                    ])
                                    ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                                    ->where([
                                        'EducationSystems.academic_period_id' => $selectedPeriodId,
                                        $this->EducationGrades->aliasField('order <=') => $gradeOrder,
                                        $this->EducationGrades->aliasField('education_programme_id') => $programeId,
                                        $InstitutionGrades->aliasField('institution_id') => $institutionId
                                    ]);
                        }
                        $listOfGrades = $query->toArray();
                        $options = ['' => '-- Select --'] + $listOfGrades;
                        $attr['type'] = 'select';
                        $attr['options'] = !empty($options)? $options: [];
                        $attr['onChangeReload'] = true;
                    }/*POCOR-6349 ends*/
                }
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                if (!empty($attr['entity']->institution_class_id)) {
                    $classId = $attr['entity']->institution_class_id;

                    $attr['type'] = 'readonly';
                    $attr['attr']['value'] = $this->InstitutionClasses->get($classId)->name;
                } else {
                    $attr['type'] = 'hidden';
                }
                break;

            default:
                $fromAcademicPeriodId = $attr['entity']->academic_period_id;
                $toAcademicPeriodId = (!empty($request->getData()[$this->getAlias()]['academic_period_id']))? $request->getData()[$this->getAlias()]['academic_period_id']: '';

                if (!empty($request->getData()[$this->getAlias()]['education_grade_id'])) {
                    $toGrade = $request->getData()[$this->getAlias()]['education_grade_id'];
                    $institutionId = $attr['entity']->institution_id;
                    $InstitutionClass = $this->InstitutionClasses;
                    $classOptions = $InstitutionClass
                        ->find('list')
                        ->matching('ClassGrades')
                        ->where([$InstitutionClass->aliasField('institution_id') => $institutionId,
                            $InstitutionClass->aliasField('academic_period_id') => $toAcademicPeriodId,
                            'ClassGrades.education_grade_id' => $toGrade])
                        ->order($InstitutionClass->aliasField('name'))
                        ->toArray();

                    $attr['type'] = 'select';
                    $attr['options'] = $classOptions;
                } else {
                    $attr['type'] = 'hidden';
                }
                break;
        }

        return $attr;
    }

    public function onUpdateFieldEffectiveDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                $effectiveDate = $attr['entity']->effective_date;

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $effectiveDate;
                break;

            default:
                if (!empty($request->getData()[$this->getAlias()]['academic_period_id'])) {
                    $toAcademicPeriodId = $request->getData()[$this->getAlias()]['academic_period_id'];
                    $fromAcademicPeriodId = $attr['entity']->academic_period_id;
                    $toPeriodData = $this->AcademicPeriods->get($toAcademicPeriodId);

                    $startDate = $toPeriodData->start_date->format('d-m-Y');
                    $endDate = $toPeriodData->end_date->format('d-m-Y');
                    $withFirstDay = Time::parse($toPeriodData->start_date);
                    $excludeFirstDay = $withFirstDay->modify('+1 day')->format('d-m-Y');

                    if ($toAcademicPeriodId == $fromAcademicPeriodId) {
                        $attr['type'] = 'date';
                        $attr['value'] = Time::now()->format('d-m-Y');
                        $attr['date_options'] = ['startDate' => $excludeFirstDay, 'endDate' => $endDate];
                    } else {
                        // if different academic period chosen, start date is fixed to start date of academic period
                        $attr['type'] = 'readonly';
                        $attr['value'] = $startDate;
                        $attr['attr']['value'] = $startDate;
                    }
                } else {
                    $attr['type'] = 'date';
                }
                break;
        }

        return $attr;
    }

    private function checkIsOverStudentClassCapacity($classId)
    {
        if (!empty($classId)) {
            $institutionClassTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');

            //Query to check if selected student and next class have capacity and return the classes that do not have
            $results = $institutionClassTable->find('all', array('fields' => array('id', 'name'), 'contain' => array()));
            $conditions['OR'] = [];
            $conditions['OR'][] = [
                        $institutionClassTable->aliasField('capacity') . '-' . $institutionClassTable->aliasField('total_male_students') . '-' . $institutionClassTable->aliasField('total_female_students') . ' < :value',
                        $institutionClassTable->aliasField('id =') => $classId
            ];
            $results->bind(':value', 1, "integer");
            $results->where($conditions);
            $overCapacityClass = $results->first();

            if (!empty($overCapacityClass)) {
                $this->Alert->clear();
                $this->Alert->show( 'Next class ' . $overCapacityClass['name'] . ' does not have enough capacity for students.','error',['reset' => true]);
                return true;
            }
        }
        return false;
    }

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        //echo "<pre>"; print_r($entity); die;
        $checkResult = false;
        $process = function ($model, $entity) use ($event, $extra, $requestData) {
            if (empty($entity->getErrors())) {
                if ($entity->has('institution_class_id')) {
                    $checkResult = $this->checkIsOverStudentClassCapacity($entity->institution_class_id);
                }

                if ($checkResult) {
                    $event->stopPropagation();
                    return $this->controller->redirect($this->url('add'));
                } else {
                    // write data to session
                    $this->Session->write($this->getRegistryAlias().'.confirm', $entity);
                    //POCOR-7330 start


                    $educationGradeId = $entity->education_grade_id;
                    $educationGradeName = $this->EducationGrades->get($educationGradeId)->code;
                    $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
                    $studentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
                    $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
                    $EducationGradesData = $EducationGrades->find()
                    ->where([
                        $EducationGrades->aliasField('code') => $educationGradeName
                    ])
                    ->extract('id')
                    ->toArray();
                    $studentId = $entity->student_id;
                    $studentStatusesValidateRepeater = 'no';
                    $studentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
                    $statusStudentId = $studentStatuses->find()->where([$studentStatuses->aliasField('id') => $entity->student_status_id])
                            ->first();
                    $students =  $institutionStudents->find()->where(
                        [
                            $institutionStudents->aliasField('student_id') => $studentId
                        ])
                        ->all();
                    foreach($students AS $studentsData){
                        $educationGradeName1 = $this->EducationGrades->get($studentsData->education_grade_id)->code;
                        if($educationGradeName == $educationGradeName1){
                            if($studentsData->student_status_id == 6 || $studentsData->student_status_id == 7){
                                $studentStatusesValidateRepeater = $studentsData->education_grade_id;
                            }
                        }
                    }
                    $students =  $institutionStudents->find()->where(
                        [
                            $institutionStudents->aliasField('education_grade_id')
                            => $studentStatusesValidateRepeater,
                            $institutionStudents->aliasField('student_id') => $studentId
                        ])
                        ->first();
                    if(empty($students)){
                        $validation = 'no';
                    }else{
                        $validation = 'yes';
                    }
                    // if($statusStudentId->name == 'Repeated'){
                    //     foreach($EducationGradesData AS $EducationGradesDataVal){
                    //         $educationGradeName1 = $this->EducationGrades->get($EducationGradesDataVal)->code;
                    //         // echo "<pre>";print_r($EducationGradesDataVal);die;


                    //         // if($educationGradeName == $educationGradeName1){
                    //             $students =  $institutionStudents->find()->where(
                    //             [
                    //                 $institutionStudents->aliasField('student_id') => $studentId,
                    //                 $institutionStudents->aliasField('education_grade_id') => $EducationGradesDataVal
                    //             ])
                    //             ->first();
                    //             if($students->student_status_id == 6 || $students->student_status_id == 7)
                    //             {
                    //                 $studentStatusesValidateRepeater = 'yes';
                    //             }
                    //         // }
                    //     }
                    // }
                    if($validation == 'yes'){
                        $message = __('This student has completed the education grade before. Please assign to a different grade.');
                        $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                        $event->stopPropagation();
                        return false;
                    }

                    $studentStatusesValidate = $this->studentIfExist($entity, $requestData);
                    if($studentStatusesValidate == 'yes'){
                        $message = __('This student has completed the education grade before. Please assign to a different grade');
                        $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                        $event->stopPropagation();
                        return false;
                    }
                    //POCOR-7330 end
                    $event->stopPropagation();
                    return $this->controller->redirect($this->url('reconfirm'));
                }
            }
        };

        return $process;
    }

    public function reconfirm(EventInterface $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        // retrieve data from session
        $sessionKey = $this->getRegistryAlias() . '.confirm';
        if ($this->Session->check($sessionKey)) {
            $currentEntity = $this->Session->read($sessionKey);
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($this->url('add'));
        }

        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $extra['redirect'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentUser',
            '0' => 'view',
           // '1' => $this->paramsEncode(['id' => $currentEntity->student_id]),
            '1' => $encodedQueryString,
            'id' => $currentEntity->id
        ];

        $this->Alert->info('general.reconfirm', ['reset' => true]);
        $this->setupFields($currentEntity);

        if ($currentEntity && !empty($currentEntity)) {
            $this->controller->set('data', $currentEntity);

            if ($this->request->is(['post', 'put'])) {
                $saveSuccess = $this->savePromotion($currentEntity);

                if ($saveSuccess) {
                    $this->Alert->success($this->aliasField('success'), ['reset' => true]);
                    $this->Session->delete($this->getRegistryAlias());
                    $event->stopPropagation();
                    return $this->controller->redirect($extra['redirect']);
                } else {
                    $this->Alert->error($this->aliasField('savingPromotionError'), ['reset' => true]);
                }
            }
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($this->url('add'));
        }

        return $currentEntity;
    }

    public function savePromotion(Entity $entity)
    {
        $institutionStudentsId = $this->getQueryString('institution_student_id');
        $studentStatusUpdates = TableRegistry::getTableLocator()->get('Institution.StudentStatusUpdates');
        //$id = $entity->id;
        $id = $institutionStudentsId;
        $originalStudent = $this->get($id);
        $studentStatuses = $this->StudentStatuses->findCodeList();
        $statusToUpdate = $entity->student_status_id;
        $fromAcademicPeriodId = $entity->from_academic_period_id;
        $toAcademicPeriodId = $entity->academic_period_id;
        $toPeriodData = $this->AcademicPeriods->get($toAcademicPeriodId);
        $effectiveDate = Time::parse($entity->effective_date);
        $studentStatusId = $studentStatuses['CURRENT'];
        $todayDate = Date::now();
        $todayDate = $todayDate->format('Y-m-d');
        $promoteEffectiveDate = $effectiveDate->format('Y-m-d');

        if($promoteEffectiveDate > $todayDate)
        {
            $studentStatusId = $statusToUpdate;
        }

        // InstitutionStudents: Insert new record
        $studentObj = [];
        $studentObj['student_status_id'] = 1;
        $studentObj['student_id'] = $entity->student_id;
        $studentObj['education_grade_id'] = $entity->education_grade_id;
        $studentObj['academic_period_id'] = $entity->academic_period_id;
        $studentObj['end_date'] = $toPeriodData->end_date;
        $studentObj['end_year']= $toPeriodData->end_year;
        $studentObj['institution_id'] = $entity->institution_id;
        $studentObj['previous_institution_student_id'] = $id;

        // StudentStatusUpdates: Insert new record
        $studentStatusUpdatesObj = $studentStatusUpdates->newEntity([]);
        $studentStatusUpdatesObj->model = 'StudentStatusUpdates';
        $studentStatusUpdatesObj->model_reference = 'Institutions';
        $studentStatusUpdatesObj->effective_date = $effectiveDate;
        $studentStatusUpdatesObj->execution_status = 1;
        $studentStatusUpdatesObj->security_user_id = $entity->student_id;
        $studentStatusUpdatesObj->institution_id = $entity->institution_id;
        $studentStatusUpdatesObj->academic_period_id = $entity->academic_period_id;
        $studentStatusUpdatesObj->education_grade_id = $entity->education_grade_id;
        $studentStatusUpdatesObj->status_id = $statusToUpdate;

        if ($toAcademicPeriodId == $fromAcademicPeriodId)
        {
            // if student is promoted/demoted in the middle of the academic period
            $studentObj['start_date'] = $effectiveDate;
            $studentObj['start_year'] = $effectiveDate->year;
        } else {
            $studentObj['start_date'] = $toPeriodData->start_date;
            $studentObj['start_year'] = $toPeriodData->start_year;
        }

        $newInstitutionStudent = $this->newEntity($studentObj);
        // End

        // InstitutionStudents: Update old record
        $existingInstitutionStudent = $this->find()
            ->where([
                $this->aliasField('institution_id') => $originalStudent->institution_id,
                $this->aliasField('student_id') => $originalStudent->student_id,
                $this->aliasField('academic_period_id') => $originalStudent->academic_period_id,
                $this->aliasField('education_grade_id') => $originalStudent->education_grade_id,
                $this->aliasField('student_status_id') => $studentStatuses['CURRENT']
            ])
            ->first();

        $existingInstitutionStudent->student_status_id = $statusToUpdate;

        if ($toAcademicPeriodId == $fromAcademicPeriodId)
        {   // if student is promoted/demoted in the middle of the academic period
            $beforeEffectiveDate = Time::parse($entity->effective_date)->modify('-1 day');
            $existingInstitutionStudent->end_date = $beforeEffectiveDate;
            $existingInstitutionStudent->end_year = $beforeEffectiveDate->year;
        }
        // End

        // InstitutionClassStudents: Insert and update records
        //$classId = $entity->institution_class_id;

        if (!empty($entity->institution_class_id))
        {
            $newClassStudent = [];
            $newClassStudent['student_id'] = $entity->student_id;
            $newClassStudent['education_grade_id'] = $entity->education_grade_id;
            $newClassStudent['institution_class_id'] = $entity->institution_class_id;
            $newClassStudent['student_status_id'] = 1; //POCOR-6349
            $newClassStudent['institution_id'] = $entity->institution_id;
            $newClassStudent['academic_period_id'] = $entity->academic_period_id;
        }

        $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $existingClassStudent = $InstitutionClassStudents->find()
            ->where([
                $InstitutionClassStudents->aliasField('institution_id') => $originalStudent->institution_id,
                $InstitutionClassStudents->aliasField('student_id') => $originalStudent->student_id,
                $InstitutionClassStudents->aliasField('academic_period_id') => $originalStudent->academic_period_id,
                $InstitutionClassStudents->aliasField('education_grade_id') => $originalStudent->education_grade_id,
                $InstitutionClassStudents->aliasField('student_status_id') => $studentStatuses['CURRENT']
            ])
            ->first();

        if (!empty($existingClassStudent))
        {
            $existingClassStudent->student_status_id = $statusToUpdate;
        }
        // End
//        $this->log($existingInstitutionStudent, 'debug');
//        $this->log($newInstitutionStudent, 'debug');

        if ($this->save($existingInstitutionStudent)) {
            if ($this->save($newInstitutionStudent)) {
                // update old class if exists
                if (!empty($existingClassStudent)) {
                    $InstitutionClassStudents->save($existingClassStudent);
                }
                // insert new class if class is selected
                if (!empty($entity->institution_class_id)) {
                    $InstitutionClassStudents->autoInsertClassStudent($newClassStudent);

                    //POCOR-7170
//                    $classId = $entity->institution_class_id;
//                    $studentClassData = $this->institutionClassStudentData($classId);
                }

//                $this->log($studentStatusUpdatesObj, 'debug');


                // Save record in the studentStatusUpdates
                $studentStatusUpdates->save($studentStatusUpdatesObj);

                return true;
            } else {
                $this->log($newInstitutionStudent->errors, 'debug');
            }
        } else {
            $message = 'failed to update student status';
            $this->log($message, 'debug');
        }

        return false;
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents'),
            TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents')
        ];
        $this->dispatchEventToModels('Model.Students.afterSave', [$entity], $this, $listeners);
    }

// POCOR-9403 webhook call moved to institutionclass


    /**
     * POCOR-7330
     * show validation message in education grade if promotion is done on same grade
     * */
    public function studentIfExist($entity,$requestData)
    {
       $educationGradeId = $this->request->getData()['IndividualPromotion']['education_grade_id'];
        $studentId = $entity->student_id;
        $statusId = $entity->student_status_id;
        $statusId = $entity->academic_period_id;
        $academicPeriodId = $this->request->getData()['IndividualPromotion']['academic_period_id'];
        $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $studentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $statusStudentId = $studentStatuses->find()->where([$studentStatuses->aliasField('name') => 'Promoted'])
                            ->first()->id;
        $students =  $institutionStudents->find()->where([$institutionStudents->aliasField('student_id') => $studentId, $institutionStudents->aliasField('student_status_id') => $statusStudentId , $institutionStudents->aliasField('academic_period_id') => $academicPeriodId,$institutionStudents->aliasField('education_grade_id') => $educationGradeId])->first();
        if(!empty($students)){
            return 'yes';
        }
    }
}

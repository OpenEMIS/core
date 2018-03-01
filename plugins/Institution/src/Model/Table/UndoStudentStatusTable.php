<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Controller\Component;
use App\Model\Table\AppTable;

class UndoStudentStatusTable extends AppTable
{
    private $undoActions = [];
    private $Grades = null;
    private $Students = null;
    private $statuses = [];     // Student Status
    private $dataCount = 0;

    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);

        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);

        // Undo behavior
        $this->Grades = TableRegistry::get('Institution.InstitutionGrades');
        $this->Students = TableRegistry::get('Institution.Students');
        $this->statuses = $this->StudentStatuses->findCodeList();
        $settings = [
            'model' => 'Institution.Students',
            'statuses' => $this->statuses
        ];

        // $this->addBehavior('Institution.UndoCurrent', $settings);
        // $this->addBehavior('Institution.UndoWithdrawn', $settings);
        // $this->addBehavior('Institution.UndoTransferred', $settings);
        $this->addBehavior('Institution.UndoGraduated', $settings);
        $this->addBehavior('Institution.UndoPromoted', $settings);
        $this->addBehavior('Institution.UndoRepeated', $settings);
        $this->addBehavior('Institution.ClassStudents');
        // End
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        // To clear the query string from the previous page to prevent logic conflict on this page
        $this->request->query = [];
    }

    public function beforeAction(Event $event)
    {
        $institutionClassTable = TableRegistry::get('Institution.InstitutionClasses');
        $this->institutionId = $this->Session->read('Institution.Institutions.id');
        $this->institutionClasses = $institutionClassTable->find('list')
            ->where([$institutionClassTable->aliasField('institution_id') => $this->institutionId])
            ->toArray();
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('class');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona = false)
    {
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
        $Navigation->substituteCrumb('Undo', 'Students', $url);
        $Navigation->addCrumb('Undo');
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $studentIds = [];
        $errors = $entity->errors();
        if (isset($errors['student_id'])) {
            unset($errors['student_id']);
        }
        if (!$errors) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('students', $data[$this->alias()])) {
                    foreach ($data[$this->alias()]['students'] as $key => $obj) {
                        $studentId = $obj['id'];
                        if ($studentId != 0) {
                            $studentIds[$studentId] = $studentId;
                        } else {
                            unset($data[$this->alias()]['students'][$key]);
                        }
                    }
                }
            }

            if (empty($studentIds)) {
                $this->Alert->warning('general.notSelected', ['reset' => true]);
            } else {
                $data[$this->alias()]['student_ids'] = $studentIds;
                // redirects to confirmation page
                $url = $this->ControllerAction->url('view');
                $url[0] = 'reconfirm';
                $session = $this->Session;
                $session->write($this->registryAlias().'.confirm', $entity);
                $session->write($this->registryAlias().'.confirmData', $data->getArrayCopy());
                $this->Alert->success('UndoStudentStatus.success', ['reset' => true]);
                $event->stopPropagation();
                return $this->controller->redirect($url);
            }
        }
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        // unset buttons if no students found
        switch ($this->action) {
            case 'add':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Next');
                break;
            case 'reconfirm':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Confirm');
                $cancelUrl = $this->ControllerAction->url('add');
                $cancelUrl = array_diff_key($cancelUrl, $this->request->query);
                $buttons[1]['url'] = $cancelUrl;
                break;
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'reconfirm') {
            $selectedPeriod = $request->data[$this->alias()]['academic_period_id'];
            $periodData = $this->AcademicPeriods
                ->find()
                ->where([$this->AcademicPeriods->aliasField('id') => $selectedPeriod])
                ->select([$this->AcademicPeriods->aliasField('name')])
                ->first();
            $periodName = (!empty($periodData))? $periodData['name']: '';

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $periodName;
        } else if ($action == 'add' || $action == 'edit') {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $Grades = $this->Grades;

            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = null;
            $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
                'selectOption' => false,
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
                'callable' => function ($id) use ($Grades, $institutionId) {
                    return $Grades
                        ->find()
                        ->where([$Grades->aliasField('institution_id') => $institutionId])
                        ->find('academicPeriod', ['academic_period_id' => $id])
                        ->count();
                }
            ]);

            $attr['options'] = $periodOptions;
            $attr['attr']['value'] = $request->query('period');
            $attr['onChangeReload'] = 'changePeriod';
        }

        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'reconfirm') {
            $selectedGrade = $request->data[$this->alias()]['education_grade_id'];
            $gradeData = $this->EducationGrades
                ->find()
                ->where([$this->EducationGrades->aliasField('id') => $selectedGrade])
                ->select([$this->EducationGrades->aliasField('education_programme_id'), $this->EducationGrades->aliasField('name')])
                ->first();
            $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('noGrades'));

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $gradeName;
        } else if ($action == 'add' || $action == 'edit') {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $selectedPeriod = $request->query('period');
            $gradeOptions = [];
            if (!empty($selectedPeriod)) {
                $gradeOptions = $this->Grades
                    ->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
                    ->contain(['EducationGrades.EducationProgrammes', 'EducationGrades.EducationStages'])
                    ->where([$this->Grades->aliasField('institution_id') => $institutionId])
                    ->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
                    ->order(['EducationStages.order', 'EducationGrades.order'])
                    ->toArray();
                $selectedGrade = $request->query('grade');
                $gradeOptions = $gradeOptions;
                $Students = $this->Students;
                $this->advancedSelectOptions($gradeOptions, $selectedGrade, [
                    'selectOption' => false,
                    'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
                    'callable' => function ($id) use ($Students, $institutionId, $selectedPeriod) {
                            return $Students
                                ->find()
                                ->where([
                                    'institution_id' => $institutionId,
                                    'academic_period_id' => $selectedPeriod,
                                    'education_grade_id' => $id
                                ])
                                ->count();
                    }
                ]);
            }

            $attr['options'] = $gradeOptions;
            $attr['attr']['value'] = $request->query('grade');
            $attr['onChangeReload'] = 'changeGrade';
        }

        return $attr;
    }

    public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'reconfirm') {
            $selectedStatus = $request->data[$this->alias()]['student_status_id'];
            $statusData = $this->StudentStatuses
                ->find()
                ->where([$this->StudentStatuses->aliasField('id') => $selectedStatus])
                ->select([$this->StudentStatuses->aliasField('id'), $this->StudentStatuses->aliasField('name')])
                ->first();
            $statusName = (!empty($statusData))? $statusData->name: $this->getMessage($this->aliasField('noGrades'));

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $statusName;
        } else if ($action == 'add' || $action == 'edit') {
            $statusOptions = [];

            // Admission, Transfer and Withdraw undo features have been moved to the Submit for Cancellation step in the custom workflows
            $codes = [];
            // $codes[$this->statuses['CURRENT']] = $this->statuses['CURRENT'];
            // $codes[$this->statuses['TRANSFERRED']] = $this->statuses['TRANSFERRED'];
            // $codes[$this->statuses['WITHDRAWN']] = $this->statuses['WITHDRAWN'];
            $codes[$this->statuses['GRADUATED']] = $this->statuses['GRADUATED'];
            $codes[$this->statuses['PROMOTED']] = $this->statuses['PROMOTED'];
            $codes[$this->statuses['REPEATED']] = $this->statuses['REPEATED'];

            $statusOptions = $this->StudentStatuses
                ->find('list')
                ->where([
                    $this->StudentStatuses->aliasField('id IN') => $codes
                ])
                ->toArray();

            $attr['options'] = $statusOptions;
            $attr['onChangeReload'] = 'changeStatus';
        }

        return $attr;
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, Request $request)
    {
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');

        if ($action == 'reconfirm') {
            if ($request->query('status') == $this->statuses['TRANSFERRED']) {
                $attr['type'] = 'hidden';
            } else {
                $attr['type'] = 'readonly';
            }

            $selectedClass = $request->query('class');
            if ($selectedClass != -1) {
                $institutionClassRecord = $InstitutionClasses->get($selectedClass)->name;
            } else {
                $institutionClassRecord = __('Students without Class');
            }

            $attr['attr']['value'] = $institutionClassRecord;
        } else {
            if ($request->query('status') == $this->statuses['TRANSFERRED']) {
                $attr['type'] = 'hidden';
            }

            $institutionId = $institutionId = $this->Session->read('Institution.Institutions.id');
            $selectedPeriod = $request->query('period');
            $selectedGrade = $request->query('grade');

            $institutionClassRecords = $InstitutionClasses->find('list')
                ->innerJoinWith('ClassGrades')
                ->where([
                    $InstitutionClasses->aliasField('institution_id') => $institutionId,
                    $InstitutionClasses->aliasField('academic_period_id') => $selectedPeriod,
                    'ClassGrades.education_grade_id' => $selectedGrade
                ])
                ->toArray();
            $options = ['-1' => __('Students without Class')] + $institutionClassRecords;
            $selectedClass = $request->query('class');
            if (empty($selectedClass)) {
                if (!empty($classes)) {
                    $selectedClass = key($classes);
                }
            }

            $this->advancedSelectOptions($options, $selectedClass);
            $request->query['class'] = $selectedClass;
            $attr['options'] = $options;
            $attr['attr']['value'] = $request->query('class');
            $attr['onChangeReload'] = 'changeClass';
        }
        return $attr;
    }

    public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request)
    {
        $data = [];

        if ($action == 'reconfirm') {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $selectedPeriod = $request->data[$this->alias()]['academic_period_id'];
            $selectedGrade = $request->data[$this->alias()]['education_grade_id'];
            $selectedStatus = $request->data[$this->alias()]['student_status_id'];
            $student_ids = $request->data[$this->alias()]['student_ids'];
            $selectedClass = $request->query('class');

            $conditions = [
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') =>  $selectedPeriod,
                $this->aliasField('education_grade_id') => $selectedGrade,
                $this->aliasField('student_status_id') => $selectedStatus,
                $this->aliasField('student_id IN') => $student_ids
            ];

            $data = $this
                ->find()
                ->matching('Users')
                ->matching('EducationGrades');

            if ($selectedStatus == $this->statuses['TRANSFERRED']) {
                $data->find('UndoTransferredStudent',
                    ['institutionId' => $institutionId, 'selectedPeriod' => $selectedPeriod, 'selectedClass' => $selectedClass, 'selectedGrade' => $selectedGrade, 'studentIds' => $student_ids]
                );
            } else {
                $data = $data
                    ->where([
                        $conditions
                    ])
                    ->find('studentClasses', ['institution_class_id' => $selectedClass])
                    ->select(['institution_class_id' => 'InstitutionClassStudents.institution_class_id']);
            }

            $data = $data
                ->order(['Users.first_name'])
                ->autoFields(true);

            $this->dataCount = $data->count();
        } else if ($action == 'add' || $action == 'edit') {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $selectedPeriod = $request->query('period');
            $selectedGrade = $request->query('grade');
            $selectedStatus = $request->query('status');
            $selectedClass = $request->query('class');

            if (!is_null($selectedPeriod) && $selectedGrade != -1 && $selectedStatus != -1) {
                $conditions = [
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('academic_period_id') =>  $selectedPeriod,
                    $this->aliasField('education_grade_id') => $selectedGrade,
                    $this->aliasField('student_status_id') => $selectedStatus
                ];

                $data = $this
                        ->find()
                        ->matching('Users')
                        ->matching('EducationGrades');

                //to undo enrolled, then student cant have specific status before.
                if ($selectedStatus == $this->statuses['CURRENT']) {
                    $checkStatus = [
                        $this->statuses['GRADUATED'],
                        $this->statuses['PROMOTED'],
                        $this->statuses['REPEATED'],
                        $this->statuses['TRANSFERRED']
                    ];

                    $data = $data
                        ->leftJoin(['InstitutionStudent' => 'institution_students'], [
                            'InstitutionStudent.id = ' . $this->aliasfield('previous_institution_student_id'),
                        ])
                        ->where([
                            $conditions,
                            'OR' => [
                                'InstitutionStudent.student_status_id NOT IN (' . implode(', ', $checkStatus) . ')',
                                'InstitutionStudent.student_status_id IS NULL' //null is a result of left join to detect new / single record
                            ],
                        ]);
                } else if ($selectedStatus == $this->statuses['TRANSFERRED']) {
                    $data->find('UndoTransferredStudent',
                        ['institutionId' => $institutionId, 'selectedPeriod' => $selectedPeriod, 'selectedClass' => $selectedClass, 'selectedGrade' => $selectedGrade, 'studentIds' => '']
                    );
                } else if ($selectedStatus == $this->statuses['WITHDRAWN']) {
                    $data = $data
                        ->leftJoin(['InstitutionStudent' => 'institution_students'], [
                            $this->aliasfield('id') . ' = ' . 'InstitutionStudent.previous_institution_student_id'
                        ])
                        ->where([
                            $conditions,
                            'InstitutionStudent.student_status_id IS NULL' //no record after withdraw record
                        ]);
                } else {
                    $data = $data
                        ->where([
                            $conditions
                        ]);
                }

                if ($selectedStatus != $this->statuses['TRANSFERRED']) { //for undo transfer, class filter is unnecessary.
                    $data = $data
                        ->find('studentClasses', ['institution_class_id' => $selectedClass])
                        ->select(['institution_class_id' => 'InstitutionClassStudents.institution_class_id']);
                }

                $data = $data
                    ->order(['Users.first_name'])
                    ->autoFields(true);

                // update students count here and show / hide form buttons in onGetFormButtons()
                $this->dataCount = $data->count();

                // onGetCurrentStudents event
                $statusCode = array_search($selectedStatus, $this->statuses);
                $undoAction = Inflector::camelize(strtolower($statusCode));
                $event = $this->dispatchEvent('Undo.get' . $undoAction . 'Students', [$data], $this);
                if ($event->isStopped()) {
                    return $event->result;
                }
                if (!empty($event->result)) {
                    $data = $event->result;
                    $this->dataCount = sizeof($data);
                }
                // End event
                if (empty($this->dataCount)) {
                    $this->Alert->warning($this->aliasField('noData'));
                }
            }
        }

        $attr['type'] = 'element';
        $attr['element'] = 'Institution.UndoStudentStatus/students';
        $attr['data'] = $data;
        $attr['classOptions'] = $this->institutionClasses;

        return $attr;
    }

    public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        $request->query['grade'] = -1;
        $request->query['class'] = -1;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
                if (array_key_exists('student_status_id', $request->data[$this->alias()])) {
                    $request->query['status'] = $request->data[$this->alias()]['student_status_id'];
                }
            }
        }
    }

    public function addEditOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
                }
                if (array_key_exists('student_status_id', $request->data[$this->alias()])) {
                    $request->query['status'] = $request->data[$this->alias()]['student_status_id'];
                }
                if (array_key_exists('class', $request->data[$this->alias()])) {
                    $request->query['class'] = $request->data[$this->alias()]['class'];
                }
            }
        }
    }

    public function addEditOnChangeGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        $request->query['class'] = -1;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
                }
                if (array_key_exists('student_status_id', $request->data[$this->alias()])) {
                    $request->query['status'] = $request->data[$this->alias()]['student_status_id'];
                }
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function addEditOnChangeStatus(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        $request->query['period'] = -1;
        $request->query['grade'] = -1;
        $request->query['class'] = -1;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('student_status_id', $request->data[$this->alias()])) {
                    $request->query['status'] = $request->data[$this->alias()]['student_status_id'];
                }
            }
        }
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($action == 'reconfirm') {
            $toolbarButtons['back'] = $buttons['back'];
            $toolbarButtons['back']['type'] = 'button';
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr'] = $attr;
            $toolbarButtons['back']['attr']['title'] = __('Back');
            $toolbarButtons['back']['url'][0] = 'add';
        } else if ($action == 'add') {
            $toolbarButtons['back'] = $buttons['back'];
            $toolbarButtons['back']['type'] = 'button';
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr'] = $attr;
            $toolbarButtons['back']['attr']['title'] = __('Back');
            $toolbarButtons['back']['url']['action'] = 'Students';
        }
    }

    public function reconfirm()
    {
        $model = $this;
        $request = $this->request;

        $entity = null;
        $sessionKey = $this->registryAlias() . '.confirm';
        if ($this->Session->check($sessionKey)) {
            $entity = $this->Session->read($sessionKey);
            $requestData = $this->Session->read($sessionKey.'Data');
        }

        if (!is_null($entity)) {
            $this->Alert->info($this->aliasField('reconfirm'), ['reset' => true]);

            if ($this->request->is(['get'])) {
                $this->request->data = $requestData;
            } else if ($this->request->is(['post', 'put'])) {
                $submit = isset($this->request->data['submit']) ? $this->request->data['submit'] : 'save';
                $patchOptions = new ArrayObject([]);
                $requestData = new ArrayObject($request->data);

                if ($submit == 'save') {
                    // bypass validation
                    $patchOptions['validate'] = false;

                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    $request->data = $requestData->getArrayCopy();
                    $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);

                    $selectedStatus = $entity->student_status_id;
                    $statusCode = array_search($selectedStatus, $this->statuses);
                    $undoAction = Inflector::camelize(strtolower($statusCode));

                    $event = $this->dispatchEvent('Undo.processSave' . $undoAction . 'Students', [$entity, $requestData], $this);
                    if ($event->isStopped()) {
                        return $event->result;
                    }

                    // set student_ids and output alert message in addAfterSave()
                    $student_ids = $event->result;

                    if (empty($student_ids)) {
                        $this->Alert->error('UndoStudentStatus.failed', ['reset' => true]);
                    } else {
                        $this->Alert->success('UndoStudentStatus.success', ['reset' => true]);
                    }

                    $url = $this->ControllerAction->url('add');
                    return $this->controller->redirect($url);
                }
            }

            $this->setupFields($entity);

            $this->controller->set('data', $entity);
        } else {
            $this->Alert->warning('general.notExists', ['reset' => true]);
            return $this->controller->redirect($this->ControllerAction->url('add'));
        }

        $this->ControllerAction->renderView('/ControllerAction/edit');
    }

    public function addUndoActions($type)
    {
        $this->undoActions[$type] = $type;
    }

    private function setupFields(Entity $entity)
    {
        $this->ControllerAction->field('student_id', ['visible' => false]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('start_date', ['visible' => false]);
        $this->ControllerAction->field('start_year', ['visible' => false]);
        $this->ControllerAction->field('end_date', ['visible' => false]);
        $this->ControllerAction->field('end_year', ['visible' => false]);

        $this->ControllerAction->field('academic_period_id', ['type' => 'select']);
        $this->ControllerAction->field('education_grade_id', ['type' => 'select']);
        $this->ControllerAction->field('class', ['select' => false]);
        $this->ControllerAction->field('student_status_id', ['type' => 'select']);
        $this->ControllerAction->field('students');

        $this->ControllerAction->setFieldOrder(['student_status_id', 'academic_period_id', 'education_grade_id', 'class', 'students']);
    }

    public function findUndoTransferredStudent(Query $query, array $options)
    {
        $conditions = [
            $this->aliasField('academic_period_id') =>  $options['selectedPeriod'],
            $this->aliasField('education_grade_id') => $options['selectedGrade'],
            $this->aliasField('student_status_id') => $this->statuses['TRANSFERRED'],
            'StudentAdmission.institution_id = ' . $options['institutionId']
        ];

        if ($options['studentIds']) { //if has selected student / reconfirm page.
            $conditions[$this->aliasField('student_id') . ' IN '] = $options['studentIds'];
        }

        $query
            ->innerjoin(
                ['StudentAdmission' => 'institution_student_admission'], [
                    'StudentAdmission.previous_institution_id = ' . $this->aliasfield('institution_id'),
                    'StudentAdmission.student_id = ' . $this->aliasfield('student_id'),
                    'StudentAdmission.academic_period_id = ' . $this->aliasfield('academic_period_id'),
                    'StudentAdmission.education_grade_id = ' . $this->aliasfield('education_grade_id'),
                    'StudentAdmission.type = 2', //transfer type
                    'StudentAdmission.status = 1' //status is approved
                ])
            ->leftJoin(
                ['InstitutionStudent' => 'institution_students'], [
                    $this->aliasfield('id') . ' = ' . 'InstitutionStudent.previous_institution_student_id',
                    'StudentAdmission.institution_id = ' . 'InstitutionStudent.institution_id',
                    'StudentAdmission.new_education_grade_id = ' . 'InstitutionStudent.education_grade_id'
                ])
            ->where([
                $conditions,
                'OR' => [
                    'InstitutionStudent.student_status_id = ' . $this->statuses['CURRENT'],
                    'InstitutionStudent.student_status_id IS NULL' //null is a result of left join to detect transferred without enrolled record (Jordan data)
                ],
            ]);

        return $query;
    }
}

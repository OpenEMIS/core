<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Controller\Component;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class StudentPromotionTable extends AppTable
{
    private $InstitutionGrades = null;
    private $institutionId = null;
    private $currentPeriod = null;
    private $statuses = []; // Student Status

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
        $this->addBehavior('Institution.ClassStudents');
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        // To clear the query string from the previous page to prevent logic conflict on this page
        $this->request->query = [];
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('from_academic_period_id')
            ->requirePresence('next_academic_period_id')
            ->requirePresence('grade_to_promote')
            ->requirePresence('class')
            ->allowEmpty('education_grade_id', function ($context) {
                $studentStatusId = (!empty($context['data']['student_status_id']))? $context['data']['student_status_id']: '';
                return ($studentStatusId != $this->statuses['PROMOTED']);
            });
    }

    public function validationRemoveStudentPromotionValidation(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator
            ->requirePresence('from_academic_period_id', false)
            ->requirePresence('next_academic_period_id', false)
            ->requirePresence('grade_to_promote', false)
            ->requirePresence('class', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::get('Institution.InstitutionClassStudents'),
            TableRegistry::get('Institution.InstitutionSubjectStudents')
        ];
        $this->dispatchEventToModels('Model.Students.afterSave', [$entity], $this, $listeners);
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona=false)
    {
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
        $Navigation->substituteCrumb('Promotion', 'Students', $url);
        $Navigation->addCrumb('Promotion');
    }

    public function beforeAction(Event $event)
    {
        $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $this->institutionId = $this->Session->read('Institution.Institutions.id');
        $institutionClassTable = TableRegistry::get('Institution.InstitutionClasses');
        $this->institutionClasses = $institutionClassTable->find('list')
            ->where([$institutionClassTable->aliasField('institution_id') => $this->institutionId])
            ->toArray();
        $selectedPeriod = $this->AcademicPeriods->getCurrent();
        $this->currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
        $this->statuses = $this->StudentStatuses->findCodeList();
    }

    public function addAfterAction()
    {
        $this->fields = [];
        $this->ControllerAction->field('from_academic_period_id', ['attr' => ['label' => $this->getMessage($this->aliasField('fromAcademicPeriod'))]]);
        $this->ControllerAction->field('next_academic_period_id', ['attr' => ['label' => $this->getMessage($this->aliasField('toAcademicPeriod'))]]);
        $this->ControllerAction->field('grade_to_promote', ['attr' => ['label' => $this->getMessage($this->aliasField('fromGrade'))]]);
        $this->ControllerAction->field('class');
        $this->ControllerAction->field('student_status_id', ['attr' => ['label' => $this->getMessage($this->aliasField('status'))]]);
        $this->ControllerAction->field('education_grade_id', ['attr' => ['label' => $this->getMessage($this->aliasField('toGrade'))]]);
        $this->ControllerAction->field('students');

        $this->ControllerAction->setFieldOrder(['from_academic_period_id', 'next_academic_period_id', 'grade_to_promote', 'class', 'student_status_id', 'education_grade_id','students']);
    }

    public function addOnChangeFromPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['from_period']);
        unset($this->request->query['grade_to_promote']);
        unset($this->request->query['class']);
        unset($this->request->query['student_status']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('from_academic_period_id', $data[$this->alias()])) {
                    $this->request->query['from_period'] = $data[$this->alias()]['from_academic_period_id'];
                }
                if (array_key_exists('next_academic_period_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['next_academic_period_id']);
                }
                if (array_key_exists('grade_to_promote', $data[$this->alias()])) {
                    unset($data[$this->alias()]['grade_to_promote']);
                }
                if (array_key_exists('class', $data[$this->alias()])) {
                    unset($data[$this->alias()]['class']);
                }
                if (array_key_exists('student_status_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['student_status_id']);
                }
            }
        }
    }

    public function addOnChangeGradeToPromote(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['grade_to_promote']);
        unset($this->request->query['class']);
        unset($this->request->query['student_status']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('grade_to_promote', $data[$this->alias()])) {
                    $this->request->query['grade_to_promote'] = $data[$this->alias()]['grade_to_promote'];
                }

                if (array_key_exists('student_status_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['student_status_id']);
                }

                if (array_key_exists('class', $data[$this->alias()])) {
                    unset($data[$this->alias()]['class']);
                }
            }
        }
    }

    public function addOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['class']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('class', $data[$this->alias()])) {
                    $this->request->query['class'] = $data[$this->alias()]['class'];
                }
            }
        }
    }

    public function addOnChangeStudentStatus(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['student_status']);
        unset($data[$this->alias()]['education_grade_id']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('student_status_id', $data[$this->alias()])) {
                    $this->request->query['student_status'] = $data[$this->alias()]['student_status_id'];
                }
            }
        }
    }

    public function onUpdateFieldFromAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $selectedAcademicPeriodId = $currentData['from_academic_period_id'];
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->AcademicPeriods->get($selectedAcademicPeriodId)->name;
                break;

            default:
                $condition = [$this->AcademicPeriods->aliasField('order').' >= ' => $this->currentPeriod->order];
                $academicPeriodList = $this->AcademicPeriods->getYearList(['conditions' => $condition, 'isEditable' => true]);
                $attr['type'] = 'select';
                $attr['options'] = $academicPeriodList;
                $attr['onChangeReload'] = 'changeFromPeriod';
                break;
        }
        return $attr;
    }

    public function onUpdateFieldNextAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                if ($currentData->has('next_academic_period_id')) {
                    $academicPeriodData = $this->AcademicPeriods
                        ->find()
                        ->where([$this->AcademicPeriods->aliasField($this->AcademicPeriods->primaryKey()) => $currentData->next_academic_period_id])
                        ->select([$this->AcademicPeriods->aliasField('name')])
                        ->first();
                    $academicPeriodName = (!empty($academicPeriodData))? $academicPeriodData['name']: '';
                }

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = (!empty($academicPeriodName))? $academicPeriodName: $this->getMessage($this->aliasField('noAvailableAcademicPeriod'));
                break;

            default:
                $selectedPeriodId = $request->query('from_period');
                $periodOptions = [];
                if (!empty($selectedPeriodId) && $selectedPeriodId != -1) {
                    $selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
                    $condition = [$this->AcademicPeriods->aliasField('order').' < ' => $selectedPeriod->order, $this->AcademicPeriods->aliasField('id').' <> ' => $selectedPeriodId];
                    $periodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition, 'isEditable' => true]);
                    $attr['type'] = 'select';
                }

                $attr['options'] = $periodOptions;
                break;
        }

        return $attr;
    }

    public function onUpdateFieldNextGrade(Event $event, array $attr, $action, Request $request)
    {
        // used for reconfirm
        $sessionKey = $this->registryAlias() . '.confirm';
        if ($this->Session->check($sessionKey)) {
            $currentData = $this->Session->read($sessionKey);
        }

        if ($currentData->has('education_grade_id')) {
            $gradeData = $this->EducationGrades
                ->find()
                ->where([$this->EducationGrades->aliasField($this->EducationGrades->primaryKey()) => $currentData->education_grade_id])
                ->select([$this->EducationGrades->aliasField('education_programme_id'), $this->EducationGrades->aliasField('name')])
                ->first();
            $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('noAvailableGrades'));

            // to get the notEnrolled message for the reconfirm page
            $nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($currentData['grade_to_promote']);

            // list of grades available in the institution
            $institutionId = $this->institutionId;
            $listOfInstitutionGrades = $this->getListOfInstitutionGrades($institutionId);

            if ($currentData['student_status_id'] == $this->statuses['GRADUATED'] && array_key_exists(key($nextGrades), $listOfInstitutionGrades)) {
                $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('notEnrolled'));
            }
            // end of getting the notEnrolled message

        } else if ($currentData['student_status_id'] == $this->statuses['REPEATED']) {
            $gradeData = $this->EducationGrades->get($currentData['grade_to_promote']);
            $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('noAvailableGrades'));
        }

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = (!empty($gradeName))? $gradeName: '';

        return $attr;
    }

    public function onUpdateFieldGradeToPromote(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                if ($currentData->has('grade_to_promote')) {
                    $gradeData = $this->EducationGrades
                        ->find()
                        ->where([$this->EducationGrades->aliasField($this->EducationGrades->primaryKey()) => $currentData->grade_to_promote])
                        ->select([$this->EducationGrades->aliasField('education_programme_id'), $this->EducationGrades->aliasField('name')])
                        ->first();
                    $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('noAvailableGrades'));
                }

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = (!empty($gradeName))? $gradeName: '';
                break;

            default:
                $InstitutionTable = $this->Institutions;
                $InstitutionGradesTable = $this->InstitutionGrades;
                $selectedPeriod = $request->query('from_period');
                $gradeOptions = [];
                if (!empty($selectedPeriod) && $selectedPeriod != -1) {
                    $institutionId = $this->institutionId;
                    $statuses = $this->statuses;
                    $gradeOptions = $InstitutionGradesTable
                        ->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
                        ->contain(['EducationGrades.EducationProgrammes', 'EducationGrades.EducationStages'])
                        ->where([$InstitutionGradesTable->aliasField('institution_id') => $institutionId])
                        ->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
                        ->order(['EducationStages.order', 'EducationGrades.order'])
                        ->toArray();

                    $attr['type'] = 'select';
                    $selectedGrade = null;
                    $GradeStudents = $this;
                    $this->advancedSelectOptions($gradeOptions, $selectedGrade, [
                        'selectOption' => false,
                        'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
                        'callable' => function($id) use ($GradeStudents, $institutionId, $selectedPeriod, $statuses) {
                            return $GradeStudents
                                ->find()
                                ->where([
                                    $GradeStudents->aliasField('institution_id') => $institutionId,
                                    $GradeStudents->aliasField('academic_period_id') => $selectedPeriod,
                                    $GradeStudents->aliasField('education_grade_id') => $id,
                                    $GradeStudents->aliasField('student_status_id') => $statuses['CURRENT']
                                ])
                                ->count();
                        }
                    ]);
                }
                $attr['onChangeReload'] = 'changeGradeToPromote';
                $attr['options'] = $gradeOptions;
                break;
        }

        return $attr;
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, Request $request)
    {
        $institutionClass = TableRegistry::get('Institution.InstitutionClasses');
        switch ($action) {

            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $institutionClassId = $currentData['class'];
                if ($institutionClassId == -1) {
                    $attr['type'] = 'readonly';
                    $attr['attr']['value'] = __('Students without Class');
                } else {
                    $attr['type'] = 'readonly';
                    $attr['attr']['value'] = $institutionClass->get($institutionClassId)->name;
                }
                break;

            default:
                $institutionId = $this->institutionId;
                $selectedPeriod = $request->query('from_period');
                $educationGradeId = $request->query('grade_to_promote');
                $classes = [];
                $options = ['-1' => __('Students without Class')];
                if (!empty($selectedPeriod) && $selectedPeriod != -1 && !empty($educationGradeId) && $educationGradeId != -1) {
                    $classes = $institutionClass
                        ->find('list')
                        ->leftJoinWith('ClassGrades')
                        ->where([
                            $institutionClass->aliasField('academic_period_id') => $selectedPeriod,
                            $institutionClass->aliasField('institution_id') => $institutionId,
                            'ClassGrades.education_grade_id' => $educationGradeId
                        ])
                        ->toArray();
                    $options = $options + $classes;
                    $selectedClass = $request->query('class');
                    if (empty($selectedClass)) {
                        if (!empty($classes)) {
                            $selectedClass = key($classes);
                        }
                    }
                    $studentStatuses = $this->statuses;
                    $model = $this;

                    $this->advancedSelectOptions($options, $selectedClass, [
                            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
                            'callable' => function($id) use ($model, $institutionId, $selectedPeriod, $educationGradeId, $studentStatuses) {
                                if ($id == -1) {
                                    return true;
                                }
                                return $model->find()
                                    ->innerJoin(['InstitutionClassStudents' => 'institution_class_students'],
                                        [
                                            'InstitutionClassStudents.education_grade_id = '.$model->aliasField('education_grade_id'),
                                            'InstitutionClassStudents.student_id = '.$model->aliasField('student_id'),
                                            'InstitutionClassStudents.institution_id = '.$model->aliasField('institution_id'),
                                            'InstitutionClassStudents.academic_period_id = '.$model->aliasField('academic_period_id'),
                                        ]
                                    )
                                    ->where([
                                        $this->aliasField('institution_id') => $institutionId,
                                        $this->aliasField('academic_period_id') => $selectedPeriod,
                                        $this->aliasField('student_status_id') => $studentStatuses['CURRENT'],
                                        $this->aliasField('education_grade_id') => $educationGradeId,
                                        'InstitutionClassStudents.institution_class_id' => $id
                                    ])
                                    ->count();
                            }
                        ]);
                    $request->query['class'] = $selectedClass;
                }
                $attr['options'] = $options;
                $attr['select'] = false;
                $attr['onChangeReload'] = 'changeClass';


                break;
        }
        return $attr;
    }

    public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $studentStatusesList = $this->StudentStatuses->find('list')->toArray();
            $statusesCode = $this->statuses;
            $options = [];
            $educationGradeId = $request->query('grade_to_promote');
            if (!empty($educationGradeId) && $educationGradeId != -1) {
                $nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId);
                $isLastGrade = $this->EducationGrades->isLastGradeInEducationProgrammes($educationGradeId);

                // If there is no more next grade in the same education programme then the student may be graduated
                if (count($nextGrades) == 0 || $isLastGrade) {
                    $options[$statusesCode['GRADUATED']] = __($studentStatusesList[$statusesCode['GRADUATED']]);
                } else {
                    $options[$statusesCode['PROMOTED']] = __($studentStatusesList[$statusesCode['PROMOTED']]);
                }
                $options[$statusesCode['REPEATED']] = __($studentStatusesList[$statusesCode['REPEATED']]);
            }

            foreach ($options as $key => $value) {
                $options[$key] = __($value);
            }

            $attr['options'] = $options;
            $attr['onChangeReload'] = 'changeStudentStatus';
            return $attr;
        }
    }

    public function onUpdateFieldStudentStatus(Event $event, array $attr, $action, Request $request)
    {
        // used for reconfirm
        $sessionKey = $this->registryAlias() . '.confirm';
        if ($this->Session->check($sessionKey)) {
            $currentData = $this->Session->read($sessionKey);
        }

        if ($currentData->has('student_status_id')) {
            $statusData = $this->StudentStatuses
                ->find()
                ->where([$this->StudentStatuses->aliasField($this->StudentStatuses->primaryKey()) => $currentData->student_status_id])
                ->select([$this->StudentStatuses->aliasField('name')])
                ->first();
            $statusName = (!empty($statusData))? $statusData->name: '';
        }

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = (!empty($statusName))? $statusName: '';

        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $studentStatusId = $request->query('student_status');

        if (!empty($studentStatusId)) {
            $statuses = $this->statuses;
            $educationGradeId = $request->query('grade_to_promote');

            if (!in_array($studentStatusId, [$statuses['REPEATED']])) {
                $institutionId = $this->institutionId;

                $isLastGrade = $this->EducationGrades->isLastGradeInEducationProgrammes($educationGradeId);
                if ($isLastGrade) {
                    // list of next first grades from all next programme available to promote to
                    // 'true' means get all the grades of the next programmes plus the current programme grades
                    // 'true' means get first grade only from all available next programme
                    $listOfGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, true, true);
                } else {
                    // list of grades available to promote to
                    // 'false' means only displayed the next level within the same grade level.
                    $listOfGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, false);

                    // if is not last grade, listOfGrades show the next grade of the current grade only
                    $listOfGrades = [key($listOfGrades) => current($listOfGrades)];
                }

                // list of grades available in the institution
                $listOfInstitutionGrades = $this->getListOfInstitutionGrades($institutionId);

                // Only display the options that are available in the institution and also linked to the current programme
                $gradeOptions = array_intersect_key($listOfInstitutionGrades, $listOfGrades);

                // if no grade option or the next grade is not available in the institution
                if (count($gradeOptions) == 0) {
                    $attr['select'] = false;
                    $options = [0 => $this->getMessage($this->aliasField('noAvailableGrades'))];
                } else {
                    // to cater for graduate
                    if (in_array($studentStatusId, [$statuses['GRADUATED']])) {
                        $options = [0 => $this->getMessage($this->aliasField('notEnrolled'))] + $gradeOptions;
                    } else {
                        // to cater for promote
                        $options = $gradeOptions;
                    }
                }

                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;

            } else {
                $gradeData = $this->EducationGrades->get($educationGradeId);
                $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: '';

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $gradeName;
            }

        } else {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = '';
        }

        return $attr;
    }

    public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request)
    {
        $institutionId = $this->institutionId;

        $currentData = null;
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $attr['selectedStudents'] = ($currentData->has('students'))? $currentData->students: [];
                $selectedPeriod = $currentData['from_academic_period_id'];
                break;

            default:
                $selectedPeriod = $request->query('from_period');
                break;
        }

        $students = [];
        if (!empty($selectedPeriod) && $selectedPeriod != -1) {
            $selectedGrade = $request->query('grade_to_promote');
            if (!is_null($selectedGrade)) {
                $studentStatuses = $this->statuses;
                $selectedClass = $request->query('class');

                $students = $this->find()
                    ->matching('Users')
                    ->matching('EducationGrades')
                    ->where([
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('student_status_id') => $studentStatuses['CURRENT'],
                        $this->aliasField('education_grade_id') => $selectedGrade
                    ])
                    ->find('studentClasses', ['institution_class_id' => $selectedClass])
                    ->select(['institution_class_id' => 'InstitutionClassStudents.institution_class_id'])
                    ->order(['Users.first_name'])
                    ->autoFields(true);

                if ($students->count() > 0) {
                    $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
                    $StudentAdmissionTable = TableRegistry::get('Institution.StudentAdmission');
                    $StudentTransfersTable = TableRegistry::get('Institution.InstitutionStudentTransfers');
                    $StudentWithdrawTable = TableRegistry::get('Institution.StudentWithdraw');
                    $students = $students->toArray();

                    $pendingAdmissionStatus = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentAdmission', 'PENDING');
                    $pendingWithdrawStatus = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentWithdraw', 'PENDING');
                    $pendingTransferStatuses = $StudentTransfersTable->getStudentTransferWorkflowStatuses('PENDING');

                    // check if students have any pending requests
                    foreach ($students as $key => $value) {
                        $totalCount = 0;

                        // count pending admission requests
                        $conditions = [
                            'student_id' => $value->student_id,
                            'status_id IN ' => $pendingAdmissionStatus,
                            'education_grade_id' => $value->education_grade_id,
                            'institution_id' => $value->institution_id,
                            'academic_period_id' => $value->academic_period_id
                        ];
                        $admissionCount = $StudentAdmissionTable->find()
                            ->where($conditions)
                            ->count();
                        $totalCount += $admissionCount;

                        // count pending transfer requests
                        $conditions = [
                            'student_id' => $value->student_id,
                            'status_id IN ' => $pendingTransferStatuses,
                            'previous_education_grade_id' => $value->education_grade_id,
                            'previous_institution_id' => $value->institution_id,
                            'previous_academic_period_id' => $value->academic_period_id
                        ];
                        $transferCount = $StudentTransfersTable->find()
                            ->where($conditions)
                            ->count();
                        $totalCount += $transferCount;

                        // count pending withdraw requests
                        $conditions = [
                            'student_id' => $value->student_id,
                            'status_id IN ' =>  $pendingWithdrawStatus,
                            'education_grade_id' => $value->education_grade_id,
                            'institution_id' => $value->institution_id,
                            'academic_period_id' => $value->academic_period_id
                        ];
                        $withdrawCount = $StudentWithdrawTable->find()
                            ->where($conditions)
                            ->count();
                        $totalCount += $withdrawCount;

                        $students[$key]->pendingRequestsCount = $totalCount;
                    }
                }
            }
            if (empty($students)) {
                $this->Alert->warning($this->aliasField('noData'));
            }
        }

        $attr['type'] = 'element';
        $attr['element'] = 'Institution.StudentPromotion/students';
        $attr['data'] = $students;
        $attr['classOptions'] = $this->institutionClasses;

        return $attr;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        switch ($action) {
            case 'add':
                $toolbarButtons['back'] = $buttons['back'];
                $toolbarButtons['back']['type'] = 'button';
                $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
                $toolbarButtons['back']['attr'] = $attr;
                $toolbarButtons['back']['attr']['title'] = __('Back');
                $toolbarButtons['back']['url']['action'] = 'Students';
                break;

            case 'reconfirm':
                unset($toolbarButtons['back']);
                break;

            default:
                # code...
                break;
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        // Removal of some fields that are not in use in the table validation
        $errors = $entity->errors();
        $studentStatus = $data[$this->alias()]['student_status_id'];

        if (isset($errors['student_id'])) {
            unset($errors['student_id']);
        }
        if (isset($errors['academic_period_id'])) {
            unset($errors['academic_period_id']);
        }
        if (isset($errors['institution_id'])) {
            unset($errors['institution_id']);
        }

        $statuses = TableRegistry::get('Student.StudentStatuses');
        $repeatStatus = $statuses->getIdByCode('REPEATED');

        if (!$errors) {
            if (array_key_exists($this->alias(), $data)) {
                $selectedStudent = false;
                if (array_key_exists('students', $data[$this->alias()])) {
                    foreach ($data[$this->alias()]['students'] as $key => $value) {
                        if ($value['selected'] != 0) {
                            $selectedStudent = true;
                            break;
                        }
                    }
                }
                $nextAcademicPeriodId = isset($data[$this->alias()]['next_academic_period_id']) ? $data[$this->alias()]['next_academic_period_id'] : 0;
                $educationGradeId = isset($data[$this->alias()]['education_grade_id']) ? $data[$this->alias()]['education_grade_id'] : 0;

                if ($selectedStudent) {
                    // redirects to confirmation page
                    $url = $this->ControllerAction->url('reconfirm');
                    $this->currentEntity = $entity;
                    $session = $this->Session;
                    $session->write($this->registryAlias().'.confirm', $entity);
                    $session->write($this->registryAlias().'.confirmData', $data);
                    $this->currentEvent = $event;
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                } else {
                    $this->Alert->warning($this->alias().'.noStudentSelected');
                }
            }
        }
    }

    public function savePromotion(Entity $entity, ArrayObject $data)
    {
        $url = $this->ControllerAction->url('index');
        $url['action'] = 'Students';

        $nextAcademicPeriodId = null;
        $nextEducationGradeId = null;
        $fromAcademicPeriod = null;
        $currentGrade = null;
        $statusToUpdate = null;
        $studentStatuses = $this->statuses;
        $institutionId = $this->institutionId;
        if (array_key_exists('from_academic_period_id', $data[$this->alias()])) {
            $fromAcademicPeriod = $data[$this->alias()]['from_academic_period_id'];
        }
        if (array_key_exists('grade_to_promote', $data[$this->alias()])) {
            $currentGrade = $data[$this->alias()]['grade_to_promote'];
        }

        if (array_key_exists('next_academic_period_id', $data[$this->alias()])) {
            $nextAcademicPeriodId = $data[$this->alias()]['next_academic_period_id'];
        }
        if (array_key_exists('education_grade_id', $data[$this->alias()])) {
            $nextEducationGradeId = $data[$this->alias()]['education_grade_id'];
        }
        if (array_key_exists('student_status_id', $data[$this->alias()])) {
            $statusToUpdate = $data[$this->alias()]['student_status_id'];
        }
        if ($statusToUpdate == $studentStatuses['REPEATED']) {
            $nextEducationGradeId = $currentGrade;
        }
        if ($statusToUpdate == $studentStatuses['PROMOTED']) {
            $successMessage = $this->aliasField('success');
        } else if ($statusToUpdate == $studentStatuses['GRADUATED']) {
            $successMessage = $this->aliasField('successGraduated');
        } else {
            $successMessage = $this->aliasField('successOthers');
        }
        if (!empty($fromAcademicPeriod) && !empty($currentGrade)) {
            if (array_key_exists('students', $data[$this->alias()])) {
                foreach ($data[$this->alias()]['students'] as $key => $studentObj) {
                    if ($studentObj['selected']) {
                        unset($studentObj['selected']);
                        if ($nextAcademicPeriodId != 0) {
                            $studentObj['academic_period_id'] = $nextAcademicPeriodId;
                            $studentObj['education_grade_id'] = $nextEducationGradeId;
                            $studentObj['institution_id'] = $institutionId;
                            $studentObj['student_status_id'] = $studentStatuses['CURRENT'];
                            $nextPeriod = $this->AcademicPeriods->get($nextAcademicPeriodId);
                            $studentObj['start_date'] = $nextPeriod->start_date->format('Y-m-d');
                            $studentObj['end_date'] = $nextPeriod->end_date->format('Y-m-d');
                        }
                        $entity = $this->newEntity($studentObj, ['validate' => 'RemoveStudentPromotionValidation']);
                        $existingStudentEntity = $this->find()->where([
                                $this->aliasField('institution_id') => $institutionId,
                                $this->aliasField('student_id') => $studentObj['student_id'],
                                $this->aliasField('academic_period_id') => $fromAcademicPeriod,
                                $this->aliasField('education_grade_id') => $currentGrade,
                                $this->aliasField('student_status_id') => $studentStatuses['CURRENT']
                            ])->first();
                        $existingStudentEntity->student_status_id = $statusToUpdate;
                        if ($this->save($existingStudentEntity)) {
                            if ($nextEducationGradeId != 0 && $nextAcademicPeriodId != 0) {

                                $entity->previous_institution_student_id = $existingStudentEntity->id;

                                //registry the Institution.Students so it will call the afterSave in it.
                                $InstitutionStudents = TableRegistry::get('Institution.Students');
                                if ($InstitutionStudents->save($entity)) {
                                    $this->Alert->success($successMessage, ['reset' => true]);
                                } else {
                                    $this->log($entity->errors(), 'debug');
                                }
                            } else {
                                $this->Alert->success($successMessage, ['reset' => true]);
                            }
                        } else {
                            $message = 'failed to update student status';
                            $this->Alert->error($this->aliasField('savingPromotionError'), ['reset' => true]);
                            $this->log($message, 'debug');
                            $url['action'] = 'Promotion';
                            $url[0] = 'add';
                        }
                    }
                }
            } else {
                $message = 'students does not exists in data';
                $this->Alert->error($this->aliasField('noStudentSelected'), ['reset' => true]);
                $this->log($message, 'debug');
                $url['action'] = 'Promotion';
                $url[0] = 'add';
            }
        } else {
            $message = 'nextAcademicPeriodId && fromAcademicPeriod && currentGrade are empty';
            $this->Alert->error($this->aliasField('noNextGradeOrNextPeriod'), ['reset' => true]);
            $this->log($message, 'debug');
            $url['action'] = 'Promotion';
            $url[0] = 'add';
        }

        return $this->controller->redirect($url);
    }

    public function reconfirm()
    {
        $this->Alert->info($this->aliasField('reconfirm', ['reset' => true]));

        $sessionKey = $this->registryAlias() . '.confirm';
        if ($this->Session->check($sessionKey)) {
            $currentEntity = $this->Session->read($sessionKey);
            $currentData = $this->Session->read($sessionKey.'Data');
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($this->ControllerAction->url('add'));
        }
        $academicPeriodData = $this->AcademicPeriods
            ->find()
            ->where([$this->AcademicPeriods->aliasField($this->AcademicPeriods->primaryKey()) => $currentEntity->from_academic_period_id])
            ->select([$this->AcademicPeriods->aliasField('name')])
            ->first();
        $academicPeriodName = (!empty($academicPeriodData))? $academicPeriodData['name']: '';
        // preset all fields as invisble
        foreach ($this->fields as $key => $value) {
            $this->fields[$key]['visible'] = false;
        }

        $this->ControllerAction->field('from_academic_period_id', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('fromAcademicPeriod'))]]);
        $this->ControllerAction->field('grade_to_promote', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('fromGrade'))]]);
        $this->ControllerAction->field('class');
        $this->ControllerAction->field('next_academic_period_id', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('toAcademicPeriod'))]]);
        $this->ControllerAction->field('student_status', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('status'))]]);
        $statuses = $this->statuses;
        $this->ControllerAction->field('students', ['type' => 'readonly']);
        $this->ControllerAction->field('next_grade', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('toGrade'))]]);
        $this->ControllerAction->setFieldOrder(['from_academic_period_id', 'next_academic_period_id', 'grade_to_promote', 'class', 'student_status', 'next_grade',  'students']);

        if ($currentEntity && !empty($currentEntity)) {
            if ($this->request->is(['post', 'put'])) {
                if ($currentData instanceOf ArrayObject) {
                    $currentData = $currentData->getArrayCopy();
                }
                $currentEntity = $this->patchEntity($currentEntity, $currentData, []);
                return $this->savePromotion($currentEntity, new ArrayObject($currentData));
            }
            $this->controller->set('data', $currentEntity);
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($this->ControllerAction->url('add'));
        }

        $this->ControllerAction->renderView('/ControllerAction/edit');
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
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

            default:
                # code...
                break;
        }
    }

    public function getListOfInstitutionGrades($institutionId)
    {
        // list of grades available in the institution
        $today = date('Y-m-d');
        $listOfInstitutionGrades = $this->InstitutionGrades
        ->find('list', [
            'keyField' => 'education_grade_id',
            'valueField' => 'education_grade.programme_grade_name'])
        ->contain(['EducationGrades.EducationProgrammes'])
        ->where([
            $this->InstitutionGrades->aliasField('institution_id') => $institutionId,
            'OR' => [
                [
                    $this->InstitutionGrades->aliasField('end_date IS NULL'),
                    $this->InstitutionGrades->aliasField('start_date <= ') => $today
                ],
                [
                    $this->InstitutionGrades->aliasField('end_date IS NOT NULL'),
                    $this->InstitutionGrades->aliasField('start_date <= ') => $today,
                    $this->InstitutionGrades->aliasField('end_date >= ') => $today
                ]
            ]
        ])
        ->order(['EducationProgrammes.order', 'EducationGrades.order'])
        ->toArray();

        return $listOfInstitutionGrades;
    }
}

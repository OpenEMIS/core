<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Text;
use Cake\I18n\Time;

class InstitutionExaminationsUndoRegistrationTable extends ControllerActionTable {

    public function initialize(array $config) {
        $this->table('examination_centres_examinations_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->belongsToMany('ExaminationCentresExaminationsSubjects', [
            'className' => 'Examination.ExaminationCentresExaminationsSubjects',
            'joinTable' => 'examination_centres_examinations_subjects_students',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'targetForeignKey' => ['examination_centre_id', 'examination_item_id'],
            'through' => 'Examination.ExaminationCentresExaminationsSubjectsStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', [
            'className' => 'Examination.ExaminationCentreRoomsExaminationsStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'bindingKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'dependent' => true,
            'cascadeCallBacks' => true
        ]);

        $this->toggle('index', false);
        $this->toggle('remove', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.reconfirm'] = 'reconfirm';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'ExaminationStudents'];
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        if (isset($toolbarButtons['back'])) {
            $toolbarButtons['back']['url']['action'] = 'ExaminationStudents';
        }

        if ($this->action == 'reconfirm') {
           $entity = $this->Session->read($this->registryAlias().'.confirm');
        }

        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('examination_education_grade', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('institution_class_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('student_id', ['entity' => $entity]);
        $this->field('registration_number', ['visible' => false]);

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'examination_education_grade', 'institution_class_id', 'student_id'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
        if ($action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            $attr['default'] = $selectedAcademicPeriod;
            $attr['onChangeReload'] = 'changeAcademicPeriodId';
        } else if ($action == 'reconfirm') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->academic_period_id;
            $attr['attr']['value'] = $this->AcademicPeriods->get($attr['value'])->name;
        }

        return $attr;
    }

    public function addOnChangeAcademicPeriodId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('examination_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['examination_id']);
                }
                if (array_key_exists('institution_class_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['institution_class_id']);
                }
            }
        }
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, $request) {
        $examinationOptions = [];

        if ($action == 'add') {
            $todayDate = Time::now();
            if(!empty($request->data[$this->alias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }

            $Examinations = $this->Examinations;
            $examinationOptions = $Examinations->find('list')
                ->where([$Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
                ->toArray();
            $examinationId = isset($request->data[$this->alias()]['examination_id']) ? $request->data[$this->alias()]['examination_id'] : null;
            $this->advancedSelectOptions($examinationOptions, $examinationId, [
                'message' => '{{label}} - ' . $this->getMessage('InstitutionExaminationStudents.notAvailableForRegistration'),
                'selectOption' => false,
                'callable' => function($id) use ($Examinations, $todayDate) {
                    return $Examinations
                        ->find()
                        ->where([
                            $Examinations->aliasField('id') => $id,
                            $Examinations->aliasField('registration_start_date <=') => $todayDate,
                            $Examinations->aliasField('registration_end_date >=') => $todayDate
                        ])
                        ->count();
                }
            ]);
            $attr['options'] = $examinationOptions;
            $attr['onChangeReload'] = 'changeExaminationId';
        } else if ($action == 'reconfirm') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->examination_id;
            $attr['attr']['value'] = $this->Examinations->get($attr['value'])->name;
        }


        return $attr;
    }

    public function addOnChangeExaminationId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('institution_class_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['institution_class_id']);
                }
            }
        }
    }

    public function onUpdateFieldExaminationEducationGrade(Event $event, array $attr, $action, $request) {
        $educationGrade = '';
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $selectedExamination = $request->data[$this->alias()]['examination_id'];
                $Examinations = $this->Examinations
                    ->get($selectedExamination, [
                        'contain' => ['EducationGrades']
                    ])
                    ->toArray();

                $educationGrade = $Examinations['education_grade']['name'];
                $this->request->data[$this->alias()]['education_grade_id'] = $Examinations['education_grade']['id'];
            }
        } else if ($action == 'reconfirm') {
            $educationGradeId = $this->Examinations->get($attr['entity']->examination_id)->education_grade_id;
            $educationGrade = __($this->Examinations->EducationGrades->get($educationGradeId)->name);
        }
        $attr['attr']['value'] = $educationGrade;
        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, $request) {
        $classes = [];
        $InstitutionClass = TableRegistry::get('Institution.InstitutionClasses');
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $institutionId = $attr['entity']->institution_id;
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $educationGradeId = $this->Examinations->get($examinationId)->education_grade_id;
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $classes = $InstitutionClass
                    ->find('list')
                    ->matching('ClassGrades')
                    ->where([$InstitutionClass->aliasField('institution_id') => $institutionId,
                        $InstitutionClass->aliasField('academic_period_id') => $academicPeriodId,
                        'ClassGrades.education_grade_id' => $educationGradeId])
                    ->order($InstitutionClass->aliasField('name'))
                    ->toArray();
            }
            $attr['options'] = $classes;
        } else if ($action == 'reconfirm') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->institution_class_id;
            $attr['attr']['value'] = $InstitutionClass->get($attr['value'])->name;
        }
        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request) {
        $students = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id']) && !empty($request->data[$this->alias()]['institution_class_id'])) {
                $institutionId = $attr['entity']->institution_id;
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $institutionClassId = $request->data[$this->alias()]['institution_class_id'];
                $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $examinationId = $request->data[$this->alias()]['examination_id'];

                $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
                $students = $ClassStudents->find()
                    ->matching('EducationGrades')
                    ->leftJoin(['InstitutionExaminationStudents' => 'examination_centres_examinations_students'], [
                        'InstitutionExaminationStudents.examination_id' => $examinationId,
                        'InstitutionExaminationStudents.student_id = '.$ClassStudents->aliasField('student_id')
                    ])
                    ->innerJoin(['ExaminationCentres' => 'examination_centres'], [
                        'ExaminationCentres.id = InstitutionExaminationStudents.examination_centre_id'
                    ])
                    ->contain('Users.SpecialNeeds.SpecialNeedsTypes')
                    ->where([
                        $ClassStudents->aliasField('institution_id') => $institutionId,
                        $ClassStudents->aliasField('academic_period_id') => $academicPeriodId,
                        $ClassStudents->aliasField('institution_class_id') => $institutionClassId,
                        $ClassStudents->aliasField('student_status_id') => $enrolledStatus,
                        'InstitutionExaminationStudents.student_id IS NOT NULL'
                    ])
                    ->select(['examination_centre_id' => 'InstitutionExaminationStudents.examination_centre_id', 'registration_number' => 'InstitutionExaminationStudents.registration_number'])
                    ->autoFields(true)
                    ->group(['InstitutionExaminationStudents.student_id'])
                    ->toArray();
            }
            $attr['type'] = 'element';
            $attr['element'] = 'Examination.undo_students';
            $attr['data'] = $students;
        } else if ($action == 'reconfirm') {
            $studentIds = $this->Session->read($this->registryAlias().'.confirmStudent');
            if (!empty($studentIds)) {
                $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
                $students = $ClassStudents->find()
                    ->matching('EducationGrades')
                    ->innerJoin(['InstitutionExaminationStudents' => 'examination_centres_examinations_students'], [
                        'InstitutionExaminationStudents.examination_id' => $attr['entity']->examination_id,
                        'InstitutionExaminationStudents.student_id = '.$ClassStudents->aliasField('student_id')
                    ])
                    ->contain('Users.SpecialNeeds.SpecialNeedsTypes')
                    ->where([$ClassStudents->aliasField('student_id').' IN ' => $studentIds])
                    ->group([$ClassStudents->aliasField('student_id')])
                    ->select(['examination_centre_id' => 'InstitutionExaminationStudents.examination_centre_id', 'registration_number' => 'InstitutionExaminationStudents.registration_number'])
                    ->autoFields(true)
                    ->toArray();
            }
            $attr['type'] = 'element';
            $attr['element'] = 'Examination.undo_students';
            $attr['data'] = $students;
        }

        return $attr;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData[$this->alias()]['student_id'] = 0;
        $requestData[$this->alias()]['examination_centre_id'] = 0;
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons) {
        switch ($this->action) {
            case 'add':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Next');
                $cancelUrl = $this->url('index');
                $cancelUrl['action'] = 'ExaminationStudents';
                $cancelUrl = array_diff_key($cancelUrl, $this->request->query);
                $buttons[1]['url'] = $cancelUrl;
                break;

            case 'reconfirm':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Confirm');
                $cancelUrl = $this->url('add');
                $cancelUrl = array_diff_key($cancelUrl, $this->request->query);
                $buttons[1]['url'] = $cancelUrl;
                break;
        }
    }

    public function reconfirm(Event $event, ArrayObject $extra)
    {
        $extra['redirect'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'ExaminationStudents'
        ];
        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $entity = $this->newEntity();
        $this->Alert->info('general.reconfirm');
        if ($this->request->is(['post', 'put'])) {
            $requestData = new ArrayObject($this->request->data);
            $submit = isset($requestData['submit']) ? $requestData['submit'] : 'save';
            if ($submit == 'save') {
                $examStudents = $requestData[$this->alias()]['examination_students'];
                $examinationId = $requestData[$this->alias()]['examination_id'];

                $students = [];
                $entity->errors('student_id', 'No selected students');
                if (!empty($examStudents)) {
                    $students = array_column($examStudents, 'student_id');
                    $examinationCentres = array_unique(array_column($examStudents, 'examination_centre_id'));

                    $deleteStudentEntity = $this->find()
                        ->where([$this->aliasField('student_id').' IN ' => $students, $this->aliasField('examination_id') => $examinationId])
                        ->toArray();

                    $ExamCentreStudents = TableRegistry::get('Examination.ExamCentreStudents');
                    foreach ($deleteStudentEntity as $deleteStudent) {
                        $ExamCentreStudents->delete($deleteStudent);
                    }

                    // event to delete all associated records for student
                    $listeners[] = TableRegistry::get('Examination.ExaminationCentresExaminationsStudents');
                    $this->dispatchEventToModels('Model.Examinations.afterUnregister', [$students, $examinationId, $examinationCentres], $this, $listeners);

                    $this->Alert->success($this->aliasField('success'));
                    $session = $this->Session;
                    $session->delete($this->registryAlias());
                    $event->stopPropagation();
                    return $this->controller->redirect($extra['redirect']);
                }
                $this->Alert->success($this->aliasField('fail'));
            }
        }
        $event = $this->dispatchEvent('ControllerAction.Model.add.afterAction', [$entity, $extra], $this);
        $this->controller->set('data', $entity);
        return $entity;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) {
            return false;
        };

        if (!empty($entity->errors())) {
            return $process;
        }

        if ($entity->has('examination_students')) {
            $students = $entity->examination_students;

            $selectedStudents = [];
            foreach ($students as $key => $student) {
                if ($student['selected'] == 1) {
                    $selectedStudents[] = $student['student_id'];
                }
            }

            if (!empty($selectedStudents)) {
                $extra['redirect'] = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'UndoExaminationRegistration',
                    'reconfirm'
                ];
                $session = $this->Session;
                $session->write($this->registryAlias().'.confirm', $entity);
                $session->write($this->registryAlias().'.confirmStudent', $selectedStudents);
                $event->stopPropagation();
                return $this->controller->redirect($extra['redirect']);
            }
            $this->Alert->warning($this->aliasField('noStudentSelected'));
            $entity->errors('student_id', __('There are no students selected'));
            return $process;
        } else {
            $this->Alert->warning($this->aliasField('noStudentSelected'));
            $entity->errors('student_id', __('There are no students selected'));
            return $process;
        }
    }
}

<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Controller\Component;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Traits\HtmlTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class ExaminationCentreRoomsTable extends ControllerActionTable {
    use HtmlTrait;

    private $examCentreId = null;
    private $studentOptions = [];
    private $studentLists = [];
    private $invigilatorOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Area.Areapicker');
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsToMany('Invigilators', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centre_rooms_invigilators',
            'foreignKey' => 'examination_centre_room_id',
            'targetForeignKey' => 'invigilator_id',
            'through' => 'Examination.ExaminationCentreRoomsInvigilators',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centre_room_students',
            'foreignKey' => 'examination_centre_room_id',
            'targetForeignKey' => 'student_id',
            'through' => 'Examination.ExaminationCentreRoomStudents',
            'dependent' => true
        ]);
        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $queryString = $request->query['queryString'];
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres'];
        $overviewUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'view', 'queryString' => $queryString];

        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
        $Navigation->substituteCrumb('Exam Centre Rooms', 'Exam Centres', $overviewUrl);
        $Navigation->addCrumb('Rooms');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('name', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_centre_id']]],
                'provider' => 'table'
            ])
            ->add('size', 'ruleValidateNumeric',  [
                'rule' => ['numericPositive']
            ])
            ->add('size', 'ruleRoomSize',  [
                'rule'  => ['range', 1, 2147483647]
            ])
            ->add('number_of_seats', 'ruleValidateNumeric',  [
                'rule' => ['numericPositive']
            ])
            ->add('number_of_seats', 'ruleSeatsNumber',  [
                'rule'  => ['range', 1, 2147483647]
            ])
            ->add('number_of_seats', 'ruleExceedRoomCapacity', [
                'rule' => 'validateRoomCapacity'
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
        $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Rooms'));
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if (is_null($this->examCentreId)) {
            $event->stopPropagation();
            $this->controller->redirect(['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'index']);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('examination_id', ['visible' => false]);
        $this->field('examination_centre_id', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('invigilators', ['type' => 'integer', 'after' => 'number_of_seats']);
        $this->field('students', ['type' => 'integer', 'after' => 'invigilators']);
    }

    public function onGetInvigilators(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return count($entity->invigilators);
        }
    }

    public function onGetStudents(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return count($entity->students);
        }
    }

    private function getExamCentreStudentsList()
    {
        $ExaminatonCentreStudents = TableRegistry::get('Examination.ExaminationCentreStudents');
        $list = $ExaminatonCentreStudents
                ->find()
                ->matching('Users')
                ->leftJoin(['ExaminationCentreRoomStudents' => 'examination_centre_room_students'], [
                    'ExaminationCentreRoomStudents.student_id = '.$ExaminatonCentreStudents->aliasField('student_id')
                ])
                ->where(['ExaminationCentreRoomStudents.student_id IS NULL', $ExaminatonCentreStudents->aliasField('examination_centre_id') => $this->examCentreId])
                ->group([
                    $ExaminatonCentreStudents->aliasField('student_id')
                ])
                ->order(['Users.first_name']);

        if ($this->action == 'edit') {
            $examCentreRoomId = $this->paramsDecode($this->paramsPass(0))['id'];
            $list = $list->orWhere(['ExaminationCentreRoomStudents.examination_centre_room_id' => $examCentreRoomId]);
        }
        $options = [];
        $studentDetails = [];
        foreach($list as $students) {
            $options[$students->student_id] = $students['_matchingData']['Users']['name_with_id'];
            $studentDetails[$students->student_id] = [
                'examination_centre_id' => $students->examination_centre_id,
                'institution_id' => $students->institution_id,
                'education_grade_id' => $students->education_grade_id,
                'academic_period_id' => $students->academic_period_id,
                'examination_id' => $students->examination_id
            ];
        }

        return [$options, $studentDetails];
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $examinationCentre = $this->ExaminationCentres->get($this->examCentreId);
        $this->field('examination_id', ['type' => 'hidden', 'value' => $examinationCentre->examination_id]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $examinationCentre->id, 'attr' => ['value' => $examinationCentre->name]]);
        $this->field('academic_period_id', ['type' => 'hidden', 'value' => $examinationCentre->academic_period_id]);

    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        list($this->studentOptions, $this->studentLists) = $this->getExamCentreStudentsList();
        $this->invigilatorOptions = $this->getExamCentreInvigilatorList();
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('size');
        $this->field('number_of_seats');
        $examCentre = $this->ExaminationCentres->get($this->ControllerAction->getQueryString('examination_centre_id'), [
            'contain' => ['AcademicPeriods', 'Examinations']
        ]);
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $examCentre->academic_period_id, 'attr' => ['value' => $examCentre->academic_period->name]]);
        $this->field('examination_id', ['type' => 'readonly', 'value' => $examCentre->examination_id, 'attr' => ['value' => $examCentre->examination->name]]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $examCentre->id, 'attr' => ['value' => $examCentre->code_name]]);
        $this->field('invigilators', ['type' => 'chosenSelect', 'after' => 'examination_centre_id', 'options' => $this->invigilatorOptions]);
        $this->field('students', ['type' => 'chosenSelect', 'options' => $this->studentOptions]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('invigilators', ['type' => 'element', 'element' => 'Examination.exam_centre_room_invigilators', 'data' => $entity]);
        $ExaminationCentreRoomStudents = TableRegistry::get('Examination.ExaminationCentreRoomStudents');
        $examCentreRoomId = $this->paramsDecode($this->paramsPass(0))['id'];
        $examinationCentreRoomStudentList = $ExaminationCentreRoomStudents->findByExaminationCentreRoomId($examCentreRoomId)
            ->find('list', [
                'keyField' => 'student_id',
                'valueField' => 'registration_no'
            ])
            ->innerJoin(['ExaminationCentreStudents' => 'examination_centre_students'], [
                    'ExaminationCentreStudents.examination_centre_id = '.$ExaminationCentreRoomStudents->aliasField('examination_centre_id'),
                    'ExaminationCentreStudents.student_id = '.$ExaminationCentreRoomStudents->aliasField('student_id'),
                ])
            ->select(['registration_no' =>'ExaminationCentreStudents.registration_number', 'student_id' => $ExaminationCentreRoomStudents->aliasField('student_id')])
            ->group(['ExaminationCentreStudents.student_id'])
            ->toArray();

        $this->field('students', ['type' => 'element', 'element' => 'Examination.exam_centre_room_students', 'data' => $entity, 'registrationNoList' => $examinationCentreRoomStudentList, 'after' => 'invigilators']);
        $this->setFieldOrder(['name', 'size', 'number_of_seats', 'academic_period_id', 'examination_id', 'examination_centre_id', 'invigilators', 'students']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('size');
        $this->field('number_of_seats');
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $entity->academic_period_id, 'attr' => ['value' => $entity->academic_period->name]]);
        $this->field('examination_id', ['type' => 'readonly', 'value' => $entity->examination_id, 'attr' => ['value' => $entity->examination->name]]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $entity->examination_centre_id, 'attr' => ['value' => $entity->examination_centre->code_name]]);
        $this->field('invigilators', ['type' => 'chosenSelect', 'options' => $this->invigilatorOptions]);
        $this->field('students', ['type' => 'chosenSelect', 'options' => $this->studentOptions]);

        $this->setFieldOrder(['name', 'size', 'number_of_seats', 'academic_period_id', 'examination_id', 'examination_centre_id', 'invigilators', 'students']);
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request)
    {
        $ExaminatonCentreStudents = TableRegistry::get('Examination.ExaminationCentreStudents');
        $list = $ExaminatonCentreStudents
                ->find()
                ->matching('Users')
                ->leftJoin(['ExaminationCentreRoomStudents' => 'examination_centre_room_students'], [
                    'ExaminationCentreRoomStudents.student_id = '.$ExaminatonCentreStudents->aliasField('student_id')
                ])
                ->where(['ExaminationCentreRoomStudents.student_id IS NULL', $ExaminatonCentreStudents->aliasField('examination_centre_id') => $this->examCentreId])
                ->group([
                    $ExaminatonCentreStudents->aliasField('student_id')
                ])
                ->order(['Users.first_name'])
                ->all();
        $options = [];
        $examCentreStudentId = [];
        if (isset($this->request->data[$this->alias()]['students'])) {
            $examCentreStudentId = Hash::extract($this->request->data[$this->alias()]['students'], '{n}.id');
        }
        foreach($list as $students) {
            if (!in_array($students->id, $examCentreStudentId)) {
                $options[$students->id] = $students['_matchingData']['Users']['name_with_id'];
            }
        }

        $attr['options'] = ['' => '-- '.__('Select One').' --'] + $options;
        $attr['onChangeReload'] = 'AddStudents';
        $attr['attr']['multiple'] = false;
        return $attr;
    }

    public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request)
    {
        $ExaminatonCentreStudents = TableRegistry::get('Examination.ExaminationCentreStudents');
        $list = $ExaminatonCentreStudents
                ->find()
                ->matching('Users')
                ->leftJoin(['ExaminationCentreRoomStudents' => 'examination_centre_room_students'], [
                    'ExaminationCentreRoomStudents.student_id = '.$ExaminatonCentreStudents->aliasField('student_id')
                ])
                ->where(['ExaminationCentreRoomStudents.student_id IS NULL', $ExaminatonCentreStudents->aliasField('examination_centre_id') => $this->examCentreId])
                ->group([
                    $ExaminatonCentreStudents->aliasField('student_id')
                ])
                ->order(['Users.first_name'])
                ->all();
        $options = [];
        $examCentreStudentId = [];
        if (isset($this->request->data[$this->alias()]['students'])) {
            $examCentreStudentId = Hash::extract($this->request->data[$this->alias()]['students'], '{n}.id');
        }
        foreach($list as $students) {
            if (!in_array($students->id, $examCentreStudentId)) {
                $options[$students->id] = $students['_matchingData']['Users']['name_with_id'];
            }
        }
        $attr['placeholder'] = __('Select Students');
        return $attr;
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action)
    {
        if ($action == 'edit') {
            $includes['autocomplete'] = [
                'include' => true,
                'css' => ['OpenEmis.../plugins/autocomplete/css/autocomplete'],
                'js' => ['OpenEmis.../plugins/autocomplete/js/autocomplete']
            ];
        }
    }

    public function addEditOnAddStudents(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'students';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }
        if ($data->offsetExists($alias)) {
            if (array_key_exists('student_id', $data[$alias]) && !empty($data[$alias]['student_id'])) {
                $id = $data[$alias]['student_id'];

                try {
                    $obj = $this->ExaminationCentres->ExaminationCentreStudents->findById($id)->contain(['Users'])->first();

                    $data[$alias][$fieldKey][] = [
                        'id' => $obj->id,
                        '_joinData' => ['openemis_no' => $obj->user->openemis_no, 'student_id' => $obj->student_id, 'institution_id' => $obj->institution_id, 'education_grade_id' => $obj->education_grade_id, 'name' => $obj->user->name]
                    ];
                } catch (RecordNotFoundException $ex) {
                    Log::write('debug', __METHOD__ . ': Record not found for id: ' . $id);
                }
            }
        }

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'Students' => ['validate' => false]
        ];
    }

    private function getExamCentreInvigilatorList()
    {
        $ExaminationCentresInvigilators = TableRegistry::get('Examination.ExaminationCentresInvigilators');
        $list = $ExaminationCentresInvigilators
                ->find()
                ->matching('Invigilators')
                ->leftJoin(['ExaminationCentreRoomsInvigilators' => 'examination_centre_rooms_invigilators'], [
                    'ExaminationCentreRoomsInvigilators.invigilator_id = '.$ExaminationCentresInvigilators->aliasField('invigilator_id')
                ])
                ->where(['ExaminationCentreRoomsInvigilators.invigilator_id IS NULL', $ExaminationCentresInvigilators->aliasField('examination_centre_id') => $this->examCentreId])
                ->group([
                    $ExaminationCentresInvigilators->aliasField('invigilator_id')
                ])
                ->order(['Invigilators.first_name']);

        if ($this->action == 'edit') {
            $examCentreRoomId = $this->paramsDecode($this->paramsPass(0))['id'];
            $list = $list->orWhere(['ExaminationCentreRoomsInvigilators.examination_centre_room_id' => $examCentreRoomId]);
        }
        $options = [];
        $studentDetails = [];
        foreach($list as $invigilator) {
            $options[$invigilator->invigilator_id] = $invigilator['_matchingData']['Invigilators']['name_with_id'];
        }

        return $options;
    }

    public function onUpdateFieldInvigilators(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
        } else if ($action == 'add' || $action == 'edit') {
            $attr['placeholder'] = __('Select Invigilators');
        }

        return $attr;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'Invigilators' => [
                    'sort' => ['Invigilators.first_name' => 'ASC', 'Invigilators.last_name' => 'ASC']
                ],
                'Students' => [
                    'sort' => ['Students.first_name' => 'ASC', 'Students.last_name' => 'ASC']
                ],
                'AcademicPeriods', 'Examinations', 'ExaminationCentres']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Invigilators', 'Students'])
            ->where([$this->aliasField('examination_centre_id') => $this->examCentreId]);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        // $options['associated'] = ['Students._joinData' => ['validate' => false]];
        $options['associated']['Students._joinData'] = ['validate' => false];
        $options['associated']['Invigilators._joinData'] = ['validate' => false];

        $data[$this->alias()]['invigilators'] = $this->processInvigilators($data, 'add');
        $data[$this->alias()]['students'] = $this->processStudents($data, 'add');
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $options['associated'][] = 'Students._joinData';
        $options['associated'][] = 'Invigilators._joinData';

        $data[$this->alias()]['invigilators'] = $this->processInvigilators($data, 'edit');
        $data[$this->alias()]['students'] = $this->processStudents($data, 'edit');
    }

    public function processStudents(ArrayObject $data, $action)
    {
        $students = [];
        if (isset($data[$this->alias()]['students']['_ids']) && !empty($data[$this->alias()]['students']['_ids'])) {
            foreach ($data[$this->alias()]['students']['_ids'] as $key => $value) {
                $joinData = [
                    'examination_centre_id' => $data[$this->alias()]['examination_centre_id'],
                    'institution_id' => $this->studentLists[$value]['institution_id'],
                    'education_grade_id' => $this->studentLists[$value]['education_grade_id'],
                    'academic_period_id' => $data[$this->alias()]['academic_period_id'],
                    'examination_id' => $data[$this->alias()]['examination_id']
                ];

                if ($action == 'edit') {
                    $joinData['student_id'] = $value;
                    $joinData['examination_centre_room_id'] = $data[$this->alias()]['id'];
                }


                $students[] = [
                    'id' => $value,
                    '_joinData' => $joinData
                ];
            }
            unset($data[$this->alias()]['students']['_ids']);
        }

        return $students;
    }

    public function processInvigilators(ArrayObject $data, $action)
    {
        $invigilators = [];

        if (isset($data[$this->alias()]['invigilators']['_ids']) && !empty($data[$this->alias()]['invigilators']['_ids'])) {
            foreach ($data[$this->alias()]['invigilators']['_ids'] as $key => $value) {
                $joinData = [
                    'academic_period_id' => $data[$this->alias()]['academic_period_id'],
                    'examination_id' => $data[$this->alias()]['examination_id'],
                    'examination_centre_id' => $data[$this->alias()]['examination_centre_id']
                ];
                if ($action == 'edit') {
                    $joinData['invigilator_id'] = $value;
                    $joinData['examination_centre_room_id'] = $data[$this->alias()]['id'];
                }
                $invigilators[] = [
                    'id' => $value,
                    '_joinData' => $joinData
                ];
            }

            unset($data[$this->alias()]['invigilators']['_ids']);
        }

        return $invigilators;
    }
}

<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
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

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('name', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_centre_id']]],
                'provider' => 'table'
            ])
            ->add('number_of_seats', 'ruleExceedRoomCapacity', [
                'rule' => 'validateRoomCapacity'
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');
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
        } else if ($this->action == 'view') {
            $invigilatorList = [];
            foreach ($entity->invigilators as $key => $obj) {
                $invigilatorList[] = $obj->name_with_id;
            }

            return implode(', ', $invigilatorList);
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
                ->order(['Users.first_name'])
                ->all();
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
        $this->field('invigilators', ['type' => 'chosenSelect', 'after' => 'examination_centre_id']);
        $this->field('students', ['type' => 'chosenSelect', 'options' => $this->studentOptions]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('invigilators', ['type' => 'chosenSelect', 'after' => 'examination_centre_id']);
        $this->field('students', ['type' => 'element', 'element' => 'Examination.exam_centre_room_students', 'data' => $entity, 'after' => 'invigilators']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('size');
        $this->field('number_of_seats');
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $entity->academic_period_id, 'attr' => ['value' => $entity->academic_period->name]]);
        $this->field('examination_id', ['type' => 'readonly', 'value' => $entity->examination_id, 'attr' => ['value' => $entity->examination->name]]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $entity->examination_centre_id, 'attr' => ['value' => $entity->examination_centre->code_name]]);
        $this->field('invigilators', ['type' => 'chosenSelect']);
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

    public function onUpdateFieldInvigilators(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
        } else if ($action == 'add' || $action == 'edit') {
            $examCentreEntity = $this->ExaminationCentres
                ->find()
                ->contain([
                    'Invigilators' => [
                        'sort' => ['Invigilators.first_name' => 'ASC', 'Invigilators.last_name' => 'ASC']
                    ]
                ])
                ->where([$this->ExaminationCentres->aliasField('id') => $this->examCentreId])
                ->first();

            $invigilatorOptions = [];
            if ($examCentreEntity->has('invigilators')) {
                foreach ($examCentreEntity->invigilators as $key => $obj) {
                    $invigilatorOptions[$obj->id] = $obj->name_with_id;
                }
            }

            $attr['placeholder'] = __('Select Invigilators');
            $attr['options'] = $invigilatorOptions;
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

    public function processStudents(ArrayObject $data)
    {
        $students = [];
        if (isset($data[$this->alias()]['students']['_ids']) && !empty($data[$this->alias()]['students']['_ids'])) {
            foreach ($data[$this->alias()]['students']['_ids'] as $key => $value) {
                $students[] = [
                    'id' => $value,
                    '_joinData' => [
                        'examination_centre_id' => $data[$this->alias()]['examination_centre_id'],
                        'institution_id' => $this->studentLists[$value]['institution_id'],
                        'education_grade_id' => $this->studentLists[$value]['education_grade_id'],
                        'academic_period_id' => $data[$this->alias()]['academic_period_id'],
                        'examination_id' => $data[$this->alias()]['examination_id']
                    ]
                ];
            }
            unset($data[$this->alias()]['students']['_ids']);
        }

        return $students;
    }

    public function processInvigilators(ArrayObject $data)
    {
        $invigilators = [];

        if (isset($data[$this->alias()]['invigilators']['_ids']) && !empty($data[$this->alias()]['invigilators']['_ids'])) {
            foreach ($data[$this->alias()]['invigilators']['_ids'] as $key => $value) {
                $invigilators[] = [
                    'id' => $value,
                    '_joinData' => [
                        'academic_period_id' => $data[$this->alias()]['academic_period_id'],
                        'examination_id' => $data[$this->alias()]['examination_id'],
                        'examination_centre_id' => $data[$this->alias()]['examination_centre_id']
                    ]
                ];
            }

            unset($data[$this->alias()]['invigilators']['_ids']);
        }

        return $invigilators;
    }
}

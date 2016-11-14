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

class ExaminationCentreRoomsTable extends ControllerActionTable {
    use HtmlTrait;

    private $examCentreId = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Area.Areapicker');
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
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
        $this->field('students', ['type' => 'integer', 'after' => 'number_of_seats']);
    }

    public function onGetStudents(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return count($entity->students);
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $examinationCentre = $this->ExaminationCentres->get($this->examCentreId);
        $this->field('examination_id', ['type' => 'hidden', 'value' => $examinationCentre->examination_id]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $examinationCentre->id, 'attr' => ['value' => $examinationCentre->name]]);
        $this->field('academic_period_id', ['type' => 'hidden', 'value' => $examinationCentre->academic_period_id]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('students', ['type' => 'element', 'element' => 'Examination.exam_centre_room_students', 'data' => $entity, 'after' => 'examination_centre_id']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('size');
        $this->field('number_of_seats');
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $entity->academic_period_id, 'attr' => ['value' => $entity->academic_period->name]]);
        $this->field('examination_id', ['type' => 'readonly', 'value' => $entity->examination_id, 'attr' => ['value' => $entity->examination->name]]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $entity->examination_centre_id, 'attr' => ['value' => $entity->examination_centre->code_name]]);
        $this->field('students', ['type' => 'students', 'after' => 'examination_centre_id']);
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
        $this->field('students', ['type' => 'students', 'after' => 'examination_centre_id']);
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxStudentAutocomplete'] = 'ajaxStudentAutocomplete';
        return $events;
    }

    public function ajaxStudentAutocomplete()
    {
        $examCentreId = $this->paramsPass(0);
        $this->controller->autoRender = false;
        $this->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];
            // autocomplete
            $data = [];
            $search = sprintf('%s%%', $term);

            $ExaminatonCentreStudents = $this->ExaminationCentres->ExaminationCentreStudents;

            $list = $ExaminatonCentreStudents
                ->find()
                ->matching('Users', function($q) use ($search) {
                    return $q
                        ->find('all')
                        ->where([
                            'OR' => [
                                'Users.openemis_no LIKE' => $search,
                                'Users.first_name LIKE' => $search,
                                'Users.middle_name LIKE' => $search,
                                'Users.third_name LIKE' => $search,
                                'Users.last_name LIKE' => $search
                            ]
                        ]);
                })
                ->leftJoin(['ExaminationCentreRoomStudents' => 'examination_centre_room_students'], [
                    'ExaminationCentreRoomStudents.student_id = '.$ExaminatonCentreStudents->aliasField('student_id')
                ])
                ->where(['ExaminationCentreRoomStudents.student_id IS NULL', $ExaminatonCentreStudents->aliasField('examination_centre_id') => $examCentreId])
                ->group([
                    $ExaminatonCentreStudents->aliasField('student_id')
                ])
                ->order(['Users.first_name'])
                ->all();

            foreach($list as $obj) {
                $_matchingData = $obj->_matchingData['Users'];
                $data[] = [
                    'label' => sprintf('%s - %s', $_matchingData->openemis_no, $_matchingData->name),
                    'value' => $obj->id
                ];
            }
            // End

            echo json_encode($data);
            die;
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

    public function onGetStudentsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $tableHeaders = [__('OpenEMIS ID'), __('Student'), ''];
        $tableCells = [];
        $examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');
        $alias = $this->alias();
        $Form = $event->subject()->Form;
        $key = 'students';
        $Form->unlockField('ExaminationCentreRooms.students');
        $Form->unlockField('ExaminationCentreRooms.student_id');

        if ($this->request->is(['get'])) {
            if (!array_key_exists($alias, $this->request->data)) {
                $this->request->data[$alias] = [$key => []];
            } else {
                $this->request->data[$alias][$key] = [];
            }

            $associated = $entity->extractOriginal([$key]);
            if (!empty($associated[$key])) {
                foreach ($associated[$key] as $i => $obj) {
                    $this->request->data[$alias][$key][$obj->id] = [
                        'id' => $obj->id,
                        '_joinData' => ['openemis_no' => $obj->openemis_no, 'student_id' => $obj->id, 'name' => $obj->name, 'institution_id' => $obj->institution_id, 'education_grade_id' => $obj->education_grade_id]
                    ];
                }
            }
        }

        if ($action == 'add') {
            $examCentre = $this->ExaminationCentres->get($examCentreId);
            $entity->academic_period_id = $examCentre->academic_period_id;
            $entity->examination_id = $examCentre->examination_id;
            $entity->examination_centre_id = $examCentre->examination_centre_id;
        }

        // refer to addEditOnAddTrainee for http post
        if ($this->request->data("$alias.$key")) {
            $associated = $this->request->data("$alias.$key");

            foreach ($associated as $i => $obj) {
                $joinData = $obj['_joinData'];
                $rowData = [];
                $name = $joinData['name'];
                $name .= $Form->hidden("$alias.$key.$i.id", ['value' => $joinData['student_id']]);
                $name .= $Form->hidden("$alias.$key.$i._joinData.openemis_no", ['value' => $joinData['openemis_no']]);
                $name .= $Form->hidden("$alias.$key.$i._joinData.student_id", ['value' => $joinData['student_id']]);
                $name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
                $name .= $Form->hidden("$alias.$key.$i._joinData.examination_centre_room_id", ['value' => $entity->id]);
                $name .= $Form->hidden("$alias.$key.$i._joinData.academic_period_id", ['value' => $entity->academic_period_id]);
                $name .= $Form->hidden("$alias.$key.$i._joinData.examination_id", ['value' => $entity->examination_id]);
                $name .= $Form->hidden("$alias.$key.$i._joinData.examination_centre_id", ['value' => $entity->examination_centre_id]);
                $name .= $Form->hidden("$alias.$key.$i._joinData.institution_id", ['value' => $joinData['institution_id']]);
                $name .= $Form->hidden("$alias.$key.$i._joinData.education_grade_id", ['value' => $joinData['education_grade_id']]);
                $rowData[] = [$joinData['openemis_no'], ['autocomplete-exclude' => $joinData['student_id']]];
                $rowData[] = $name;
                $rowData[] = $this->getDeleteButton();
                $tableCells[] = $rowData;
            }
        }

        return $event->subject()->renderElement('Examination.exam_centre_room_students', ['tableHeaders' => $tableHeaders, 'tableCells' => $tableCells, 'examCentreId' => $examCentreId]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'Students' => [
                    'sort' => ['Students.first_name' => 'ASC', 'Students.last_name' => 'ASC']
                ],
                'AcademicPeriods', 'Examinations', 'ExaminationCentres']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Students'])
            ->where([$this->aliasField('examination_centre_id') => $this->examCentreId]);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $options['associated'] = ['Students._joinData' => ['validate' => false]];
        if (!isset($data['ExaminationCentreRooms']['students'])) {
            $data['ExaminationCentreRooms']['students'] = [];
        }
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $options['associated'][] = 'Students._joinData';
        if (!isset($data['ExaminationCentreRooms']['students'])) {
            $data['ExaminationCentreRooms']['students'] = [];
        }
    }
}

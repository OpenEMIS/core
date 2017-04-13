<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Utility\Text;
use Cake\I18n\Time;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class ExamCentreStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    private $queryString;
    private $examCentreId;
    private $examCentreRoomStudents = [];

    public function initialize(array $config) {
        $this->table('examination_centres_examinations_students');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
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
            'cascadeCallBack' => true
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index', 'add']
        ]);
        $this->addBehavior('CompositeKey');
        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.onGetFieldLabel'] = 'onGetFieldLabel';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
            ->requirePresence('examination_centre_room_id');
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $this->queryString = $request->query['queryString'];
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres'];
        $overviewUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'view', 'queryString' => $this->queryString];

        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
        $Navigation->substituteCrumb('Exam Centre Students', 'Examination Centre', $overviewUrl);
        $Navigation->addCrumb('Students');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
        $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Students'));

        $this->fields['examination_id']['type'] = 'string';
        $this->fields['student_id']['type'] = 'string';
        $this->fields['academic_period_id']['visible'] = false;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if (is_null($this->examCentreId)) {
            $event->stopPropagation();
            $this->Alert->error('general.notExists', ['reset' => 'override']);
            $this->controller->redirect(['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'index']);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('room');
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->fields['student_id']['sort'] = ['field' => 'Users.first_name'];
        $this->fields['examination_id']['sort'] = false;
        $this->setFieldOrder(['registration_number', 'openemis_no', 'student_id', 'institution_id', 'examination_id', 'room']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // set queryString for page refresh
        $this->controller->set('queryString', $this->queryString);

        // Examination filter
        $ExaminationCentresExaminations = $this->ExaminationCentresExaminations;
        $examinationOptions = $this->ExaminationCentresExaminations
            ->find('list', [
                'keyField' => 'examination_id',
                'valueField' => 'examination.code_name'
            ])
            ->contain('Examinations')
            ->where([$ExaminationCentresExaminations->aliasField('examination_centre_id') => $this->examCentreId])
            ->toArray();

        $examinationOptions = ['-1' => '-- '.__('Select Examination').' --'] + $examinationOptions;
        $selectedExamination = !is_null($this->request->query('examination_id')) ? $this->request->query('examination_id') : -1;
        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
           $where[$this->aliasField('examination_id')] = $selectedExamination;
        }

        // Room filter
        $ExamCentreRooms = TableRegistry::get('Examination.ExaminationCentreRooms');
        $roomOptions = $ExamCentreRooms->find('list')
            ->where([$ExamCentreRooms->aliasField('examination_centre_id') => $this->examCentreId])
            ->toArray();
        $roomOptions = ['0' => __('All Rooms'), '-1' => __('Students without Room')] + $roomOptions;
        $selectedRoom = !is_null($this->request->query('examination_centre_room_id')) ? $this->request->query('examination_centre_room_id') : 0;
        $this->controller->set(compact('roomOptions', 'selectedRoom'));

        if ($selectedRoom > 0) {
            $query->matching('ExaminationCentreRoomsExaminationsStudents');
            $where['ExaminationCentreRoomsExaminationsStudents.examination_centre_room_id'] = $selectedRoom;
        } else if ($selectedRoom == -1) {
            $query
                ->leftJoinWith('ExaminationCentreRoomsExaminationsStudents')
                ->where(['ExaminationCentreRoomsExaminationsStudents.examination_centre_room_id IS NULL']);
        }

        // exam centre controls
        $extra['elements']['controls'] = ['name' => 'Examination.ExaminationCentres/controls', 'data' => [], 'options' => [], 'order' => 1];

        $where[$this->aliasField('examination_centre_id')] = $this->examCentreId;
        $extra['auto_contain_fields'] = ['Institutions' => ['code']];
        $query->where([$where]);

        // sort
        $sortList = ['Users.openemis_no', 'Users.first_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => 'Users', 'searchTerm' => $search]);
            $extra['OR'] = $nameConditions; // to be merged with auto_search 'OR' conditions
        }

        $ExamCentreRoomStudents = $this->ExaminationCentreRoomsExaminationsStudents;
        $this->examCentreRoomStudents = $ExamCentreRoomStudents->find('list', [
                'keyField' => 'student_id',
                'valueField' => 'room_name'
            ])
            ->innerJoinWith('ExaminationCentreRooms')
            ->select([$ExamCentreRoomStudents->aliasField('student_id'), 'room_name' => 'ExaminationCentreRooms.name'])
            ->where([$ExamCentreRoomStudents->aliasField('examination_centre_id') => $this->examCentreId])
            ->toArray();
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'registration_number';
        $searchableFields[] = 'student_id';
        $searchableFields['student_id'] = 'openemis_no';
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('identity_number');
        $this->field('room');
        $this->field('openemis_no');
        $this->setFieldOrder(['registration_number', 'openemis_no', 'student_id', 'identity_number', 'institution_id', 'examination_id', 'room']);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
        if ($field == 'identity_number') {
            return __(TableRegistry::get('FieldOption.IdentityTypes')->find()->find('DefaultIdentityType')->first()->name);
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        return $entity->user->identity_number;
    }

    public function onGetRoom(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return isset($this->examCentreRoomStudents[$entity->student_id]) ? $this->examCentreRoomStudents[$entity->student_id] : '';
        } else if ($this->action == 'view') {
            $ExamCentreRoomStudents = $this->ExaminationCentreRoomsExaminationsStudents;
            $examCentreRoomStudents = $ExamCentreRoomStudents->find()
                ->innerJoinWith('ExaminationCentreRooms')
                ->select([$ExamCentreRoomStudents->aliasField('student_id'), 'room_name' => 'ExaminationCentreRooms.name'])
                ->where([
                    $ExamCentreRoomStudents->aliasField('examination_centre_id') => $this->examCentreId,
                    $ExamCentreRoomStudents->aliasField('examination_id') => $entity->examination_id,
                    $ExamCentreRoomStudents->aliasField('student_id') => $entity->student_id
                ])
                ->first();
            if (!empty($examCentreRoomStudents)) {
                return $examCentreRoomStudents->room_name;
            } else {
                return '';
            }
        }
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        if ($entity->institution_id) {
            return $entity->institution->code_name;
        } else {
            return __('Private Candidate');
        }
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Examinations', 'Users', 'AcademicPeriods', 'ExaminationCentres', 'Institutions']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'readonly', 'visible' => true, 'entity' => $entity]);
        $this->field('registration_number', ['type' => 'readonly']);
        $this->field('openemis_no', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('student_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('institution_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('examination_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('examination_centre_room_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->setFieldOrder(['academic_period_id', 'registration_number', 'openemis_no', 'student_id', 'institution_id', 'examination_id', 'examination_centre_id', 'examination_centre_room_id']);
    }


    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $attr['value'] = $attr['entity']->academic_period_id;
        $attr['attr']['value'] = $attr['entity']->academic_period->name;
        return $attr;
    }

    public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $openemisNo = $attr['entity']->user->openemis_no;
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $openemisNo;
            return $attr;
        }
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request)
    {
        $student = $attr['entity']->user->name;
        $attr['value'] = $attr['entity']->student_id;
        $attr['attr']['value'] = $student;
        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $institution = $attr['entity']->institution->code_name;
        $attr['attr']['value'] = $institution;
        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request)
    {
        $examination = $attr['entity']->examination->code_name;
        $attr['value'] = $attr['entity']->examination_id;
        $attr['attr']['value'] = $examination;
        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, Request $request)
    {
        $examinationCentre = $attr['entity']->examination_centre->code_name;
        $attr['value'] = $attr['entity']->examination_centre_id;
        $attr['attr']['value'] = $examinationCentre;
        return $attr;
    }

    public function onUpdateFieldExaminationCentreRoomId(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];

        $ExamCentreRooms = TableRegistry::get('Examination.ExaminationCentreRooms');
        $roomOptions = $ExamCentreRooms->find('list')
            ->where([$ExamCentreRooms->aliasField('examination_centre_id') => $this->examCentreId])
            ->toArray();

        $ExamCentreRoomStudents = $this->ExaminationCentreRoomsExaminationsStudents;
        $room = $ExamCentreRoomStudents->find()
            ->where([
                $ExamCentreRoomStudents->aliasField('examination_centre_id') => $this->examCentreId,
                $ExamCentreRoomStudents->aliasField('examination_id') => $entity->examination_id,
                $ExamCentreRoomStudents->aliasField('student_id') => $entity->student_id
            ])
            ->extract('examination_centre_room_id')
            ->first();

        $attr['type'] = 'select';
        $attr['options'] = $roomOptions;
        $attr['default'] = $room;
        return $attr;
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            $ExamCentreRoomStudents = TableRegistry::get('Examination.ExaminationCentreRoomsExaminationsStudents');
            $conditions = [
                'examination_centre_id' => $requestData[$model->alias()]['examination_centre_id'],
                'examination_id' => $requestData[$model->alias()]['examination_id'],
                'student_id' => $requestData[$model->alias()]['student_id']
            ];

            $existingRecord = $ExamCentreRoomStudents->find()->where($conditions)->first();

            if (isset($requestData[$model->alias()]['examination_centre_room_id']) && !empty($requestData[$model->alias()]['examination_centre_room_id'])) {
                if (!empty($existingRecord)) {
                    // update room
                    $ExamCentreRoomStudents->updateAll(['examination_centre_room_id' => $requestData[$model->alias()]['examination_centre_room_id']], [$conditions]);

                } else {
                    // add new student in room
                    $conditions['examination_centre_room_id'] = $requestData[$model->alias()]['examination_centre_room_id'];
                    $newEntity = $ExamCentreRoomStudents->newEntity($conditions);
                    $ExamCentreRoomStudents->save($newEntity);
                }

            } else {
                // delete student from room if user does not select room option
                if (!empty($existingRecord)) {
                    $ExamCentreRoomStudents->deleteAll([$conditions]);
                }
            }
            return true;
        };

        return $process;
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $examCentreId = $entity->examination_centre_id;
        $studentId = $entity->student_id;
        $this->deleteAll([
            'examination_centre_id' => $examCentreId,
            'student_id' => $studentId
        ]);

        TableRegistry::get('Examination.ExaminationCentreRoomsExaminationsStudents')->deleteAll([
            'examination_centre_id' => $examCentreId,
            'student_id' => $studentId
        ]);

        $studentCount = $this->find()
            ->where([$this->aliasField('examination_centre_id') => $entity->examination_centre_id])
            ->group([$this->aliasField('student_id')])
            ->count();

        $this->ExaminationCentres->updateAll(['total_registered' => $studentCount],['id' => $entity->examination_centre_id]);
    }

    public function findResults(Query $query, array $options) {
        $examinationId = $options['examination_id'];
        $examinationCentreId = $options['examination_centre_id'];
        $examinationItemId = $options['examination_item_id'];

        $Users = $this->Users;
        $SubjectStudents = TableRegistry::get('Examination.ExaminationCentresExaminationsSubjectsStudents');
        $ItemResults = TableRegistry::get('Examination.ExaminationItemResults');

        return $query
            ->select([
                $ItemResults->aliasField('id'),
                $ItemResults->aliasField('marks'),
                $ItemResults->aliasField('examination_grading_option_id'),
                $ItemResults->aliasField('academic_period_id'),
                $this->aliasField('registration_number'),
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $SubjectStudents->aliasField('total_mark'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name')
            ])
            ->matching('Users')
            ->innerJoin(
                [$SubjectStudents->alias() => $SubjectStudents->table()],
                [
                    $SubjectStudents->aliasField('examination_id = ') . $this->aliasField('examination_id'),
                    $SubjectStudents->aliasField('examination_centre_id = ') . $this->aliasField('examination_centre_id'),
                    $SubjectStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $SubjectStudents->aliasField('examination_item_id = ') . $examinationItemId
                ]
            )
            ->leftJoin(
                [$ItemResults->alias() => $ItemResults->table()],
                [
                    $ItemResults->aliasField('examination_id = ') . $this->aliasField('examination_id'),
                    $ItemResults->aliasField('examination_centre_id = ') . $this->aliasField('examination_centre_id'),
                    $ItemResults->aliasField('examination_item_id = ') . $SubjectStudents->aliasField('examination_item_id'),
                    $ItemResults->aliasField('student_id = ') . $this->aliasField('student_id')
                ]
            )
            ->where([
                $this->aliasField('examination_id') => $examinationId,
                $this->aliasField('examination_centre_id') => $examinationCentreId
            ])
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('examination_id')
            ])
            ->order([
                $Users->aliasField('first_name'), $Users->aliasField('last_name')
            ]);
    }
}

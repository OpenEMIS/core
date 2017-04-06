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
            'foreignKey' => ['examination_centre_id', 'student_id', 'examination_id'],
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

        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index', 'add']
        ]);
        $this->addBehavior('CompositeKey');
        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.onGetFieldLabel'] = 'onGetFieldLabel';
        return $events;
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('registration_number')
            ->add('registration_number', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_id']]],
                'provider' => 'table'
            ]);
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $this->queryString = $request->query['queryString'];
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres'];
        $overviewUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'Centres', 'view', 'queryString' => $this->queryString];

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

        $this->field('room');
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_id', ['type' => 'select', 'sort' => ['field' => 'Users.first_name']]);
        $this->fields['examination_id']['type'] = 'string';
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
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $button['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'LinkedInstitutionAddStudents', 'add', 'queryString' => $this->request->query('queryString')];
        $button['type'] = 'button';
        $button['label'] = '<i class="fa kd-add-multiple"></i>';
        $button['attr'] = $toolbarAttr;
        $button['attr']['title'] = __('Bulk Register');
        $extra['toolbarButtons']['bulkAdd'] = $button;

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
        $selectedRecordExamId = $this->ControllerAction->getQueryString('examination_id');
        $selectedExamination = !is_null($this->request->query('examination_id')) ? $this->request->query('examination_id') : $selectedRecordExamId;
        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
           $where[$this->aliasField('examination_id')] = $selectedExamination;
        }

        // Room filter
        $ExamCentreRoomsExams = TableRegistry::get('Examination.ExaminationCentreRoomsExaminations');
        $roomOptions = $ExamCentreRoomsExams->find('list', [
                'keyField' => 'examination_centre_room.id',
                'valueField' => 'examination_centre_room.name'
            ])
            ->contain('ExaminationCentreRooms')
            ->where([
                $ExamCentreRoomsExams->aliasField('examination_id') => $selectedExamination,
                $ExamCentreRoomsExams->aliasField('examination_centre_id') => $this->examCentreId
            ])
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

        //kiv
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

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('identity_number');
        $this->setFieldOrder(['registration_number', 'openemis_no', 'student_id', 'identity_number', 'institution_id', 'room']);
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
        $academicPeriodId = $options['academic_period_id'];
        $examinationId = $options['examination_id'];
        $examinationCentreId = $options['examination_centre_id'];
        $examinationItemId = $options['examination_item_id'];

        $Users = $this->Users;
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
                $this->aliasField('education_grade_id'),
                $this->aliasField('total_mark'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name')
            ])
            ->matching('Users')
            ->leftJoin(
                [$ItemResults->alias() => $ItemResults->table()],
                [
                    $ItemResults->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $ItemResults->aliasField('examination_id = ') . $this->aliasField('examination_id'),
                    $ItemResults->aliasField('examination_centre_id = ') . $this->aliasField('examination_centre_id'),
                    $ItemResults->aliasField('examination_item_id = ') . $this->aliasField('examination_item_id'),
                    $ItemResults->aliasField('student_id = ') . $this->aliasField('student_id')
                ]
            )
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('examination_id') => $examinationId,
                $this->aliasField('examination_centre_id') => $examinationCentreId,
                $this->aliasField('examination_item_id') => $examinationItemId
            ])
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('examination_id')
            ])
            ->order([
                $Users->aliasField('first_name'), $Users->aliasField('last_name')
            ]);
    }
}

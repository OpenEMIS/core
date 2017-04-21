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

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Area.Areapicker');
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsToMany('Examinations', [
            'className' => 'Examination.Examinations',
            'joinTable' => 'examination_centre_rooms_examinations',
            'foreignKey' => 'examination_centre_room_id',
            'targetForeignKey' => 'examination_id',
            'through' => 'Examination.ExaminationCentreRoomsExaminations',
            'dependent' => true,
            'cascadeCallbacks' => true
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
                'rule' => ['validateUnique', ['scope' => 'examination_centre_id']],
                'provider' => 'table'
            ])
            ->add('size', 'ruleValidateNumeric',  [
                'rule' => ['numericPositive']
            ])
            ->add('size', 'ruleRoomSize',  [
                'rule'  => ['range', 0, 2147483647]
            ])
            ->add('number_of_seats', 'ruleValidateNumeric',  [
                'rule' => ['numericPositive']
            ])
            ->add('number_of_seats', 'ruleSeatsNumber',  [
                'rule'  => ['range', 0, 2147483647]
            ])
            ->add('number_of_seats', 'ruleCheckRoomCapacityMoreThanStudents', [
                'rule' => 'checkRoomCapacityMoreThanStudents',
                'on' => 'update'
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
        $this->field('examination_centre_id', ['visible' => false]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('examination_centre_id') => $this->examCentreId]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('size');
        $this->field('number_of_seats');
        $examinationCentre = $this->ExaminationCentres->get($this->examCentreId, ['contain' => ['AcademicPeriods']]);
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $examinationCentre->academic_period_id, 'attr' => ['value' => $examinationCentre->academic_period->name]]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $examinationCentre->id, 'attr' => ['value' => $examinationCentre->code_name]]);
        $this->setFieldOrder(['academic_period_id', 'examination_centre_id', 'name', 'size', 'number_of_seats']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields['examination_centre_id']['visible'] = false;
        $this->setFieldOrder(['name', 'size', 'number_of_seats']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('size');
        $this->field('number_of_seats');
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $entity->examination_centre->academic_period_id, 'attr' => ['value' => $entity->examination_centre->academic_period->name]]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $entity->examination_centre_id, 'attr' => ['value' => $entity->examination_centre->code_name]]);
        $this->setFieldOrder(['academic_period_id', 'examination_centre_id', 'name', 'size', 'number_of_seats']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['ExaminationCentres.AcademicPeriods']);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $examCentreId = $entity->examination_centre_id;
        $listeners = [TableRegistry::get('Examination.ExaminationCentreRoomsExaminations')];
        $this->dispatchEventToModels('Model.ExaminationCentreRooms.afterSave', [$entity], $this, $listeners);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->Examinations->alias()
        ];

        $ExamRoomStudents = TableRegistry::get('Examination.ExaminationCentreRoomsExaminationsStudents');
        $associatedStudentCount = $ExamRoomStudents->find()
            ->where([$ExamRoomStudents->aliasField('examination_centre_room_id') => $entity->id])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'Students', 'count' => $associatedStudentCount];

        $ExamRoomInvigilators = TableRegistry::get('Examination.ExaminationCentreRoomsExaminationsInvigilators');
        $associatedInvigilatorsCount = $ExamRoomInvigilators->find()
            ->where([$ExamRoomInvigilators->aliasField('examination_centre_room_id') => $entity->id])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'Invigilators', 'count' => $associatedInvigilatorsCount];
    }
}

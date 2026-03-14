<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
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

    public function initialize(array $config): void
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

    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona)
    {
        $queryString = $this->request->getQuery('queryString');
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres'];
        $overviewUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'view', 'queryString' => $queryString];

        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
        $Navigation->substituteCrumb('Exam Centre Rooms', 'Exam Centres', $overviewUrl);
        $Navigation->addCrumb('Rooms');
    }

    /*public function validationDefault(Validator $validator): Validator
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
    }*/

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');
        if ($this->examCentreId === null) {
            $event->stopPropagation();
            $this->Alert->warning('general.notExists');
            $this->controller->redirect(['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'index']);

            return;
        }
        // Set the header of the page
        $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
        $this->controller->set('contentHeader', $examCentreName . ' - ' . __('Rooms'));

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Exam Centre Rooms','Examinations');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        if (is_null($this->examCentreId)) {
            $event->stopPropagation();
            $this->controller->redirect(['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'index']);
        }
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('examination_centre_id', ['visible' => false]);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('examination_centre_id') => $this->examCentreId]);
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('size');
        $this->field('number_of_seats');
        $examinationCentre = $this->ExaminationCentres->get($this->examCentreId);
        $academicPeriodName = $this->_getAcademicPeriodNameForCentre($this->examCentreId);
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => null, 'attr' => ['value' => $academicPeriodName]]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $examinationCentre->id, 'attr' => ['value' => $examinationCentre->code_name]]);
        $this->setFieldOrder(['academic_period_id', 'examination_centre_id', 'name', 'size', 'number_of_seats']);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields['examination_centre_id']['visible'] = false;
        $this->setFieldOrder(['name', 'size', 'number_of_seats']);
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('size');
        $this->field('number_of_seats');
        $academicPeriodName = $this->_getAcademicPeriodNameForRoomEntity($entity);
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => null, 'attr' => ['value' => $academicPeriodName]]);
        $this->field('examination_centre_id', ['type' => 'readonly', 'value' => $entity->examination_centre_id, 'attr' => ['value' => $entity->examination_centre->code_name]]);
        $this->setFieldOrder(['academic_period_id', 'examination_centre_id', 'name', 'size', 'number_of_seats']);
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['ExaminationCentres', 'Examinations' => ['AcademicPeriods']]);
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $examCentreId = $entity->examination_centre_id;
        $listeners = [TableRegistry::getTableLocator()->get('Examination.ExaminationCentreRoomsExaminations')];
        $this->dispatchEventToModels('Model.ExaminationCentreRooms.afterSave', [$entity], $this, $listeners);
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->Examinations->getAlias()
        ];

        $ExamRoomStudents = TableRegistry::getTableLocator()->get('Examination.ExaminationCentreRoomsExaminationsStudents');
        $associatedStudentCount = $ExamRoomStudents->find()
            ->where([$ExamRoomStudents->aliasField('examination_centre_room_id') => $entity->id])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'Students', 'count' => $associatedStudentCount];

        $ExamRoomInvigilators = TableRegistry::getTableLocator()->get('Examination.ExaminationCentreRoomsExaminationsInvigilators');
        $associatedInvigilatorsCount = $ExamRoomInvigilators->find()
            ->where([$ExamRoomInvigilators->aliasField('examination_centre_room_id') => $entity->id])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'Invigilators', 'count' => $associatedInvigilatorsCount];
    }

    /**
     * Get academic period name for an examination centre (from a linked examination).
     * ExaminationCentres has no AcademicPeriods association; period comes from Examinations.
     */
    protected function _getAcademicPeriodNameForCentre($examinationCentreId)
    {
        $ExaminationCentresExaminations = TableRegistry::getTableLocator()->get('Examination.ExaminationCentresExaminations');
        $link = $ExaminationCentresExaminations->find()
            ->where([$ExaminationCentresExaminations->aliasField('examination_centre_id') => $examinationCentreId])
            ->contain(['Examinations.AcademicPeriods'])
            ->first();
        return $link && $link->examination && $link->examination->academic_period
            ? $link->examination->academic_period->name
            : '';
    }

    /**
     * Get academic period name for a room entity (from its linked examinations or centre).
     */
    protected function _getAcademicPeriodNameForRoomEntity(Entity $entity)
    {
        if (!empty($entity->examinations) && isset($entity->examinations[0]->academic_period)) {
            return $entity->examinations[0]->academic_period->name;
        }
        return $this->_getAcademicPeriodNameForCentre($entity->examination_centre_id);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_id') {
            return __('Institution');
        } elseif ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'examination_centre_id') {
            return __('Examination Centre');
        } elseif ($field == 'size') {
            return __('Size');
        }elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'number_of_seats') {
            return __('Number Of Seats');
        } elseif ($field == 'institutions') {
            return __('Institutions');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}

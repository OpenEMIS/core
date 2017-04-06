<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Controller\Component;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsInvigilatorsTable extends ControllerActionTable
{
    private $queryString;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
		$this->belongsTo('Invigilators', ['className' => 'User.Users']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->hasMany('ExaminationCentreRoomsExaminationsInvigilators', [
            'className' => 'Examination.ExaminationCentreRoomsExaminationsInvigilators',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'invigilator_id'],
            'bindingKey' => ['examination_centre_id', 'examination_id', 'invigilator_id'],
            'dependent' => true,
            'cascadeCallBack' => true
        ]);

        $this->addBehavior('CompositeKey');
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $this->queryString = $request->query['queryString'];
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExaminationCentres'];
        $overviewUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'Centres', 'view', 'queryString' => $this->queryString];

        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
        $Navigation->substituteCrumb('Exam Centre Invigilators', 'Examination Centre', $overviewUrl);
        $Navigation->addCrumb('Invigilators');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
        $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Invigilators'));

        $this->fields['examination_id']['type'] = 'string';
        $this->fields['invigilator_id']['type'] = 'string';
        $this->field('rooms');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['invigilator_id', 'examination_id', 'rooms']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // set queryString for page refresh
        $this->controller->set('queryString', $this->queryString);

        // Examination filter
        $ExaminationCentresExaminations = $this->ExaminationCentresExaminations;
        $examinationOptions = $ExaminationCentresExaminations
            ->find('list', [
                'keyField' => 'examination_id',
                'valueField' => 'examination.code_name'
            ])
            ->contain('Examinations')
            ->where([$ExaminationCentresExaminations->aliasField('examination_centre_id') => $this->examCentreId])
            ->toArray();

        $examinationOptions = ['-1' => '-- '.__('Select Examination').' --'] + $examinationOptions;
        $recordExamId = $this->ControllerAction->getQueryString('examination_id');
        $selectedExamination = !is_null($this->request->query('examination_id')) ? $this->request->query('examination_id') : $recordExamId;
        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
            $where[$this->aliasField('examination_id')] = $selectedExamination;
        }

        $where[$this->aliasField('examination_centre_id')] = $this->examCentreId;
        $query
            ->contain('ExaminationCentreRoomsExaminationsInvigilators.ExaminationCentreRooms')
            ->where([$where]);

        $extra['elements']['controls'] = ['name' => 'Examination.ExaminationCentres/controls', 'data' => [], 'options' => [], 'order' => 1];
        $extra['auto_contain_fields'] = ['ExaminationItems' => ['code']];
    }

    public function onGetRooms(Event $event, Entity $entity)
    {
        if ($entity->has('examination_centre_rooms_examinations_invigilators')) {
            $roomInvigilators = $entity->examination_centre_rooms_examinations_invigilators;

            $rooms = [];
            foreach ($roomInvigilators as $room) {
                $rooms[] = $room->examination_centre_room->name;
            }

            return implode($rooms, ", ");
        }
    }
}

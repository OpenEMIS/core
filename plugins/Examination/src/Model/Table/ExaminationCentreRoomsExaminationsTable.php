<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class ExaminationCentreRoomsExaminationsTable extends ControllerActionTable
{
    private $queryString;
    private $examCentreId = null;

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentreRooms', ['className' => 'Examination.ExaminationCentreRooms']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->hasMany('ExaminationCentreRoomsExaminationsInvigilators', ['className' => 'Examination.ExaminationCentreRoomsExaminationsInvigilators', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', ['className' => 'Examination.ExaminationCentreRoomsExaminationsStudents', 'dependent' => true, 'cascadeCallbacks' => true]);

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
        $Navigation->substituteCrumb('Exam Centre Rooms', 'Examination Centre', $overviewUrl);
        $Navigation->addCrumb('Rooms');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
        $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Rooms'));

        $this->fields['examination_centre_id']['visible'] = false;
        $this->fields['examination_centre_room_id']['type'] = 'string';
        $this->field('examination_id', ['type' => 'select']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // set queryString for page refresh
        $this->controller->set('queryString', $this->queryString);

        // Examination filter
        $ExaminationCentresExaminations = TableRegistry::get('Examination.ExaminationCentresExaminations');
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
        $query->where([$where]);

        $extra['elements']['controls'] = ['name' => 'Examination.ExaminationCentres/controls', 'data' => [], 'options' => [], 'order' => 1];
        $extra['auto_contain_fields'] = ['ExaminationItems' => ['code']];
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        $this->field('examination_centre_rooms');
        $this->fields['examination_centre_room_id']['visible'] = false;
        $this->setFieldOrder(['academic_period_id', 'examination_id', 'examination_centre_rooms']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $examCentre = $this->ExaminationCentres->get($this->examCentreId, ['contain' => ['AcademicPeriods']]);
        $academicPeriodId = $examCentre->academic_period->name;

        $attr['type'] = 'readonly';
        $attr['value'] =
        $attr['attr']['value'] = $academicPeriodId;
        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $ExaminationCentresExaminations = TableRegistry::get('Examination.ExaminationCentresExaminations');
            $examinationOptions = $ExaminationCentresExaminations
                ->find('list', [
                    'keyField' => 'examination_id',
                    'valueField' => 'examination.code_name'
                ])
                ->contain('Examinations')
                ->where([$ExaminationCentresExaminations->aliasField('examination_centre_id') => $this->examCentreId])
                ->toArray();

            $attr['options'] = $examinationOptions;
            $attr['onChangeReload'] = true;
            $attr['type'] = 'select';
        }
        return $attr;
    }

    public function onUpdateFieldExaminationCentreRooms(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $roomOptions = $this->ExaminationCentreRooms->find('list')
                ->where([$this->ExaminationCentreRooms->aliasField('examination_centre_id') => $this->examCentreId]);

            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $roomOptions->notMatching('Examinations', function ($q) use ($examinationId) {
                    return $q->where(['Examinations.id' => $examinationId]);
                });
            }

            $attr['options'] = $roomOptions->toArray();
            $attr['type'] = 'chosenSelect';
        }
        return $attr;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData['examination_centre_id'] = $this->examCentreId;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (isset($requestData[$model->alias()]['examination_id']) && !empty($requestData[$model->alias()]['examination_centre_rooms'])) {
                $roomIds = $requestData[$model->alias()]['examination_centre_rooms']['_ids'];
                $newEntities = [];
                foreach ($roomIds as $room) {
                    $requestData[$model->alias()]['examination_centre_room_id'] = $room;
                    $newEntities[] = $model->newEntity($requestData->getArrayCopy());
                }

                return $model->saveMany($newEntities);
            }
        };

        return $process;
    }
}

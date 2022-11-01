<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Controller\Component;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsInvigilatorsTable extends ControllerActionTable
{
    use HtmlTrait;

    private $queryString;
    private $examCentreId;

	public function initialize(array $config)
    {
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
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('CompositeKey');
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxInvigilatorAutocomplete'] = 'ajaxInvigilatorAutocomplete';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $this->queryString = $request->query['queryString'];
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres'];
        $overviewUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'view', 'queryString' => $this->queryString];

        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
        $Navigation->substituteCrumb('Exam Centre Invigilators', 'Exam Centres', $overviewUrl);
        $Navigation->addCrumb('Invigilators');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
        $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Invigilators'));

        $this->fields['examination_id']['type'] = 'integer';
        $this->fields['invigilator_id']['type'] = 'integer';
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
        $this->fields['invigilator_id']['sort'] = ['field' => 'Invigilators.first_name'];
        $this->field('openemis_no', ['sort' => ['field' => 'Invigilators.openemis_no']]);
        $this->field('rooms');
        $this->setFieldOrder(['openemis_no', 'invigilator_id', 'examination_id', 'rooms']);
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
        $roomOptions = ['0' => __('All Rooms'), '-1' => __('Invigilators without Room')] + $roomOptions;
        $selectedRoom = !is_null($this->request->query('examination_centre_room_id')) ? $this->request->query('examination_centre_room_id') : 0;
        $this->controller->set(compact('roomOptions', 'selectedRoom'));

        if ($selectedRoom > 0) {
            $query->matching('ExaminationCentreRoomsExaminationsInvigilators');
            $where['ExaminationCentreRoomsExaminationsInvigilators.examination_centre_room_id'] = $selectedRoom;

        } else if ($selectedRoom == -1) {
            $query->leftJoinWith('ExaminationCentreRoomsExaminationsInvigilators');
            $where[] = 'ExaminationCentreRoomsExaminationsInvigilators.examination_centre_room_id IS NULL';
        }

        $where[$this->aliasField('examination_centre_id')] = $this->examCentreId;
        $query
            ->contain('Invigilators')
            ->contain('ExaminationCentreRoomsExaminationsInvigilators.ExaminationCentreRooms')
            ->where([$where]);

        $extra['elements']['controls'] = ['name' => 'Examination.ExaminationCentres/controls', 'data' => [], 'options' => [], 'order' => 1];

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => 'Invigilators', 'searchTerm' => $search]);
            $extra['OR'] = $nameConditions; // to be merged with auto_search 'OR' conditions
        }

        // sort
        $sortList = ['Invigilators.openemis_no', 'Invigilators.first_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'invigilator_id';
        $searchableFields['Invigilators'] = 'openemis_no';
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no');
        $this->field('rooms');
        $this->setFieldOrder(['openemis_no', 'invigilator_id', 'examination_id', 'rooms']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Invigilators', 'Examinations', 'ExaminationCentreRoomsExaminationsInvigilators.ExaminationCentreRooms']);
    }

    public function onGetRooms(Event $event, Entity $entity)
    {
        if ($entity->has('examination_centre_rooms_examinations_invigilators')) {
            $roomInvigilators = $entity->examination_centre_rooms_examinations_invigilators;

            $rooms = [];
            foreach ($roomInvigilators as $obj) {
                $rooms[] = $obj->examination_centre_room->name;
            }

            return implode($rooms, ", ");
        }
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('invigilator')) {
            $value = $entity->invigilator->openemis_no;
        }

        return $value;
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['invigilator_id']['visible'] = false;
        $this->field('academic_period_id', ['type' => 'readonly']);
        $this->field('examination_centre_id', ['type' => 'readonly']);
        $this->field('examination_id', ['type' => 'select']);
        $this->field('rooms', ['type' => 'select']);
        $this->field('invigilators', ['type' => 'custom_invigilators']);
        $this->setFieldOrder(['academic_period_id', 'examination_centre_id', 'examination_id', 'rooms', 'invigilators']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'readonly']);
        $this->field('examination_centre_id', ['type' => 'readonly']);
        $this->field('invigilator_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('examination_id', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('rooms', ['type' => 'chosenSelect', 'entity' => $entity]);
        $this->setFieldOrder(['academic_period_id', 'examination_centre_id', 'invigilator_id', 'examination_id', 'rooms']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $examCentreEntity = $this->ExaminationCentres->get($this->examCentreId, ['contain' => ['AcademicPeriods']]);
        $academicPeriod = $examCentreEntity->academic_period->name;
        $attr['attr']['value'] = $academicPeriod;
        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, Request $request)
    {
        $examCentreEntity = $this->ExaminationCentres->get($this->examCentreId);
        $attr['value'] = $this->examCentreId;
        $attr['attr']['value'] = $examCentreEntity->code_name;
        return $attr;
    }

    public function onUpdateFieldInvigilatorId(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        $attr['value'] = $entity->invigilator_id;
        $attr['attr']['value'] = $entity->invigilator->name_with_id;
        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $linkedExams = $this->ExaminationCentresExaminations->find('list', [
                    'keyField' => 'examination.id',
                    'valueField' => 'examination.code_name'
                ])
                ->contain('Examinations')
                ->where([$this->ExaminationCentresExaminations->aliasField('examination_centre_id') => $this->examCentreId])
                ->toArray();

            $attr['options'] = $linkedExams;
            $attr['onChangeReload'] = 'changeExaminationId';

        } else if ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['value'] = $entity->examination_id;
            $attr['attr']['value'] = $entity->examination->code_name;
        }

        return $attr;
    }

    public function addOnChangeExaminationId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('invigilators', $data[$this->alias()])) {
                unset($data[$this->alias()]['invigilators']);
            }
        }
    }

    public function onUpdateFieldRooms(Event $event, array $attr, $action, Request $request)
    {
        $ExaminationCentreRooms = $this->ExaminationCentres->ExaminationCentreRooms;
        $roomOptions = $ExaminationCentreRooms->find('list')
            ->where([$ExaminationCentreRooms->aliasField('examination_centre_id') => $this->examCentreId])
            ->toArray();

        if ($action == 'edit') {
            if ($attr['entity']->has('examination_centre_rooms_examinations_invigilators')) {
                $roomInvigilators = $attr['entity']->examination_centre_rooms_examinations_invigilators;

                $obj = [];
                foreach ($roomInvigilators as $room) {
                    $obj[] = $room->examination_centre_room_id;
                }
                $request->data[$this->alias()]['rooms'] = $obj;
            }

            $attr['fieldName'] = $this->alias().'.rooms';
        }

        $attr['options'] = $roomOptions;
        return $attr;
    }

    public function onGetCustomInvigilatorsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $tableHeaders = [__('OpenEMIS ID'), __('Invigilator')];
        $tableCells = [];
        $alias = $this->alias();
        $fieldKey = 'invigilators';
        $examinationId = isset($this->request->data[$this->alias()]['examination_id']) ? $this->request->data[$this->alias()]['examination_id'] : -1;

        if ($action == 'edit') {
            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $Form->unlockField('ExaminationCentres.invigilators');

            // refer to addOnAddInvigilator for http post
            if ($this->request->data("$alias.$fieldKey")) {
                $associated = $this->request->data("$alias.$fieldKey");

                foreach ($associated as $key => $obj) {
                    $invigilatorId = $obj['id'];
                    $openemisId = $obj['openemis_no'];
                    $name = $obj['name'];

                    $rowData = [];

                    $cell = $name;
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.id", ['value' => $invigilatorId, 'autocomplete-exclude' => $invigilatorId]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.openemis_no", ['value' => $openemisId]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.name", ['value' => $name]);

                    $Form->unlockField("$alias.$fieldKey.$key.id");
                    $Form->unlockField("$alias.$fieldKey.$key.openemis_no");
                    $Form->unlockField("$alias.$fieldKey.$key.name");

                    $rowData[] = $openemisId;
                    $rowData[] = $cell;
                    $rowData[] = $this->getDeleteButton();
                    $tableCells[] = $rowData;
                }
            }
        }

        $attr['examination_id'] = $examinationId;
        $attr['queryString'] = $this->queryString;
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Examination.ExaminationCentres/' . $fieldKey, ['attr' => $attr]);
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

    public function ajaxInvigilatorAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];
            $search = sprintf('%s%%', $term);
            $data = [];
            $examinationId = $this->paramsDecode($this->paramsPass(0))['examination_id'];
            $Users = $this->Invigilators;
            $query = $Users
                ->find()
                ->leftJoin(['ExaminationCentresExaminationsInvigilators' => 'examination_centres_examinations_invigilators'], [
                    'ExaminationCentresExaminationsInvigilators.invigilator_id = '.$Users->aliasField('id'),
                    'ExaminationCentresExaminationsInvigilators.examination_id' => $examinationId
                ])
                ->select([
                    $Users->aliasField('id'),
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name')
                ])
                ->where([
                    'ExaminationCentresExaminationsInvigilators.invigilator_id IS NULL',
                    $Users->aliasField('is_student') => 0,
                ])
                ->order([
                    $Users->aliasField('first_name'),
                    $Users->aliasField('last_name')
                ]);

            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Invigilators', 'searchTerm' => $search]);
            $list = $query->toArray();

            foreach($list as $obj) {
                $data[] = [
                    'label' => sprintf('%s - %s', $obj->openemis_no, $obj->name),
                    'value' => $obj->id
                ];
            }

            echo json_encode($data);
            die;
        }
    }

    public function addOnAddInvigilator(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'invigilators';

        if (empty($data[$alias][$fieldKey])) {
            $data[$alias][$fieldKey] = [];
        }

        if ($data->offsetExists($alias)) {
            if (array_key_exists('invigilator_id', $data[$alias]) && !empty($data[$alias]['invigilator_id'])) {
                $id = $data[$alias]['invigilator_id'];

                try {
                    $obj = $this->Invigilators->get($id);

                    $data[$alias][$fieldKey][] = [
                        'id' => $obj->id,
                        'openemis_no' => $obj->openemis_no,
                        'name' => $obj->name,
                    ];

                    $data[$alias]['invigilator_id'] = '';
                } catch (RecordNotFoundException $ex) {
                    Log::write('debug', __METHOD__ . ': Record not found for id: ' . $id);
                }
            }
        }
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData[$this->alias()]['invigilator_id'] = 0;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (!empty($entity->errors())) {
                return false;
            }

            if (isset($requestData[$model->alias()]['invigilators']) && !empty($requestData[$model->alias()]['invigilators'])) {
                $invigilators = $requestData[$model->alias()]['invigilators'];
                $newEntities = [];

                if (is_array($invigilators)) {
                    foreach ($invigilators as $invigilator) {
                        $requestData['invigilator_id'] = $invigilator['id'];

                        if (!empty($requestData[$model->alias()]['rooms'])) {
                            $requestData[$model->alias()]['examination_centre_rooms_examinations_invigilators'][] = [
                                'examination_centre_room_id' => $requestData[$model->alias()]['rooms'],
                                'examination_id' => $requestData[$model->alias()]['examination_id'],
                                'invigilator_id' => $invigilator['id'],
                                'examination_centre_id' => $requestData[$model->alias()]['examination_centre_id']
                            ];
                        }

                        $newEntities[] = $model->newEntity($requestData->getArrayCopy());
                    }

                    if (empty($newEntities)) {
                        $model->Alert->warning($this->aliasField('noInvigilatorsSelected'));
                        $entity->errors('invigilator_search', __('There are no invigilators selected'));
                        return false;
                    }

                    return $model->saveMany($newEntities);
                }
            } else {
                $model->Alert->warning($this->aliasField('noInvigilatorsSelected'));
                $entity->errors('invigilator_search', __('There are no invigilators selected'));
                return false;
            }
        };

        return $process;
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        if (isset($data[$this->alias()]['rooms']) && !empty($data[$this->alias()]['rooms'])) {
            $roomInvigilators = [];

            foreach ($data[$this->alias()]['rooms'] as $key => $value) {
                $roomInvigilators[] = [
                    'examination_centre_id' => $data[$this->alias()]['examination_centre_id'],
                    'invigilator_id' => $data[$this->alias()]['invigilator_id'],
                    'examination_id' => $data[$this->alias()]['examination_id'],
                    'examination_centre_room_id' => $value
                ];
            }

            $data[$this->alias()]['examination_centre_rooms_examinations_invigilators'] = $roomInvigilators;
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // manually delete hasMany roomInvigilators data
        $fieldKey = 'examination_centre_rooms_examinations_invigilators';
        if (!array_key_exists($fieldKey, $data[$this->alias()])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        $currentRoomIds = array_column($data[$this->alias()][$fieldKey], 'examination_centre_room_id');
        $originalRooms = $entity->extractOriginal([$fieldKey])[$fieldKey];

        foreach ($originalRooms as $key => $room) {
            if (!in_array($room['examination_centre_room_id'], $currentRoomIds)) {
                $this->ExaminationCentreRoomsExaminationsInvigilators->delete($room);
                unset($entity->examination_centre_rooms_examinations_invigilators[$key]);
            }
        }
    }

}

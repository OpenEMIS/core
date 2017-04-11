<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Log\Log;

class ExaminationCentresTable extends ControllerActionTable {
    use OptionsTrait;
    use HtmlTrait;

    private $examCentreName = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->hasMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentreRooms', ['className' => 'Examination.ExaminationCentreRooms', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('Examinations', [
            'className' => 'Examination.Examinations',
            'joinTable' => 'examination_centres_examinations',
            'foreignKey' => 'examination_centre_id',
            'targetForeignKey' => 'examination_id',
            'through' => 'Examination.ExaminationCentresExaminations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportExaminationCentreRooms']);

        $this->setDeleteStrategy('restrict');
        $this->toggle('index', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('create_as', 'create')
            ->requirePresence('academic_period_id')
            ->requirePresence('code')
            ->requirePresence('name')
            ->add('code', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                'provider' => 'table'
            ])
            ->requirePresence('institutions', 'create')
            ->allowEmpty('postal_code')
            ->allowEmpty('examination_centre_rooms')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ;

        return $validator;
    }

    public function validationNoInstitutions(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->requirePresence('institutions', false);
        return $validator;
    }

    public function validationInstitutions(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->requirePresence('code', false)
            ->remove('code')
            ->requirePresence('name', false);
        return $validator;
    }

    public function validationAllInstitutions(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->requirePresence('institutions', false)
            ->remove('institutions')
            ->requirePresence('code', false)
            ->remove('code')
            ->requirePresence('name', false);
        return $validator;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $params = ['examination_centre_id' => $entity->id];
        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = $this->ControllerAction->setQueryString($buttons['view']['url'], $params);
        }

        if (isset($buttons['edit']['url'])) {
            $buttons['edit']['url'] = $this->ControllerAction->setQueryString($buttons['edit']['url'], $params);
        }

        return $buttons;
    }

    public function findBySpecialNeeds(Query $query, array $options)
    {
        $selectedSpecialNeeds = $options['selectedSpecialNeeds'];
        $query
            ->select(['count' => $this->find()->func()->count('*')])
            ->matching('ExaminationCentreSpecialNeeds')
            ->where([
                $this->ExaminationCentreSpecialNeeds->aliasField('special_need_type_id IN') => $selectedSpecialNeeds
            ])
            ->group($this->aliasField('id'))
            ->having(['count =' => count($selectedSpecialNeeds)])
            ->autoFields(true);
        return $query;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'view' || $this->action == 'edit') {
            $examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');
            $this->request->params['pass'][1] = $this->paramsEncode(['id' => $examCentreId]);
            $extra['config']['selectedLink'] = ['controller' => 'Examinations', 'action' => 'ExamCentres', 'index'];
            $this->examCentreName = $this->get($examCentreId)->name;
        }
        $this->field('institution_id', ['visible' => false]);
        $this->field('name');
        $this->fields['area_id']['visible'] = false;
        $this->fields['code']['visible'] = false;
        $this->fields['address']['visible'] = false;
        $this->fields['postal_code']['visible'] = false;
        $this->fields['contact_person']['visible'] = false;
        $this->fields['telephone']['visible'] = false;
        $this->fields['fax']['visible'] = false;
        $this->fields['email']['visible'] = false;
        $this->fields['website']['visible'] = false;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['ExaminationCentreSpecialNeeds.SpecialNeedTypes'])
            ->contain(['ExaminationCentreRooms.Examinations'])
            ->contain('Examinations')
            ->matching('Areas')
            ->matching('AcademicPeriods');
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', $this->examCentreName. ' - ' .__('Overview'));
        $this->controller->getExamCentresTab();
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', $this->examCentreName. ' - ' .__('Overview'));
        $this->controller->getExamCentresTab();
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $options['associated'][] = 'ExaminationCentreSpecialNeeds';
        $options['associated'][] = 'ExaminationCentreRooms';
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // manually delete hasMany ExamCentreSpecialNeeds data
        $specialNeedsFieldKey = 'examination_centre_special_needs';
        if (!array_key_exists($specialNeedsFieldKey, $data[$this->alias()])) {
            $data[$this->alias()][$specialNeedsFieldKey] = [];
        }

        $specialNeedIds = array_column($data[$this->alias()][$specialNeedsFieldKey], 'special_need_type_id');
        $originalSpecialNeeds = $entity->extractOriginal([$specialNeedsFieldKey])[$specialNeedsFieldKey];
        foreach ($originalSpecialNeeds as $key => $need) {
            if (!in_array($need['special_need_type_id'], $specialNeedIds)) {
                $this->ExaminationCentreSpecialNeeds->delete($need);
                unset($entity->examination_centre_special_needs[$key]);
            }
        }

        // manually delete hasMany ExamCentreRooms data
        $roomsFieldKey = 'examination_centre_rooms';
        if (!array_key_exists($roomsFieldKey, $data[$this->alias()])) {
            $data[$this->alias()][$roomsFieldKey] = [];
        }

        $examCentreRoomIds = array_column($data[$this->alias()][$roomsFieldKey], 'id');
        $originalRooms = $entity->extractOriginal([$roomsFieldKey])[$roomsFieldKey];
        foreach ($originalRooms as $key => $room) {
            if (!in_array($room['id'], $examCentreRoomIds)) {
                $this->ExaminationCentreRooms->delete($room);
                unset($entity->examination_centre_rooms[$key]);
            }
        }
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $entity = $extra['entity'];
        $this->field('exam_centre_info_section', ['type' => 'section', 'title' => __('Examination Centre Information'), 'visible' => false]);
        $this->field('exam_centre_room_info_section', ['type' => 'section', 'title' => __('Room Information'), 'visible' => false]);
        $this->field('special_needs_section', ['type' => 'section', 'title' => __('Special Need Accommodations'), 'visible' => false]);

        if ($this->action == 'edit' || $this->action == 'add') {
            $this->field('academic_period_id', ['entity' => $entity]);
            $this->field('special_need_type_id', ['type' => 'custom_exam_centre_special_needs']);
            $this->field('create_as', ['type' => 'select', 'options' => $this->getSelectOptions($this->aliasField('create_as')), 'entity' => $entity]);
            $this->fields['institution_id']['visible'] = true;
            $this->fields['institution_id']['type'] = 'hidden';

            if ($this->action == 'add') {
                if ($entity->create_as == 'new') {
                    $this->fields['area_id'] = array_merge($this->fields['area_id'], ['visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
                    $this->fields['name']['visible'] = true;
                    $this->fields['code']['visible'] = true;
                    $this->fields['address']['visible'] = true;
                    $this->fields['postal_code']['visible'] = true;
                    $this->fields['contact_person']['visible'] = true;
                    $this->fields['telephone']['visible'] = true;
                    $this->fields['fax']['visible'] = true;
                    $this->fields['email']['visible'] = true;
                    $this->fields['website']['visible'] = true;
                } else if ($entity->create_as == 'existing') {
                    $this->field('institution_type');
                    $this->field('add_all_institutions');
                    $this->field('institutions');
                    $this->fields['name']['visible'] = false;
                } else {
                    $this->fields['name']['visible'] = false;
                }
                $this->fields['special_needs_section']['visible'] = true;

                // field order
                $this->setFieldOrder(['exam_centre_info_section', 'create_as', 'academic_period_id', 'institution_type', 'add_all_institutions', 'institutions', 'code', 'name', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website', 'special_needs_section', 'special_need_type_id']);

            } else if ($this->action == 'edit') {
                $this->field('area_id', ['entity' => $entity, 'visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
                $this->field('examination_centre_rooms', ['type' => 'element', 'element' => 'Examination.exam_centre_rooms']);
                $this->fields['name']['visible'] = true;
                $this->fields['code']['visible'] = true;
                $this->fields['address']['visible'] = true;
                $this->fields['postal_code']['visible'] = true;
                $this->fields['contact_person']['visible'] = true;
                $this->fields['telephone']['visible'] = true;
                $this->fields['fax']['visible'] = true;
                $this->fields['email']['visible'] = true;
                $this->fields['website']['visible'] = true;

                if ($entity->institution_id != 0) {
                    $this->fields['name']['type'] = 'readonly';
                    $this->fields['code']['type'] = 'readonly';
                    $this->fields['address']['type'] = 'text';
                    $this->fields['address']['attr']['disabled'] = 'disabled';
                    $this->fields['postal_code']['type'] = 'readonly';
                    $this->fields['contact_person']['type'] = 'readonly';
                    $this->fields['telephone']['type'] = 'readonly';
                    $this->fields['fax']['type'] = 'readonly';
                    $this->fields['email']['type'] = 'readonly';
                    $this->fields['website']['type'] = 'readonly';
                }

                $this->fields['special_needs_section']['visible'] = true;
                $this->fields['exam_centre_info_section']['visible'] = true;
                $this->fields['exam_centre_room_info_section']['visible'] = true;

                // field order
                $this->setFieldOrder(['exam_centre_info_section', 'create_as', 'academic_period_id', 'code', 'name', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website', 'exam_centre_room_info_section', 'examination_centre_rooms', 'special_needs_section', 'special_need_type_id']);
            }

        } else if ($this->action == 'view') {
            $this->fields['area_id'] = array_merge($this->fields['area_id'], ['visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
            $this->field('special_need_type_id', ['type' => 'custom_exam_centre_special_needs']);
            $this->field('examination_centre_rooms', ['type' => 'element', 'element' => 'Examination.exam_centre_rooms']);

            $this->fields['code']['visible'] = true;
            $this->fields['address']['visible'] = true;
            $this->fields['postal_code']['visible'] = true;
            $this->fields['contact_person']['visible'] = true;
            $this->fields['telephone']['visible'] = true;
            $this->fields['fax']['visible'] = true;
            $this->fields['email']['visible'] = true;
            $this->fields['website']['visible'] = true;

            $this->setFieldOrder(['exam_centre_info_section', 'code', 'name', 'academic_period_id', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website', 'examination_centre_rooms', 'special_need_type_id']);
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['onChangeReload'] = true;
        } else if ($action == 'edit') {
            if (isset($attr['entity'])) {
                $attr['attr']['value'] = $attr['entity']->_matchingData['AcademicPeriods']->name;
            }
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function addEditOnSelectSpecialNeedType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $ADD_ALL = -1;
        $fieldKey = 'examination_centre_special_needs';

        $SpecialNeedTypesTable = $this->ExaminationCentreSpecialNeeds->SpecialNeedTypes;
        $allSpecialNeeds = $SpecialNeedTypesTable->getVisibleNeedTypes();
        $id = $data[$this->alias()]['special_need_type_id'];

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($id == $ADD_ALL) {
            $selectedNeeds = array_column($data[$this->alias()][$fieldKey], 'special_need_type_id');
            foreach ($allSpecialNeeds as $key => $obj) {
                if (!empty($selectedNeeds) && in_array($key, $selectedNeeds)) {
                    continue;
                }

                $data[$this->alias()][$fieldKey][] = [
                    'special_need_type_id' => $key,
                    'name' => $obj
                ];
            }
            $data[$this->alias()]['special_need_type_id'] = '';

        } else if ($id > 0) {
            try {
                $obj = $SpecialNeedTypesTable->get($id);

                $data[$this->alias()][$fieldKey][] = [
                    'special_need_type_id' => $id,
                    'name' => $obj->name
                ];

                $data[$this->alias()]['special_need_type_id'] = '';

            } catch (RecordNotFoundException $ex) {
                Log::write('debug', __METHOD__ . ': Record not found for special need type id: ' . $id);
            }
        }
    }

    public function editOnAddExamCentreRoom(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $fieldKey = 'examination_centre_rooms';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($data->offsetExists($this->alias())) {
            $data[$this->alias()][$fieldKey][] = [
                'name' => '',
                'size' => '',
                'number_of_seats' => ''
            ];
        }

        $options['associated'] = [
            'ExaminationCentreRooms' => ['validate' => false]
        ];
    }

    public function onGetCustomExamCentreSpecialNeedsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $requestData = $this->request->data;
        $tableHeaders = [__('Special Need Type')];
        $tableCells = [];
        $alias = $this->alias();
        $fieldKey = 'examination_centre_special_needs';

        if ($action == 'view') {
            $associated = $entity->extractOriginal([$fieldKey]);
            if (!empty($associated[$fieldKey])) {
                foreach ($associated[$fieldKey] as $key => $obj) {
                    $rowData = [];
                    $rowData[] = $obj->special_need_type->name;
                    $tableCells[] = $rowData;
                }
            }

        } else if ($action == 'edit') {
            // options for special needs types
            $SpecialNeedTypesTable = $this->ExaminationCentreSpecialNeeds->SpecialNeedTypes;
            $specialNeedsOptions = $SpecialNeedTypesTable->getVisibleNeedTypes();

            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $Form->unlockField('ExaminationCentres.examination_centre_special_needs');

            $selectedSpecialNeeds = [];
            if ($this->request->is(['get'])) {
                $associated = $entity->extractOriginal([$fieldKey]);
                if (!empty($associated[$fieldKey])) {
                    foreach ($associated[$fieldKey] as $key => $obj) {
                        $selectedSpecialNeeds[] = [
                            'special_need_type_id' => $obj->special_need_type_id,
                            'name' => $obj->special_need_type->name
                        ];
                    }
                }

            } else if ($this->request->is(['post', 'put'])) {
                if (array_key_exists($fieldKey, $requestData[$alias])) {
                    foreach ($requestData[$alias][$fieldKey] as $key => $obj) {
                        $selectedSpecialNeeds[] = $obj;
                    }
                }
            }

            if (!empty($selectedSpecialNeeds)) {
                foreach ($selectedSpecialNeeds as $key => $obj) {
                    $specialNeedTypeId = $obj['special_need_type_id'];
                    $name = $obj['name'];

                    $cell = $name;
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.special_need_type_id", ['value' => $specialNeedTypeId]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.name", ['value' => $name]);

                    // reload special needs options when an item is deleted
                    $attrDelete = ['onclick' => "jsTable.doRemove(this);$('#reload').val('delete').click();"];

                    $rowData = [];
                    $rowData[] = $cell;
                    $rowData[] = $this->getDeleteButton($attrDelete);
                    $tableCells[] = $rowData;

                    // remove selected option from dropdown list
                    unset($specialNeedsOptions[$obj['special_need_type_id']]);
                }
            }

            $attr['options'] = $specialNeedsOptions;
            $attr['addAll'] = true;
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        return $event->subject()->renderElement('../ControllerAction/table_with_dropdown', ['attr' => $attr]);
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return $entity->code_name;
    }

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        if ($entity->institution_id != 0) {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = __($entity->_matchingData['Areas']->name);
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->Institutions->Types
                ->find('list')
                ->find('visible')
                ->toArray();

            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldAddAllInstitutions(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = isset($request->data[$this->alias()]['academic_period_id']) ? $request->data[$this->alias()]['academic_period_id'] : 0;
            $institutionOptions = $this->Institutions->find('NotExamCentres', ['academic_period_id' => $academicPeriodId]);

            if (!empty($request->data[$this->alias()]['institution_type'])) {
                $type = $request->data[$this->alias()]['institution_type'];
                $institutionOptions->where([$this->Institutions->aliasField('institution_type_id') => $type]);
            }

            $institutionsCount = $institutionOptions->count();

            $selectOptions = [];
            if ($institutionsCount != 0) {
                $yesOption = __('Yes') . ' - ' . $institutionsCount . ' ' . __('institutions selected');
                $selectOptions = [0 => __('No'), 1 => $yesOption];
            }

            $attr['type'] = 'select';
            $attr['options'] = $selectOptions;
            $attr['select'] = false;
            $attr['default'] = 0; // default selected is no
            $attr['onChangeReload'] = 'changeAddAllInstitutions';
            return $attr;
        }
    }

    public function addOnChangeAddAllInstitutions(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('institutions', $data[$this->alias()])) {
                $data[$this->alias()]['institutions'] = '';
            }
        }
    }

    public function onUpdateFieldInstitutions(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $addAllInstitutions = isset($request->data[$this->alias()]['add_all_institutions']) ? $request->data[$this->alias()]['add_all_institutions'] : 0;

            if ($addAllInstitutions == 1) {
                $attr['type'] = 'hidden';

            } else {
                $academicPeriodId = isset($request->data[$this->alias()]['academic_period_id']) ? $request->data[$this->alias()]['academic_period_id'] : 0;
                $institutionOptions = $this->Institutions
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->find('NotExamCentres', ['academic_period_id' => $academicPeriodId]);

                // if no institution type is selected, all institutions will be shown
                if (!empty($request->data[$this->alias()]['institution_type'])) {
                    $type = $request->data[$this->alias()]['institution_type'];
                    $institutionOptions->where([$this->Institutions->aliasField('institution_type_id') => $type]);
                }

                $attr['type'] = 'chosenSelect';
                $attr['options'] = $institutionOptions->toArray();
                $attr['fieldName'] = $this->alias().'.institutions';
            }

            return $attr;
        }
    }

    public function onUpdateFieldCreateAs(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['onChangeReload'] = true;
        } else if ($action == 'edit') {
            if ($attr['entity']->institution_id != 0) {
                $attr['attr']['value'] = $attr['options']['existing'];
            } else {
                $attr['attr']['value'] = $attr['options']['new'];
            }
            $attr['type'] = 'disabled';
        }
        return $attr;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $extra['redirect']['action'] = 'ExamCentres';

        $requestData[$this->alias()]['institution_id'] = 0;
        if (!isset($requestData[$this->alias()]['area_id'])) {
            $requestData[$this->alias()]['area_id'] = 0;
        }

        if ($requestData[$this->alias()]['create_as'] == 'new') {
            $patchOptions['validate'] = 'noInstitutions';
        } else if ($requestData[$this->alias()]['create_as'] == 'existing' && $requestData[$this->alias()]['add_all_institutions'] == 1) {
            $patchOptions['validate'] = 'allInstitutions';
        } else {
            $patchOptions['validate'] = 'institutions';
        }
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {

            if (isset($requestData[$model->alias()]['institutions']) && !empty($requestData[$model->alias()]['institutions'])) {
                $institutions = $requestData[$model->alias()]['institutions'];
                $newEntities = [];
                if (is_array($institutions)) {
                    foreach ($institutions as $institution) {
                        $institutionRecord = $model->Institutions->get($institution);
                        $requestData['institution_id'] = $institution;
                        $requestData['area_id'] = $institutionRecord->area_id;
                        $requestData['name'] = $institutionRecord->name;
                        $requestData['code'] = $institutionRecord->code;
                        $requestData['address'] = $institutionRecord->address;
                        $requestData['postal_code'] = $institutionRecord->postal_code;
                        $requestData['contact_person'] = $institutionRecord->contact_person;
                        $requestData['telephone'] = $institutionRecord->telephone;
                        $requestData['fax'] = $institutionRecord->fax;
                        $requestData['email'] = $institutionRecord->email;
                        $requestData['website'] = $institutionRecord->website;

                        $newEntities[] = $model->newEntity($requestData->getArrayCopy());
                    }
                }
                return $model->saveMany($newEntities);

            } else if (isset($requestData[$model->alias()]['add_all_institutions']) && $requestData[$model->alias()]['add_all_institutions'] == 1) {
                if (empty($entity->errors())) {
                    if (!empty($requestData[$this->alias()]['academic_period_id'])) {
                        $academicPeriodId = $requestData[$model->alias()]['academic_period_id'];
                        $institutionTypeId = !empty($requestData[$model->alias()]['institution_type']) ? $requestData[$model->alias()]['institution_type'] : '';

                        $specialNeedIds = [];
                        if (isset($requestData[$model->alias()]['examination_centre_special_needs'])) {
                            $specialNeedIds = array_column($requestData[$model->alias()]['examination_centre_special_needs'], 'special_need_type_id');
                        }

                        // put special needs into System Processes params
                        $SystemProcesses = TableRegistry::get('SystemProcesses');
                        $name = 'AddAllInstitutionsExamCentre';
                        $pid = '';
                        $processModel = $model->registryAlias();
                        $eventName = '';
                        $passArray = ['special_needs' => $specialNeedIds];
                        $params = json_encode($passArray);
                        $systemProcessId = $SystemProcesses->addProcess($name, $pid, $processModel, $eventName, $params);

                        $this->triggerAddAllInstitutionsExamCentreShell($academicPeriodId, $systemProcessId, $institutionTypeId);
                        $this->Alert->warning($this->aliasField('savingProcessStarted'), ['reset' => true]);
                        return true;
                    }
                }
            } else {
                return $model->save($entity);
            }
        };

        return $process;
    }

    // FIXME
    private function triggerAddAllInstitutionsExamCentreShell($academicPeriodId, $systemProcessId, $institutionTypeId = null)
    {
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';
        $args .= !is_null($systemProcessId) ? ' '.$systemProcessId : '';
        $args .= !is_null($institutionTypeId) ? ' '.$institutionTypeId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake AddAllInstitutionsExamCentre '.$args;
        $logs = ROOT . DS . 'logs' . DS . 'AddAllInstitutionsExamCentre.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            $pid = exec($shellCmd);
            Log::write('debug', $shellCmd);
        } catch(\Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when add all institutions : '. $ex);
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->ExaminationCentreSpecialNeeds->alias()
        ];
    }

    public function findNotLinkedExamCentres(Query $query, array $options)
    {
        if (isset($options['examination_id'])) {
            $examinationId = $options['examination_id'];

            $query
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->notMatching('Examinations', function ($q) use ($examinationId) {
                    return $q->where(['Examinations.id' => $examinationId]);
                });

            if (isset($options['examination_centre_type']) && !empty($options['examination_centre_type'])) {
                $type = $options['examination_centre_type'];

                if ($type == -1) {
                    // non-institution exam centres
                    $query->where(['ExaminationCentres.institution_id' => 0]);

                } else {
                    $query
                        ->matching('Institutions')
                        ->where(['Institutions.institution_type_id' => $type]);
                }
            }

            return $query;
        }
    }
}

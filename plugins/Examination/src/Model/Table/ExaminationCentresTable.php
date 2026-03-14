<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\I18n\Time;
use Cake\Log\Log;
use Cake\Http\ServerRequest;

class ExaminationCentresTable extends ControllerActionTable {
    use OptionsTrait;
    use HtmlTrait;

    private $examCentreName = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
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

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportExaminationCentreRooms']);
        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('OpenEmis.Section');

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->requirePresence('create_as', 'create')
            ->requirePresence('code')
            ->requirePresence('name')
            ->requirePresence('area_id')
            ->add('code', 'ruleUnique', [
                'rule' => ['validateUnique'],
                'provider' => 'table'
            ])
            ->requirePresence('institutions', 'create')
            ->allowEmpty('postal_code')
           // ->allowEmpty('fax')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'message' => __('The postal code does not match the configured format.'),
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

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
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
            ->enableAutoFields(true);
        return $query;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        if ($this->action == 'view' || $this->action == 'edit') {
            $examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');
            if($examCentreId==null){
                $examCentreId = 1;
            }
            $this->request = $this->request->withParam('pass.1', $this->paramsEncode(['id' => $examCentreId]));
            $extra['config']['selectedLink'] = ['controller' => 'Examinations', 'action' => 'ExamCentres', 'index'];
            $this->examCentreName = $this->get($examCentreId)->name;
        }
        $this->field('institution_id', ['visible' => false]);
        $this->fields['area_id']['visible'] = false;
        $this->fields['address']['visible'] = false;
        $this->fields['postal_code']['visible'] = false;
        $this->fields['contact_person']['visible'] = false;
        $this->fields['telephone']['visible'] = false;
        $this->fields['email']['visible'] = false;
        $this->fields['website']['visible'] = false;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // add examination centre button
        if (isset($extra['toolbarButtons']['add'])) {
            $extra['toolbarButtons']['add']['attr']['title'] = __('Add Examination Centre');
        }

        // link examinations button
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $linkExamButton['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentreExams', 'add'];
        $linkExamButton['type'] = 'button';
        $linkExamButton['label'] = '<i class="fa fa-link"></i>';
        $linkExamButton['attr'] = $toolbarAttr;
        $linkExamButton['attr']['title'] = __('Link Examination');
        $extra['toolbarButtons']['linkExam'] = $linkExamButton;

        $this->setFieldOrder(['code', 'name']);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Exam Centres','Examinations');
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {

        $serverRequest = $this->request;
        // Examination filter
        $examinationOptions = $this->Examinations->getExaminationOptions();
        $examinationOptions = ['-1' => '-- '.__('Select Examination').' --'] + $examinationOptions;
        $selectedExamination = $serverRequest->getQuery('examination_id') ?? -1;
        if($selectedExamination == -1){
            $selectedExamination = $this->ControllerAction->getQueryString('examination_id') ?? -1;
        }
        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
            $query->matching('Examinations');
            $where[$this->Examinations->aliasField('id')] = $selectedExamination;
        }else{

        }

        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);

        // sort
        $sortList = ['code', 'name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // search
        $extra['auto_search'] = false;
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $OR = [
                [$this->aliasField('name').' LIKE' => '%' . $search . '%'],
                [$this->aliasField('code').' LIKE' => '%' . $search . '%']
            ];
            $query->where(['OR' => $OR]);
        }
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['ExaminationCentreSpecialNeeds.SpecialNeedsTypes'])
            ->contain('Examinations')
            ->matching('Areas')
        ;
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', $this->examCentreName. ' - ' .__('Overview'));
        $this->controller->getExamCentresTab();
    }

    public function editBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', $this->examCentreName. ' - ' .__('Overview'));
        $this->controller->getExamCentresTab();
    }

    public function editBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $options['associated'][] = 'ExaminationCentreSpecialNeeds';
    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // manually delete hasMany ExamCentreSpecialNeeds data
        $specialNeedsFieldKey = 'examination_centre_special_needs';
        if (!array_key_exists($specialNeedsFieldKey, $data[$this->getAlias()])) {
            $data[$this->getAlias()][$specialNeedsFieldKey] = [];
        }

        // Get special need type ids POCOR-4231
        $SpecialNeedTypesTable = $this->ExaminationCentreSpecialNeeds->SpecialNeedsTypes;
        $allSpecialNeeds = $SpecialNeedTypesTable->getVisibleNeedTypes();
        $allSpecialNeedsData = array_keys($allSpecialNeeds);

        $specialNeedIds = array_column($data[$this->getAlias()][$specialNeedsFieldKey], 'special_need_type_id');
        $originalSpecialNeeds = $entity->extractOriginal([$specialNeedsFieldKey])[$specialNeedsFieldKey];

        // Get unique ids which are not present for remove POCOR-4231
        $RemoveSpecialNeedIds = array_diff($allSpecialNeedsData, $specialNeedIds);
        if (count($RemoveSpecialNeedIds) > 0 && $entity->institution_id > 0) {
            foreach ($RemoveSpecialNeedIds as $removeSNI) {
                $this->ExaminationCentreSpecialNeeds->deleteAll(
                       [  'special_need_type_id' => $removeSNI,
                          'examination_centre_id' => $entity->institution_id
                        ]);
            }
        }

        // Get unique ids for new special need type ids and save in table  POCOR-4231
        $associatedSpecialNeedsTemp = [];
        $associatedSpecialNeeds = $this->ExaminationCentreSpecialNeeds
            ->find('all')
            ->where(['examination_centre_id' => $entity->institution_id])
            ->toArray();
        foreach ($associatedSpecialNeeds as $associatedSpecialNeed) {
            $associatedSpecialNeedsTemp[] = $associatedSpecialNeed->special_need_type_id;
        }

        $AddNewSpecialNeedIds = array_diff($specialNeedIds, $associatedSpecialNeedsTemp);

        foreach($AddNewSpecialNeedIds as $AddNewSpecialNeedId) {
            $data = [
                'examination_centre_id' => $entity->institution_id,
                'special_need_type_id' => $AddNewSpecialNeedId,
                'created_user_id' => $entity->created_user_id,
                'created' => Time::now(),
            ];
            $newEntity = $this->ExaminationCentreSpecialNeeds->newEntity($data);
            $this->ExaminationCentreSpecialNeeds->save($newEntity);
        }
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $entity = $extra['entity'];
        $this->field('exam_centre_info_section', ['type' => 'section', 'title' => __('Examination Centre Information'), 'visible' => false]);
        $this->field('special_needs_section', ['type' => 'section', 'title' => __('Special Need Accommodations'), 'visible' => false]);

        if ($this->action == 'edit' || $this->action == 'add') {
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
                    $this->fields['email']['visible'] = true;
                    $this->fields['website']['visible'] = true;
                } else if ($entity->create_as == 'existing') {
                    $this->field('institution_type');
                    $this->field('add_all_institutions');
                    $this->field('institutions');
                    $this->fields['name']['visible'] = false;
                    $this->fields['code']['visible'] = false;
                } else {
                    $this->fields['name']['visible'] = false;
                    $this->fields['code']['visible'] = false;
                }
                $this->fields['special_needs_section']['visible'] = true;

                // field order
                $this->setFieldOrder(['exam_centre_info_section', 'create_as',
                    'institution_type', 'add_all_institutions', 'institutions', 'code', 'name', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone','email', 'website', 'special_needs_section', 'special_need_type_id']);

            } else if ($this->action == 'edit') {
                $this->field('area_id', ['entity' => $entity, 'visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
                $this->fields['name']['visible'] = true;
                $this->fields['code']['visible'] = true;
                $this->fields['address']['visible'] = true;
                $this->fields['postal_code']['visible'] = true;
                $this->fields['contact_person']['visible'] = true;
                $this->fields['telephone']['visible'] = true;
              //  $this->fields['fax']['visible'] = true;
                $this->fields['email']['visible'] = true;
                $this->fields['website']['visible'] = true;

                if ($entity->institution_id != 0) {
                    $this->fields['name']['type'] = 'readonly';
                    $this->fields['code']['type'] = 'readonly';
                    $this->fields['address']['type'] = 'text';
                    $this->fields['address']['attr']['disabled'] = 'disabled';
                    $this->fields['postal_code']['type'] = 'readonly';
                    $this->fields['contact_person']['type'] = 'readonly';
                   // $this->fields['fax']['type'] = 'readonly';
                    $this->fields['telephone']['type'] = 'readonly';
                    $this->fields['email']['type'] = 'readonly';
                    $this->fields['website']['type'] = 'readonly';
                }

                $this->fields['special_needs_section']['visible'] = true;
                $this->fields['exam_centre_info_section']['visible'] = true;

                // field order
                $this->setFieldOrder(['exam_centre_info_section', 'create_as',
                    'code', 'name', 'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'email', 'website', 'special_needs_section', 'special_need_type_id']);
            }

        } else if ($this->action == 'view') {
            $this->fields['area_id'] = array_merge($this->fields['area_id'], ['visible' => true, 'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => true]);
            $this->field('special_need_type_id', ['type' => 'custom_exam_centre_special_needs']);

            $this->fields['code']['visible'] = true;
            $this->fields['address']['visible'] = true;
            $this->fields['postal_code']['visible'] = true;
            $this->fields['contact_person']['visible'] = true;
            $this->fields['telephone']['visible'] = true;
            $this->fields['email']['visible'] = true;
            $this->fields['website']['visible'] = true;

            $this->setFieldOrder(['exam_centre_info_section', 'code', 'name',
                'area_id', 'address', 'postal_code', 'contact_person', 'telephone', 'email', 'website', 'special_need_type_id']);
        }
    }

    public function addEditOnSelectSpecialNeedType(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $ADD_ALL = -1;
        $fieldKey = 'examination_centre_special_needs';

        $SpecialNeedTypesTable = $this->ExaminationCentreSpecialNeeds->SpecialNeedsTypes;
        $allSpecialNeeds = $SpecialNeedTypesTable->getVisibleNeedTypes();
        $id = $data[$this->getAlias()]['special_need_type_id'];

        if (empty($data[$this->getAlias()][$fieldKey])) {
            $data[$this->getAlias()][$fieldKey] = [];
        }

        if ($id == $ADD_ALL) {
            $selectedNeeds = array_column($data[$this->getAlias()][$fieldKey], 'special_need_type_id');
            foreach ($allSpecialNeeds as $key => $obj) {
                if (!empty($selectedNeeds) && in_array($key, $selectedNeeds)) {
                    continue;
                }

                $data[$this->getAlias()][$fieldKey][] = [
                    'special_need_type_id' => $key,
                    'name' => $obj
                ];
            }
            $data[$this->getAlias()]['special_need_type_id'] = '';

        } else if ($id > 0) {
            try {
                $obj = $SpecialNeedTypesTable->get($id);

                $data[$this->getAlias()][$fieldKey][] = [
                    'special_need_type_id' => $id,
                    'name' => $obj->name
                ];

                $data[$this->getAlias()]['special_need_type_id'] = '';

            } catch (RecordNotFoundException $ex) {
                Log::write('debug', __METHOD__ . ': Record not found for special need type id: ' . $id);
            }
        }
        //POCOR-8674 start
         // Ensure the data is properly set in the request
         $this->request = $this->request->withData($this->getAlias(), $data[$this->getAlias()]);
        //POCOR-8674 end
    }

    public function onGetCustomExamCentreSpecialNeedsElement(EventInterface $event, $action, $entity, $attr, $options=[])
    {
        $requestData = $this->request->getData();
        $tableHeaders = [__('Special Need Type')];
        $tableCells = [];
        $alias = $this->getAlias();
        $fieldKey = 'examination_centre_special_needs';
        if ($action == 'view') {
            $associatedSpecialNeedsTemp = [];
            $associatedSpecialNeeds = $this->ExaminationCentreSpecialNeeds->find('all')->where(['examination_centre_id' => $entity->institution_id])->toArray();
            $associated = $entity->extractOriginal([$fieldKey]);
            foreach ($associatedSpecialNeeds as $associatedSpecialNeed) {
                $associatedSpecialNeedsTemp[] = $associatedSpecialNeed->special_need_type_id;
            }

            if (!empty($associated[$fieldKey])) {
                foreach ($associated[$fieldKey] as $key => $obj) {
                    if (in_array($obj->special_need_type_id, $associatedSpecialNeedsTemp)) {
                        $rowData = [];
                        $rowData[] = $obj->special_needs_type->name;
                        $tableCells[] = $rowData;
                    }
                }
            }
        } else if ($action == 'edit') {
            // options for special needs types
            $SpecialNeedTypesTable = $this->ExaminationCentreSpecialNeeds->SpecialNeedsTypes;
            $specialNeedsOptions = $SpecialNeedTypesTable->getVisibleNeedTypes();

            $tableHeaders[] = ''; // for delete column
            $Form = $event->getSubject()->Form;
            $Form->unlockField('ExaminationCentres.examination_centre_special_needs');

            $selectedSpecialNeeds = [];
            if ($this->request->is(['get'])) {
                $associatedSpecialNeedsTemp = [];
                // $associatedSpecialNeeds = $this->ExaminationCentreSpecialNeeds->find('all')->where(['examination_centre_id' => $entity->institution_id])->toArray();
                $associatedSpecialNeeds = $this->ExaminationCentreSpecialNeeds->find('all')->toArray();

                foreach ($associatedSpecialNeeds as $associatedSpecialNeed) {
                    $associatedSpecialNeedsTemp[] = $associatedSpecialNeed->special_need_type_id;
                }
                $associated = $entity->extractOriginal([$fieldKey]);
                if (!empty($associated[$fieldKey])) {
                    foreach ($associated[$fieldKey] as $key => $obj) {
                        if (in_array($obj->special_need_type_id, $associatedSpecialNeedsTemp)) {
                            $selectedSpecialNeeds[] = [
                                'special_need_type_id' => $obj->special_need_type_id,
                                'name' => $obj->special_needs_type->name
                            ];
                        }
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
        return $event->getSubject()->renderElement('../ControllerAction/table_with_dropdown', ['attr' => $attr]);
    }

    public function onUpdateFieldAreaId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = $attr['entity'];
        if ($entity->institution_id != 0) {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = __($entity->_matchingData['Areas']->name);
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionType(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldAddAllInstitutions(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $institutionOptions = $this->Institutions->find('NotExamCentres');

            //POCOR-8674 start
            // if (!empty($request->data[$this->getAlias()]['institution_type'])) {
            //     $type = $request->data[$this->getAlias()]['institution_type'];
            //     $institutionOptions->where([$this->Institutions->aliasField('institution_type_id') => $type]);
            // }
            if (!empty($request->getData()[$this->getAlias()]['institution_type'])) {
                $type = $request->getData()[$this->getAlias()]['institution_type'];
                $institutionOptions->where([$this->Institutions->aliasField('institution_type_id') => $type]);
            }
            //POCOR-8674 end

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

    public function addOnChangeAddAllInstitutions(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        //POCOR-8674 start
        // if (array_key_exists($this->getAlias(), $data)) {
        //     if (array_key_exists('institutions', $data[$this->getAlias()])) {
        //         $data[$this->getAlias()]['institutions'] = '';
        //     }
        // }
        $dataArray = $data->getArrayCopy();
        if (array_key_exists($this->getAlias(), $dataArray)) {
            if (array_key_exists('institutions', $dataArray[$this->getAlias()])) {
                $dataArray[$this->getAlias()]['institutions'] = '';
            }
        }
        //POCOR-8674 end
    }

    public function onUpdateFieldInstitutions(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            //POCOR-8674 start
            // $addAllInstitutions = isset($request->data[$this->getAlias()]['add_all_institutions']) ? $request->data[$this->getAlias()]['add_all_institutions'] : 0;
            $addAllInstitutions = isset($request->getData()[$this->getAlias()]['add_all_institutions']) ? $request->getData()[$this->getAlias()]['add_all_institutions'] : 0;
            //POCOR-8674 end
            if ($addAllInstitutions == 1) {
                $attr['type'] = 'hidden';

            } else {
                $institutionQuery = $this->Institutions
                    ->find()
                    ->find('NotExamCentres')
                    ->contain(['Statuses'])
                    ->order([$this->Institutions->aliasField('name')]);

                //POCOR-8674 start
                // if no institution type is selected, all institutions will be shown
                // if (!empty($request->data[$this->getAlias()]['institution_type'])) {
                //     $type = $request->data[$this->getAlias()]['institution_type'];
                //     $institutionQuery->where([$this->Institutions->aliasField('institution_type_id') => $type]);
                // }
                if (!empty($request->getData()[$this->getAlias()]['institution_type'])) {
                    $type = $request->getData()[$this->getAlias()]['institution_type'];
                    $institutionQuery->where([$this->Institutions->aliasField('institution_type_id') => $type]);
                }
                //POCOR-8674 end

                $institutionRecords = $institutionQuery->all();

                // POCOR-3983 Disabled(grey off) INACTIVE institutions
                $institutionOptions = [];
                foreach ($institutionRecords as $institution) {
                    $institutionOptions[$institution->id]['value'] = $institution->id;
                    $institutionOptions[$institution->id]['text'] = $institution->code_name;

                    if ($institution->status->code == 'INACTIVE') {
                        $institutionOptions[$institution->id][0] = 'disabled';
                    }
                }
                // End POCOR-3983

                $attr['type'] = 'chosenSelect';
                $attr['options'] = $institutionOptions;
                $attr['fieldName'] = $this->getAlias().'.institutions';

            }

            return $attr;
        }
    }


    public function onUpdateFieldCreateAs(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $extra['redirect']['action'] = 'ExamCentres';

        $requestData[$this->getAlias()]['institution_id'] = 0;
        if (!isset($requestData[$this->getAlias()]['area_id'])) {
            $requestData[$this->getAlias()]['area_id'] = 0;
        }

        if ($requestData[$this->getAlias()]['create_as'] == 'new') {
            $patchOptions['validate'] = 'noInstitutions';
        } else if ($requestData[$this->getAlias()]['create_as'] == 'existing' && $requestData[$this->getAlias()]['add_all_institutions'] == 1) {
            $patchOptions['validate'] = 'allInstitutions';
        } else {
            $patchOptions['validate'] = 'institutions';
        }
    }

    public function addBeforeSave(EventInterface $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {

            if (isset($requestData[$model->getAlias()]['institutions']) && !empty($requestData[$model->getAlias()]['institutions'])) {
                $institutions = $requestData[$model->getAlias()]['institutions'];
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
                        $requestData['telephone'] = isset($institutionRecord->telephone) ? $institutionRecord->telephone : '';
                      //  $requestData['fax'] = $institutionRecord->fax;
                        $requestData['email'] = $institutionRecord->email;
                        $requestData['website'] = $institutionRecord->website;
                        $newEntity = $model->newEntity($requestData->getArrayCopy());

                        if ($newEntity->getErrors('telephone')) {
                            $this->Alert->getErrors('general.contactInstitution.telephone', ['reset' => 'override']);
                            return false;
                        }

                        $newEntities[] = $newEntity;
                    }
                }
                return $model->saveMany($newEntities);

            } else if (isset($requestData[$model->getAlias()]['add_all_institutions']) && $requestData[$model->getAlias()]['add_all_institutions'] == 1) {
                if (empty($entity->getErrors())) {
                    $institutionTypeId = $requestData['ExaminationCentres']['institution_type'];
                    $institutionTypeId = !empty($requestData[$model->getAlias()]['institution_type']) ? $requestData[$model->getAlias()]['institution_type'] : $institutionTypeId;

                    $specialNeedIds = [];
                    if (isset($requestData[$model->getAlias()]['examination_centre_special_needs'])) {
                        $specialNeedIds = array_column($requestData[$model->getAlias()]['examination_centre_special_needs'], 'special_need_type_id');
                    }
                    // put special needs into System Processes params
                    $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
                    $name = 'AddAllInstitutionsExamCentre';
                    $pid = '';
                    $processModel = $model->getRegistryAlias();
                    $eventName = '';
                    $passArray = ['special_needs' => $specialNeedIds, 'institution_type_id' => $institutionTypeId];
                    //$passArray = ['special_needs' => $specialNeedIds];
                    $params = json_encode($passArray);
                    $systemProcessId = $SystemProcesses->addProcess($name, $pid, $processModel, $eventName, $params);

                    $this->triggerAddAllInstitutionsExamCentreShell($systemProcessId, $institutionTypeId);
                    $this->Alert->warning($this->aliasField('savingProcessStarted'), ['reset' => true]);
                    return true;

                }
            } else {
                return $model->save($entity);
            }
        };

        return $process;
    }

    private function triggerAddAllInstitutionsExamCentreShell($systemProcessId, $institutionTypeId = null)
    {
        $args = '';
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

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->ExaminationCentreSpecialNeeds->getAlias(), $this->ExaminationCentreRooms->getAlias()
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
                })
            ;

            if (isset($options['examination_centre_type']) && !empty($options['examination_centre_type'])) {
                $type = $options['examination_centre_type'];

                if ($type == -1) {
                    // non-institution exam centres
                    $query->where(['ExaminationCentres.institution_id' => 0]);

                } else {
                    /*POCOR-5737 starts*/
                    $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
                    $getCentreTypeId = $SystemProcesses->find()
                                    ->where([$SystemProcesses->aliasField('name') => 'AddAllInstitutionsExamCentre'])
                                    ->order([$SystemProcesses->aliasField('created') => 'DESC'])
                                    ->first();
                    $convertData = json_decode($getCentreTypeId->params);
                    $lastInsertedTypeId = $convertData->institution_type_id;
                    if (!empty($lastInsertedTypeId) && $lastInsertedTypeId == $type) {
                        $query->where(['ExaminationCentres.institution_id' => 0]);
                    } else {
                        $query
                        ->matching('Institutions')
                        ->where(['Institutions.institution_type_id' => $type]);
                    }
                    /*POCOR-5737 ends*/
                }
            }

            return $query;
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'create_as') {
            return __('Created As');
        }  elseif ($field == 'special_need_type_id') {
            return __('Special Need Type');
        } elseif ($field == 'institution_type') {
            return __('Institution Type');
        } elseif ($field == 'add_all_institutions') {
            return __('Add All Institutions');
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
        }elseif ($field == 'code') {
            return __('Code');
        }elseif ($field == 'name') {
            return __('Name');
        }elseif ($field == 'area_id') {
            return __('Area');
        }elseif ($field == 'address') {
            return __('Address');
        }elseif ($field == 'postal_code') {
            return __('Postal Code');
        }elseif ($field == 'contact_person') {
            return __('Contact Person');
        }elseif ($field == 'telephone') {
            return __('Telephone');
        }elseif ($field == 'email') {
            return __('Email');
        }elseif ($field == 'website') {
            return __('Website');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}

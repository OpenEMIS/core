<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;

use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\View\NumberHelper;
use Cake\Network\Session;

class InstitutionAssetsTable extends ControllerActionTable
{
    use OptionsTrait;


    private $accessibilityOptions = [];
    private $purposeOptions = [];
    public $currency = '';

    public function initialize(array $config)
    {
        parent::initialize($config);

//        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AssetStatuses', ['className' => 'Institution.AssetStatuses']);
        $this->belongsTo('AssetTypes', ['className' => 'Institution.AssetTypes']);
        $this->belongsTo('AssetMakes', ['className' => 'FieldOption.AssetMakes']);
        $this->belongsTo('AssetModels', ['className' => 'FieldOption.AssetModels']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'user_id']);
        $this->belongsTo('AssetConditions', ['className' => 'Institution.AssetConditions']);
        $this->belongsTo('InstitutionRooms', ['className' => 'Institution.InstitutionRooms']);

        $this->addBehavior('Import.ImportLink');
        // POCOR-6152 export button <vikas.rathore@mail.valuecoders.com>
        $this->addBehavior('Excel', [
            'excludes' => [
//                'academic_period_id',
                'id'
            ],
            'pages' => ['index'],
        ]);
        // POCOR-6152 export button <vikas.rathore@mail.valuecoders.com>
    }

    // POCOR-6152 set breadcrumb header <vikas.rathore@mail.valuecoders.com>
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InstitutionAssets';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $this->currency = $ConfigItems->value('currency');

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions', 'Assets', 'Details');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
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
    // POCOR-6152 set breadcrumb header <vikas.rathore@mail.valuecoders.com>

    // setting up  fields and filter POCOR-6152
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {

        $extra = $this->setFilterOptions($extra);
        $this->setFieldsOrder();

    }

    // setting up  fields and filter POCOR-6152


    // setting up query for index POCOR-6152 start
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
//        $academicPeriod = ($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent() ;
        $assetType = ($this->request->query('asset_type_id')) ? $this->request->query('asset_type_id') : 0;
        $accessibility = $this->request->query('accessibility');;

        if ($assetType > 0) {
            $query->where([
                $this->aliasField('asset_type_id') => $assetType
            ]);
        }
        if ($accessibility != "") {
            $query->where([
                $this->aliasField('accessibility') => $accessibility
            ]);
        }

//        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
//            return $results->map(function ($row) {
//                if($row->purpose == 1){
//                    $row['purpose'] = 'Teaching';
//                }else{
//                    $row['purpose'] = 'Non-Teaching';
//                }
//
//                if($row->accessibility == 1){
//                    $row['accessibility'] = 'Accessible';
//                }else{
//                    $row['accessibility'] = 'Not Accessible';
//                }
//
//                return $row;
//            });
//        });
    }
    // setting up query for index POCOR-6152 ends

    // POCOR-6152 Export Functionality 
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query->select([
        ]);
        if(isset( $this->request)){
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
            $query->where([
                    $this->aliasField('institution_id') => $institutionId,
                ]);
            $assetType = ($this->request->query('asset_type_id')) ? $this->request->query('asset_type_id') : 0;
            $accessibility = $this->request->query('accessibility');


            if ($assetType > 0) {
                $query->where([
                    $this->aliasField('asset_type_id') => $assetType
                ]);
            }
            if ($accessibility != "") {
                $query->where([
                    $this->aliasField('accessibility') => $accessibility
                ]);
            }
        }
//        $academicPeriod = ($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent() ;

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                if ($row->purpose == 1) {
                    $row['purpose'] = 'Teaching';
                } else {
                    $row['purpose'] = 'Non-Teaching';
                }

                if ($row->accessibility == 1) {
                    $row['accessibility'] = 'Accessible';
                } else {
                    $row['accessibility'] = 'Not Accessible';
                }
                return $row;
            });
        });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $this->log($fields, 'debug');
        $extraField[] = [
            'key' => 'InstitutionAssets.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Code')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.description',
            'field' => 'description',
            'type' => 'string',
            'label' => __('Description')
        ];

        $extraField[] = [
            'key' => 'purpose',
            'field' => 'purpose',
            'type' => 'string',
            'label' => __('Purpose')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.asset_type_id',
            'field' => 'asset_type_id',
            'type' => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.asset_make_id',
            'field' => 'asset_make_id',
            'type' => 'string',
            'label' => __('Make')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.asset_model_id',
            'field' => 'asset_model_id',
            'type' => 'string',
            'label' => __('Model')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.serial_number',
            'field' => 'serial_number',
            'type' => 'string',
            'label' => __('Serial Number')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.purchase_date',
            'field' => 'purchase_date',
            'type' => 'date',
            'label' => __('Purchase Date')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.purchase_order',
            'field' => 'purchase_order',
            'type' => 'string',
            'label' => __('Purchase Order')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.cost',
            'field' => 'cost',
            'type' => 'string',
            'label' => __('Cost')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.stocktake_date',
            'field' => 'stocktake_date',
            'type' => 'date',
            'label' => __('Stocktake Date')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.lifespan',
            'field' => 'lifespan',
            'type' => 'string',
            'label' => __('Lifespan')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.institution_room_id',
            'field' => 'institution_room_id',
            'type' => 'string',
            'label' => __('Location')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.user_id',
            'field' => 'user_id',
            'type' => 'string',
            'label' => __('User')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.accessibility',
            'field' => 'accessibility',
            'type' => 'string',
            'label' => __('Accessibility')
        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.asset_condition_id',
            'field' => 'asset_condition_id',
            'type' => 'string',
            'label' => __('Condition')
        ];

//        $extraField[] = [
//            'key' => 'InstitutionAssets.depreciation',
//            'field' => 'depreciation',
//            'type' => 'string',
//            'label' => __('Disposal')
//        ];

        $extraField[] = [
            'key' => 'InstitutionAssets.asset_status_id',
            'field' => 'asset_status_id',
            'type' => 'string',
            'label' => __('Status')
        ];

        $fields->exchangeArray($extraField);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        // purpose fields POCOR-6152
        $this->purposeOptions = $this->getSelectOptions($this->aliasField('purpose'));
        $this->accessibilityOptions =
            $this->getSelectOptions($this->aliasField('accessibility'));
        $makeOptions = $this->getMakeOptions();
        $modelOptions = $this->getModelOptions();
        $userOptions = $this->getUserOptions();
        $locationOptions = $this->getLocationOptions();
        $this->fields['purpose']['type'] = 'select';
        $this->fields['asset_type_id']['type'] = 'select';
        $this->fields['asset_make_id']['type'] = 'select';
        $this->fields['asset_make_id']['default'] = -1;
        $this->fields['asset_make_id']['options'] = $makeOptions;
        $this->fields['asset_make_id']['empty'] = true;
        $this->fields['asset_model_id']['type'] = 'select';
        $this->fields['asset_make_id']['options'] = $makeOptions;
        $this->fields['asset_model_id']['empty'] = true;
        $this->fields['asset_model_id']['options'] = $modelOptions;
        $this->fields['asset_condition_id']['type'] = 'select';
        $this->fields['asset_status_id']['type'] = 'select';
        $this->fields['institution_room_id']['type'] = 'select';
        $this->fields['institution_room_id']['empty'] = true;
        $this->fields['institution_room_id']['options'] = $locationOptions;
        $this->fields['user_id']['type'] = 'select';
        $this->fields['user_id']['empty'] = true;
        $this->fields['user_id']['options'] = $userOptions;
        $this->fields['accessibility']['type'] = 'select';
//        $this->log($this->fields['asset_make_id'], 'debug');
        $this->fields['purpose']['options'] = $this->purposeOptions;
        $this->fields['accessibility']['options'] = $this->accessibilityOptions;

    }

    private function getMakeOptions()
    {
        $makeOptions = [];
        if (array_key_exists($this->alias(), $this->request->data)
            && array_key_exists('asset_type_id', $this->request->data[$this->alias()])
            && !empty($this->request->data[$this->alias()]['asset_type_id'])) {
            $asset_type_id = $this->request->data[$this->alias()]['asset_type_id'];
            $makes = TableRegistry::get('asset_makes');
            $makeOptions = $makes->find('list')
                ->select(['id', 'name'])
                ->orderAsc('order')
                ->where([
                    $makes->aliasField('visible') => 1,
                    $makes->aliasField('asset_type_id') => $asset_type_id
                ])
                ->toArray();
        }
        return $makeOptions;
    }

    private function getModelOptions()
    {
        $modelOptions = [];
        if (array_key_exists($this->alias(), $this->request->data)
            && array_key_exists('asset_type_id', $this->request->data[$this->alias()])
            && !empty($this->request->data[$this->alias()]['asset_make_id'])) {
            $asset_make_id = $this->request->data[$this->alias()]['asset_make_id'];
            $models = TableRegistry::get('asset_models');
            $modelOptions = $models->find('list')
                ->select(['id', 'name'])
                ->orderAsc('order')
                ->where([
                    $models->aliasField('visible') => 1,
                    $models->aliasField('asset_make_id') => $asset_make_id
                ])
                ->toArray();
        }
        return $modelOptions;
    }

    private function getUserOptions()
    {
        $userOptions = [];
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $staff = TableRegistry::get('institution_staff');
        $staff_ids = $staff->find('all')
            ->select('staff_id')
            ->where(['institution_id' => $institutionId])
            ->toArray();
        $staffIds = $this->array_column($staff_ids, 'staff_id');
        $staffIds = array_unique($staffIds);
        if (empty($staffIds)) {
            $staffIds = [0];
        }
        $users = TableRegistry::get('security_users');
        $user_options = $users->find('all')
            ->select(['id',
                'first_name',
                'last_name',
                'openemis_no'])
            ->orderAsc('id')
            ->where([
                $users->aliasField('id IN') => $staffIds
            ])
            ->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    $row['name'] =
                        $row['openemis_no'] . ' ' .
                        $row['first_name'] . ' ' .
                        $row['last_name'];
                    unset($row['openemis_no']);
                    unset($row['first_name']);
                    unset($row['last_name']);
                    return $row;
                });
            })
            ->toArray();
        foreach ($user_options as $user_option) {
            $userOptions[$user_option->id] = $user_option->name;
        }
//            $this->log($userOptions, 'debug');
        return $userOptions;
    }

    private function getLocationOptions()
    {
        $roomOptions = [];
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $rooms = TableRegistry::get('institution_rooms');
        $room_options = $rooms->find('all')
            ->select(['id',
                'code',
                'name'])
            ->orderAsc('id')
            ->where([
                $rooms->aliasField('institution_id') => $institutionId
            ])
            ->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    $row['code_name'] =
                        $row['code'] . ' ' .
                        $row['name'];
                    return $row;
                });
            })
            ->toArray();
        foreach ($room_options as $room_option) {
            $roomOptions[$room_option->id] = $room_option->code_name;
        }
//            $this->log($roomOptions, 'debug');
        return $roomOptions;
    }

    // set up fields in add page POCOR-6152

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setFieldsOrder();
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setFieldsOrder();
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setFieldsOrder();
    }

    /**
     * @param ArrayObject $extra
     */
    private function setFilterOptions(ArrayObject $extra)
    {
        $assetTypes = $this->AssetTypes
            ->find('optionList', ['defaultOption' => false])
            ->find('visible')
            ->find('order')
            ->toArray();

        $assetTypeOptions = ['' => __('All Types')] + $assetTypes;
        $extra['selectedAssetType'] = $this->request->query('asset_type_id');
        // set asset types filter POCOR-6152

        // set Accessibilities filter POCOR-6152
        $this->accessibilityOptions = $this->getSelectOptions($this->aliasField('accessibility'));

        $accessibilityOptions = ['' => __('All Accessibilities')] + $this->accessibilityOptions;
        $extra['selectedAccessibility'] = $this->request->query('accessibility');
        // set Accessibilities filter POCOR-6152

        $extra['elements']['control'] = [
            'name' => 'Institution.Assets/controls',
            'data' => [
                'assetTypeOptions' => $assetTypeOptions,
                'selectedAssetType' => $extra['selectedAssetType'],
                'accessibilityOptions' => $accessibilityOptions,
                'selectedAccessibility' => $extra['selectedAccessibility']
            ],
            'order' => 3
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'asset_status_id') {
            return __('Status');
        }
        if ($field == 'asset_condition_id') {
            return __('Condition');
        }
        if ($field == 'asset_type_id') {
            return __('Type');
        }
        if ($field == 'asset_make_id') {
            return __('Make');
        }
        if ($field == 'asset_model_id') {
            return __('Model');
        }
        if ($field == 'institution_room_id') {
            return __('Location');
        }
//        if ($field == 'depreciation') {
//            return __('Disposal');
//        }
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function onGetUserId(Event $event, Entity $entity)
    {
        $user = self::getRelatedRecord('security_users', $entity->user_id);
        if (!$entity->user_id) {
            return "";
        }
        if ($user) {
            return $user['first_name'] . ' ' . $user['last_name'];
        }
        return 'Deleted User Record. Id #:' . $entity->user_id;
    }

    public function onGetPurpose(Event $event, Entity $entity)
    {
        if ($entity->purpose) {
            $purpose = 'Teaching';
        } else {
            $purpose = 'Non-Teaching';
        }
        return $purpose;
    }

    public function onGetAccessibility(Event $event, Entity $entity)
    {
        if ($entity->accessibility) {
            $accessibility = 'Accessible';
        } else {
            $accessibility = 'Not Accessible';
        }
        return $accessibility;
    }

    public function onGetCost(Event $event, Entity $entity)
    {
        if (!$entity->cost) {
            return "";
        }
        $formattedAmount = $this->currency . ' ' . number_format($entity->cost, 2);
        return $formattedAmount; // Output: $1,234.56
    }

    public function onExcelGetCost(Event $event, Entity $entity)
    {
        if (!$entity->cost) {
            return "";
        }
        $formattedAmount = $this->currency . '' . number_format($entity->cost, 2);
        return $formattedAmount; // Output: $1,234.56
    }

    public function onGetDepreciation(Event $event, Entity $entity)
    {
//        if (!$entity->depreciation) {
            return "";
//        }
//        $formattedAmount = $this->currency . ' ' . number_format($entity->depreciation, 2);
//        return $formattedAmount; // Output: $1,234.56
    }

    public function onExcelGetDepreciation(Event $event, Entity $entity)
    {
//        if (!$entity->depreciation) {
            return "";
//        }
//        $formattedAmount = $this->currency . '' . number_format($entity->depreciation, 2);
//        return $formattedAmount; // Output: $1,234.56
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return null;
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            null;
        }
        return null;
    }

    public function onUpdateFieldAssetTypeId(Event $event, array $attr, $action, $request)
    {
        $optionsTable = TableRegistry::get('asset_types');
        $getOptions = $optionsTable->find('list')->select(['id','name'])->toArray();
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = $getOptions;
            $attr['onChangeReload'] = 'changeAssetTypeId';
        }
        return $attr;
    }

    public function addEditOnChangeAssetTypeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request->data[$this->alias()]['asset_type_id'] = $entity->asset_type_id;
    }

    public function onUpdateFieldAssetMakeId(Event $event, array $attr, $action, $request)
    {
        $data = isset($this->request->data) ? $this->request->data : null;
        $data = isset($data[$this->alias()]) ? $data[$this->alias()] : null;
        $where = ["1=1"];
        $option = isset($data['asset_type_id']) ? $data['asset_type_id'] : null;
        $optionsTable = TableRegistry::get('asset_makes');
        if($option){
            $where = [$optionsTable->aliasField('asset_type_id') => $option];
        }
        $getOptions = $optionsTable->find('list')->select(['id','name'])->where($where)->toArray();
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['attr']['multiple'] = false;
            $attr['select'] = true;
            $attr['options'] = $getOptions;
            $attr['onChangeReload'] = 'changeAssetTypeId';
        }
        return $attr;
    }

    public function onUpdateFieldAssetModelId(Event $event, array $attr, $action, $request)
    {
        $data = isset($this->request->data) ? $this->request->data : null;
        $data = isset($data[$this->alias()]) ? $data[$this->alias()] : null;
        $where = ["1=1"];
        $option = isset($data['asset_make_id']) ? $data['asset_make_id'] : null;
        $optionsTable = TableRegistry::get('asset_models');
        if($option){
            $where = [$optionsTable->aliasField('asset_make_id') => $option];
        }
        $getOptions = $optionsTable->find('list')->select(['id','name'])->where($where)->toArray();
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['attr']['multiple'] = false;
            $attr['select'] = true;
            $attr['options'] = $getOptions;
        }
        return $attr;
    }

    public function addEditOnChangeAssetMakeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request->data[$this->alias()]['asset_make_id'] = $entity->asset_make_id;
    }

    private function setFieldsOrder()
    {
        $this->field('code', ['visible' => true]);
        $this->field('description', ['visible' => true]);
        $this->field('purpose', ['visible' => true]);
        $this->field('asset_type_id', ['visible' => true]);
        $this->field('asset_make_id', ['visible' => true]);
        $this->field('asset_model_id', ['visible' => true]);
        $this->field('serial_number', ['visible' => true]);
        $this->field('purchase_date', ['visible' => true]);
        $this->field('purchase_order', ['visible' => true]);
        $this->field('cost', ['visible' => true]);
        $this->field('stocktake_date', ['visible' => true]);
        $this->field('lifespan', ['visible' => true]);
        $this->field('institution_room_id', ['visible' => true]);
        $this->field('user_id', ['visible' => true]);
        $this->field('accessibility', ['visible' => true]);
        $this->field('asset_condition_id', ['visible' => true]);
        $this->field('depreciation', ['visible' => false]);
        $this->field('asset_status_id', ['visible' => true]);
    }


}

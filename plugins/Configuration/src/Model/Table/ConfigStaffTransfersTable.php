<?php
namespace Configuration\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

use App\Model\Table\ControllerActionTable;

class ConfigStaffTransfersTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST SELECT_INSTITUTION_TYPES = 1;
    CONST SELECT_ALL_INSTITUTION_TYPES = '-1';

    public function initialize(array $config)
    {
        $this->table('config_items');
        parent::initialize($config);
 
        $this->addBehavior('Configuration.ConfigItems');

        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name', ['visible' => ['index' => true]]);
        $this->field('code', ['visible' => false]);
        $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $this->field('default_value', ['visible' => ['view' => true]]);
        $this->field('editable', ['visible' => false]);
        $this->field('visible', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
    }
  

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $extra['elements']['controls'] = $this->buildSystemConfigFilters();
        $this->checkController();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('type') => 'Staff Transfers']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->has('code')) {
            switch ($entity->code) {
                case 'enable_staff_transfer':
                    if ($entity->value != self::SELECT_ALL_INSTITUTION_TYPES) {
                        $entity['institution_type_selection'] = '1';
                        $id = explode(',', $entity->value);
                        $conditions = [];
                        $tmp = [];
                        foreach ($id as $key => $value) {
                            $tmp[] = ['id =' => $value];
                        }
                        $conditions['OR'] = $tmp;
                        $institutionTypesName = $this->getInstitutionTypes($conditions);
                        $entity->value = $institutionTypesName;
                    } else if ($entity->value == self::SELECT_ALL_INSTITUTION_TYPES) {
                        $entity['institution_type_selection'] = $entity->value;
                    }
                    break;
                default:
                    break;
            }
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onGetValue(Event $event, Entity $entity)
    {
        if ($entity->has('code')) {
            switch ($entity->code) {
                case 'enable_staff_transfer':
                    $conditions = [];
                    $tmp = [];
                    if ($entity->value == self::SELECT_ALL_INSTITUTION_TYPES) {
                        $institutionTypeObj = $this->getInstitutionTypes();
                    } else {
                        $institutionTypeIds = explode(",", $entity->value);
                        foreach ($institutionTypeIds as $key => $value) {
                            $tmp[] = ['id =' => $value];
                        }
                        $conditions['OR'] = $tmp;
                        $institutionTypeObj = $this->getInstitutionTypes($conditions);
                    }
                    foreach ($institutionTypeObj as $key => $value) {
                        $institutionTypeNames[] = $value['name'];
                    }
                    $valueOptions = implode(", ", $institutionTypeNames);
                    return $valueOptions;
                    break;

                case 'restrict_staff_transfer_by_sector':
                    $valueOptions = $this->getSelectOptions('general.yesno');
                   
                    return array_key_exists($entity->value, $valueOptions) ? $valueOptions[$entity->value] : $valueOptions[$entity->value];
                    break;
            }
        }
    }

    public function onGetDefaultValue(Event $event, Entity $entity)
    {
        if ($entity->has('code')) {
            switch ($entity->code) {
                case 'enable_staff_transfer':
                    $valueOptions = $this->getSelectOptions('StaffTransfers.institution_type_selection');
                    break;

                case 'restrict_staff_transfer_by_sector':
                    $valueOptions = $this->getSelectOptions('general.yesno');
                    break;
            }
        }
        return array_key_exists($entity->default_value, $valueOptions) ? $valueOptions[$entity->default_value] : $entity->default_value;
    }

    public function editbeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if ($entity->institution_type_selection == self::SELECT_INSTITUTION_TYPES) {
            $institution_ids = $requestData['ConfigStaffTransfers']['value']['_ids'];
            $ids = implode(",", $institution_ids);
            $entity->value = $ids;
        }
    }

    public function onUpdateFieldInstitutionTypeSelection(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        if ($entity->has('code')) {
            switch ($entity->code) {
                case 'enable_staff_transfer':
                    $institutionTypeSelectionOptions = $this->getSelectOptions('StaffTransfers.institution_type_selection');
                    $attr['type'] = 'select';
                    $attr['options'] = $institutionTypeSelectionOptions;
                    $attr['onChangeReload'] = true;
                    break;

                case 'restrict_staff_transfer_by_sector':
                    $attr['visible'] = false;
                    break;
                
                default:
                    break;
            }
        }
        return $attr;
    }

    public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        if ($entity->has('code')) {
            switch ($entity->code) {
                case 'enable_staff_transfer':
                    if ($entity->has('institution_type_selection')) {
                        $institutionTypeSelection = $entity->institution_type_selection;

                        if ($institutionTypeSelection == self::SELECT_INSTITUTION_TYPES) {
                            $institutionTypesNames = $this->getInstitutionTypes();
                            foreach ($institutionTypesNames as $key => $value) {
                                $institutionTypeId = $value['id'];
                                $institutionTypes[$institutionTypeId] = $value['name'];
                            }
                            $institutionTypeOptions = $institutionTypes;
                            $attr['type'] = 'chosenSelect';
                            $attr['placeholder'] = __('Select Institution Types');
                            $attr['attr'] = ['required' => true];
                            $attr['options'] = $institutionTypeOptions;
                        } elseif ($institutionTypeSelection == self::SELECT_ALL_INSTITUTION_TYPES) {
                            $attr['value'] = self::SELECT_ALL_INSTITUTION_TYPES;
                            $attr['type'] = 'readonly';
                            $attr['attr']['value'] = __('All Institution Types Selected');
                        }
                        $attr['attr']['label'] = __('Institution Types');
                    }
                    break;

                case 'restrict_staff_transfer_by_sector':
                    $valueOptions = $this->getSelectOptions('general.yesno');
                    $attr['type'] = 'select';
                    $attr['options'] = $valueOptions;
                    break;
                
                default:
                    break;
            }
        }
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('institution_type_selection', [
            'visible' => ['view' => false,  'edit' => true],
            'entity' => $entity
        ]);
        $this->field('value', [
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'type', 'label', 'institution_type_selection', 'value', 'default_value'
        ]);
    }


    public function getEnableStaffTransferConfig()
    {
        $enableStaffTransfer = TableRegistry::get($this->table('config_items'));
        $enableStaffTransferConfig = $enableStaffTransfer
        ->findByCode('enable_staff_transfer')
        ->first();
        $enableStaffTransferConfigValue = $enableStaffTransferConfig->value;

        return $enableStaffTransferConfigValue;
    }

    public function getRestrictStaffTransferBySectorConfig()
    {
        $restrictStaffTransferBySector = TableRegistry::get($this->table('config_items'));
        $restrictStaffTransferBySectorConfig = $restrictStaffTransferBySector
        ->findByCode('restrict_staff_transfer_by_sector')
        ->first();

        $restrictStaffTransferBySectorValue = $restrictStaffTransferBySectorConfig->value;
        return $restrictStaffTransferBySectorValue;
    }

    public function getInstitutionTypes($conditions = [])
    {
        $institutionTypeObj = TableRegistry::get('Institution.Types')
            ->find()
            ->where($conditions)
            ->order(['order' => 'ASC'])
            ->toArray();

        return $institutionTypeObj;
    }
}

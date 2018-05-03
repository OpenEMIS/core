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

        $this->InstitutionTypes = TableRegistry::get('Institution.Types');
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $submit = isset($data['submit']) ? $data['submit'] : 'save';
        if ($submit == 'save') {
            if ($data['code'] == 'enable_staff_transfer') {
                if (isset($data['institution_type_selection']) && $data['institution_type_selection'] == self::SELECT_INSTITUTION_TYPES) {
                    if (isset($data['value']['_ids']) && !empty($data['value']['_ids'])) {
                        $institutionTypeIds = $data['value']['_ids'];
                        $data['value'] = implode(",", $institutionTypeIds);
                    } else {
                        $data['value'] = '';
                    }
                }
            }
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name', ['visible' => ['index' => true]]);
        $this->field('code', ['type' => 'hidden']);
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
        $value = '';
        if ($entity->has('code')) {
            switch ($entity->code) {
                case 'enable_staff_transfer':
                    $list = [];
                    if ($entity->value == self::SELECT_ALL_INSTITUTION_TYPES) {
                        $list = $this->InstitutionTypes
                            ->getList()
                            ->toArray();
                    } else {
                        $institutionTypeIds = explode(",", $entity->value);
                        if (!empty($institutionTypeIds)) {
                            $list = $this->InstitutionTypes
                                ->getList()
                                ->where([
                                    $this->InstitutionTypes->aliasField('id IN ') => $institutionTypeIds
                                ])
                                ->toArray();
                        }
                    }

                    if (!empty($list)) {
                        $value = implode(", ", $list);
                    }
                    break;

                case 'restrict_staff_transfer_by_sector':
                    $valueOptions = $this->getSelectOptions('general.yesno');
                   
                    $value = array_key_exists($entity->value, $valueOptions) ? $valueOptions[$entity->value] : '';
                    break;

                default:
                    break;
            }
        }

        return $value;
    }

    public function onGetDefaultValue(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('code')) {
            switch ($entity->code) {
                case 'enable_staff_transfer':
                    if ($entity->default_value == self::SELECT_ALL_INSTITUTION_TYPES) {
                        $list = $this->InstitutionTypes->getList()->toArray();
                        $value = implode(", ", $list);
                    }

                    break;

                case 'restrict_staff_transfer_by_sector':
                    $defaultValueOptions = $this->getSelectOptions('general.yesno');
                    $value = array_key_exists($entity->default_value, $defaultValueOptions) ? $defaultValueOptions[$entity->default_value] : '';
                    break;

                default:
                    break;
            }
        }

        return $value;
    }

    public function onUpdateFieldInstitutionTypeSelection(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        if ($entity->has('code')) {
            switch ($entity->code) {
                case 'enable_staff_transfer':
                    if ($action == 'edit') {
                        $institutionTypeSelectionOptions = $this->getSelectOptions('StaffTransfers.institution_type_selection');

                        $attr['type'] = 'select';
                        $attr['options'] = $institutionTypeSelectionOptions;
                        $attr['select'] = false;
                        $attr['onChangeReload'] = true;
                    } else {
                        $attr['visible'] = false;
                    }
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

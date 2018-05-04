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
                    if ($entity->value == self::SELECT_ALL_INSTITUTION_TYPES) {
                        $entity->institution_type_selection = self::SELECT_ALL_INSTITUTION_TYPES;
                    } else {
                        $entity->institution_type_selection = self::SELECT_INSTITUTION_TYPES;

                        $institutionTypeIds = explode(',', $entity->value);
                        $institutionTypeResults = $this->InstitutionTypes
                            ->find()
                            ->find('visible')
                            ->find('order')
                            ->where([
                                $this->InstitutionTypes->aliasField('id IN ') => $institutionTypeIds
                            ])
                            ->all();

                        $entity->value = $institutionTypeResults;
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
                            $institutionTypeOptions = $this->InstitutionTypes
                                ->getList()
                                ->toArray();

                            $attr['type'] = 'chosenSelect';
                            $attr['placeholder'] = __('Select Institution Types');
                            $attr['attr'] = ['required' => true];
                            $attr['options'] = $institutionTypeOptions;
                        } elseif ($institutionTypeSelection == self::SELECT_ALL_INSTITUTION_TYPES) {
                            $attr['type'] = 'readonly';
                            $attr['value'] = self::SELECT_ALL_INSTITUTION_TYPES;
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

    public function checkIfTransferEnabled($institutionId = 0)
    {
        $enableStaffTransfer = false;

        $Institutions = TableRegistry::get('Institution.Institutions');
        $institutionTypeId = $Institutions->get($institutionId)->institution_type_id;

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $enableStaffTransferValue = $ConfigItems->value('enable_staff_transfer');
        $enableStaffTransferTypeIds = explode(",", $enableStaffTransferValue);

        if ($enableStaffTransferValue == self::SELECT_ALL_INSTITUTION_TYPES || in_array($institutionTypeId, $enableStaffTransferTypeIds)) {
            $enableStaffTransfer = true;
        }

        return $enableStaffTransfer;
    }

    public function checkDifferentSectorTransferRestricted($institutionId = 0, $compareInstitutionId = 0)
    {
        $isRestricted = false;

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $restrictStaffTransferBySector = $ConfigItems->value('restrict_staff_transfer_by_sector');

        $ConfigStaffTransfersTable = TableRegistry::get('Configuration.ConfigStaffTransfers');
        $sameSector = $ConfigStaffTransfersTable->compareInstitutionSector($institutionId, $compareInstitutionId);

        // restrict staff transfer by sector is set to true and incoming & outgoing institution are not same sector
        if ($restrictStaffTransferBySector && !$sameSector) {
            $isRestricted = true;
        }

        return $isRestricted;
    }

    public function compareInstitutionSector($institutionId = 0, $compareInstitutionId = 0)
    {
        $Institutions = TableRegistry::get('Institution.Institutions');
        $sectorId = $Institutions->get($institutionId)->institution_sector_id;
        $compareSectorId = $Institutions->get($compareInstitutionId)->institution_sector_id;

        return $sectorId == $compareSectorId;
    }
}

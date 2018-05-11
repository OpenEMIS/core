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
    CONST SELECT_INSTITUTION_SECTORS = 1;
    CONST SELECT_ALL_INSTITUTION_SECTORS = '-1';

    public function initialize(array $config)
    {
        $this->table('config_items');
        parent::initialize($config);
 
        $this->addBehavior('Configuration.ConfigItems');

        $this->toggle('add', false);
        $this->toggle('remove', false);

        $this->InstitutionTypes = TableRegistry::get('Institution.Types');
        $this->InstitutionSectors = TableRegistry::get('Institution.Sectors');
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $submit = isset($data['submit']) ? $data['submit'] : 'save';
        if ($submit == 'save') {
            if ($data['code'] == 'enable_staff_transfer_by_types') {
                if (isset($data['value_selection']) && $data['value_selection'] == self::SELECT_INSTITUTION_TYPES) {
                    if (isset($data['value']['_ids']) && !empty($data['value']['_ids'])) {
                        $institutionTypeIds = $data['value']['_ids'];
                        $data['value'] = implode(",", $institutionTypeIds);
                    } else {
                        $data['value'] = '';
                    }
                }
            } elseif ($data['code'] == 'enable_staff_transfer_by_sectors') {
                if (isset($data['value_selection']) && $data['value_selection'] == self::SELECT_INSTITUTION_SECTORS) {
                    if (isset($data['value']['_ids']) && !empty($data['value']['_ids'])) {
                        $sectorTypeIds = $data['value']['_ids'];
                        $data['value'] = implode(",", $sectorTypeIds);
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
                case 'enable_staff_transfer_by_types':
                    if ($entity->value == self::SELECT_ALL_INSTITUTION_TYPES) {
                        $entity->value_selection = self::SELECT_ALL_INSTITUTION_TYPES;
                    } else {
                        $entity->value_selection = self::SELECT_INSTITUTION_TYPES;

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

                case 'enable_staff_transfer_by_sectors':
                    if ($entity->value == self::SELECT_ALL_INSTITUTION_SECTORS) {
                        $entity->value_selection = self::SELECT_ALL_INSTITUTION_SECTORS;
                    } else {
                        $entity->value_selection = self::SELECT_INSTITUTION_SECTORS;

                        $sectorTypeIds = explode(',', $entity->value);
                        $sectorTypeResults = $this->InstitutionSectors
                            ->find()
                            ->find('visible')
                            ->find('order')
                            ->where([
                                $this->InstitutionSectors->aliasField('id IN ') => $sectorTypeIds
                            ])
                            ->all();

                        $entity->value = $sectorTypeResults;
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
                case 'enable_staff_transfer_by_types':
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

                case 'enable_staff_transfer_by_sectors':
                    $list = [];
                    if ($entity->value == self::SELECT_ALL_INSTITUTION_SECTORS) {
                        $list = $this->InstitutionSectors
                            ->getList()
                            ->toArray();
                    } else {
                        $institutionSectorIds = explode(",", $entity->value);
                        if (!empty($institutionSectorIds)) {
                            $list = $this->InstitutionSectors
                                ->getList()
                                ->where([
                                    $this->InstitutionSectors->aliasField('id IN ') => $institutionSectorIds
                                ])
                                ->toArray();
                        }
                    }

                    if (!empty($list)) {
                        $value = implode(", ", $list);
                    }
                    break;

                case 'restrict_staff_transfer_by_type':
                case 'restrict_staff_transfer_by_provider':
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
                case 'enable_staff_transfer_by_types':
                    if ($entity->default_value == self::SELECT_ALL_INSTITUTION_TYPES) {
                        $list = $this->InstitutionTypes->getList()->toArray();
                        $value = implode(", ", $list);
                    }

                    break;

                case 'enable_staff_transfer_by_sectors':
                    if ($entity->default_value == self::SELECT_ALL_INSTITUTION_SECTORS) {
                        $list = $this->InstitutionSectors->getList()->toArray();
                        $value = implode(", ", $list);
                    }

                    break;

                case 'restrict_staff_transfer_by_type':
                case 'restrict_staff_transfer_by_provider':
                    $defaultValueOptions = $this->getSelectOptions('general.yesno');
                    $value = array_key_exists($entity->default_value, $defaultValueOptions) ? $defaultValueOptions[$entity->default_value] : '';
                    break;

                default:
                    break;
            }
        }

        return $value;
    }

    public function onUpdateFieldValueSelection(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        if ($entity->has('code')) {
            switch ($entity->code) {
                case 'enable_staff_transfer_by_types':
                    if ($action == 'edit') {
                        $institutionTypeSelectionOptions = $this->getSelectOptions('StaffTransfers.institution_type_selection');

                        $attr['type'] = 'select';
                        $attr['options'] = $institutionTypeSelectionOptions;
                        $attr['select'] = false;
                        $attr['onChangeReload'] = true;
                    } else {
                        $attr['visible'] = false;
                    }

                    $attr['attr']['label'] = __('Institution Type Selection');
                    break;

                case 'enable_staff_transfer_by_sectors':
                    if ($action == 'edit') {
                        $institutionSectorSelectionOptions = $this->getSelectOptions('StaffTransfers.institution_sector_selection');

                        $attr['type'] = 'select';
                        $attr['options'] = $institutionSectorSelectionOptions;
                        $attr['select'] = false;
                        $attr['onChangeReload'] = true;
                    } else {
                        $attr['visible'] = false;
                    }

                    $attr['attr']['label'] = __('Institution Sector Selection');
                    break;

                case 'restrict_staff_transfer_by_type':
                case 'restrict_staff_transfer_by_provider':
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
                case 'enable_staff_transfer_by_types':
                    if ($entity->has('value_selection')) {
                        $institutionTypeSelection = $entity->value_selection;

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

                case 'enable_staff_transfer_by_sectors':
                    if ($entity->has('value_selection')) {
                        $institutionSectorSelection = $entity->value_selection;

                        if ($institutionSectorSelection == self::SELECT_INSTITUTION_SECTORS) {
                            $institutionSectorOptions = $this->InstitutionSectors
                                ->getList()
                                ->toArray();

                            $attr['type'] = 'chosenSelect';
                            $attr['placeholder'] = __('Select Institution Sectors');
                            $attr['attr'] = ['required' => true];
                            $attr['options'] = $institutionSectorOptions;
                        } elseif ($institutionSectorSelection == self::SELECT_ALL_INSTITUTION_SECTORS) {
                            $attr['type'] = 'readonly';
                            $attr['value'] = self::SELECT_ALL_INSTITUTION_SECTORS;
                            $attr['attr']['value'] = __('All Institution Sectors Selected');
                        }

                        $attr['attr']['label'] = __('Institution Sectors');
                    }
                    break;

                case 'restrict_staff_transfer_by_type':
                case 'restrict_staff_transfer_by_provider':
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
        $this->field('value_selection', [
            'entity' => $entity
        ]);
        $this->field('value', [
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'type', 'label', 'value_selection', 'value', 'default_value'
        ]);
    }

    public function checkIfTransferEnabled($institutionId = 0)
    {
        // true: enabled Staff Transfer (default)
        // false: disabled Staff Transfer

        $Institutions = TableRegistry::get('Institution.Institutions');
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');

        // enable_staff_transfer_by_types
        $institutionTypeId = $Institutions->get($institutionId)->institution_type_id;
        $enableStaffTransferByTypes = $ConfigItems->value('enable_staff_transfer_by_types');
        $enableStaffTransferTypeIds = explode(",", $enableStaffTransferByTypes);
        if ($enableStaffTransferByTypes != self::SELECT_ALL_INSTITUTION_TYPES && !in_array($institutionTypeId, $enableStaffTransferTypeIds)) {
            return false;
        }
        // end

        // enable_staff_transfer_by_sectors
        $institutionSectorId = $Institutions->get($institutionId)->institution_sector_id;
        $enableStaffTransferBySectors = $ConfigItems->value('enable_staff_transfer_by_sectors');
        $enableStaffTransferSectorIds = explode(",", $enableStaffTransferBySectors);
        if ($enableStaffTransferBySectors != self::SELECT_ALL_INSTITUTION_SECTORS && !in_array($institutionSectorId, $enableStaffTransferSectorIds)) {
            return false;
        }
        // end

        return true;
    }

    public function checkStaffTransferRestricted($institutionId = 0, $compareInstitutionId = 0)
    {
        $isRestricted = false;
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');

        $restrictStaffTransferByType = $ConfigItems->value('restrict_staff_transfer_by_type');
        if ($restrictStaffTransferByType) {
            $sameType = $this->compareInstitutionType($institutionId, $compareInstitutionId);

            if (!$sameType) {
                $isRestricted = true;
            }
        }

        $restrictStaffTransferByProvider = $ConfigItems->value('restrict_staff_transfer_by_provider');
        if ($restrictStaffTransferByProvider) {
            $sameProvider = $this->compareInstitutionProvider($institutionId, $compareInstitutionId);

            if (!$sameProvider) {
                $isRestricted = true;
            }
        }

        return $isRestricted;
    }

    public function compareInstitutionType($institutionId = 0, $compareInstitutionId = 0)
    {
        $Institutions = TableRegistry::get('Institution.Institutions');
        $institutionTypeId = $Institutions->get($institutionId)->institution_type_id;
        $compareInstitutionTypeId = $Institutions->get($compareInstitutionId)->institution_type_id;

        return $institutionTypeId == $compareInstitutionTypeId;
    }
    
    public function compareInstitutionProvider($institutionId = 0, $compareInstitutionId = 0)
    {
        $Institutions = TableRegistry::get('Institution.Institutions');
        $institutionProviderId = $Institutions->get($institutionId)->institution_provider_id;
        $compareInstitutionProviderId = $Institutions->get($compareInstitutionId)->institution_provider_id;

        return $institutionProviderId == $compareInstitutionProviderId;
    }
}

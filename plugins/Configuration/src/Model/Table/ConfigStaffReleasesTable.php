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

class ConfigStaffReleasesTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST STAFF_RELEASE_BY_TYPES = 'staff_release_by_types';
    CONST STAFF_RELEASE_BY_SECTORS = 'staff_release_by_sectors';
    CONST RESTRICT_STAFF_RELEASE_BETWEEN_SAME_TYPE = 'restrict_staff_release_between_same_type';
    CONST RESTRICT_STAFF_RELEASE_BETWEEN_DIFFERENT_PROVIDER = 'restrict_staff_release_between_different_provider';
    CONST SELECTION_DISABLE = "0";

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
            $arrayToJson = [];
            if ($data['code'] === self::STAFF_RELEASE_BY_TYPES || $data['code'] === self::STAFF_RELEASE_BY_SECTORS) {
                if (isset($data['value_selection']) && $data['value_selection'] !== self::SELECTION_DISABLE) {
                    if (isset($data['value']['_ids']) && !empty($data['value']['_ids'])) {
                        $idValues = $data['value']['_ids'];
                        $arrayToJson['selection'] = $data['value_selection'];
                        $arrayToJson['values'] = implode(",", $idValues);
                        $encodedValues = json_encode($arrayToJson, JSON_UNESCAPED_UNICODE);
                        $data['value'] = $encodedValues;
                    }
                } else {
                    $arrayToJson['selection'] = $data['value_selection'];
                    $encodedValues = json_encode($arrayToJson, JSON_UNESCAPED_UNICODE);
                    $data['value'] = $encodedValues;
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
        $query->where([$this->aliasField('type') => 'Staff Releases']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->has('code')) {
            switch ($entity->code) {
                case self::STAFF_RELEASE_BY_TYPES:
                    if (!empty($entity->value)) {
                        $jsonData = stripslashes(html_entity_decode($entity->value));
                        $valuesData = json_decode($jsonData,true);

                        if (array_key_exists('selection', $valuesData)) {
                            $entity->value_selection = $valuesData['selection'];
                            $entity->value = '';
                            if (array_key_exists('values', $valuesData)) {
                                $institutionTypesIds = explode(",", $valuesData['values']);
                                $entity->value = $this->getInstitutionTypes($institutionTypesIds);
                            }
                        }
                    } else if(!empty($entity->default_value)){
                        $jsonData = stripslashes(html_entity_decode($entity->default_value));
                        $valuesData = json_decode($jsonData,true);

                        $entity->value_selection = $valuesData['selection'];
                        $entity->value = '';
                        if (array_key_exists('values', $valuesData)) {
                            $institutionTypesIds = explode(",", $valuesData['values']);
                            $entity->value = $this->getInstitutionTypes($institutionTypesIds);
                        }
                    }
                    break;
                case self::STAFF_RELEASE_BY_SECTORS:
                    if (!empty($entity->value)) {
                        $jsonData = stripslashes(html_entity_decode($entity->value));
                        $valuesData = json_decode($jsonData,true);

                        if (array_key_exists('selection', $valuesData)) {
                            $entity->value_selection = $valuesData['selection'];
                            $entity->value = '';
                            if (array_key_exists('values', $valuesData)) {
                                $institutionSectorsIds = explode(",", $valuesData['values']);
                                $entity->value = $this->getInstitutionSectors($institutionSectorsIds);
                            }
                        }
                    } else if(!empty($entity->default_value)){
                        $jsonData = stripslashes(html_entity_decode($entity->default_value));
                        $valuesData = json_decode($jsonData,true);

                        $entity->value_selection = $valuesData['selection'];
                        $entity->value = '';
                        if (array_key_exists('values', $valuesData)) {
                            $institutionSectorsIds = explode(",", $valuesData['values']);
                            $entity->value = $this->getInstitutionSectors($institutionSectorsIds);
                        }
                    }
                    break;
                default:
                    break;
            }
        }
    }

    public function getInstitutionTypes($institutionTypesIds)
    {
        $institutionTypeResults = $this->InstitutionTypes
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->InstitutionTypes->aliasField('id IN ') => $institutionTypesIds
            ])
            ->all();

        return $institutionTypeResults;
    }
    public function getInstitutionSectors($institutionSectorsIds)
    {
        $institutionsSectorResults = $this->InstitutionSectors
            ->find()
            ->find('visible')
            ->find('order')
            ->where([
                $this->InstitutionSectors->aliasField('id IN ') => $institutionSectorsIds
            ])
            ->all();

        return $institutionsSectorResults;
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onGetValue(Event $event, Entity $entity)
    {
        $value = " ";
        $list = [];
        if ($entity->has('code')) {
            switch ($entity->code) {
                case self::STAFF_RELEASE_BY_TYPES:
                    if (!empty($entity->value)) {
                        $jsonData = stripslashes(html_entity_decode($entity->value));
                        $valuesData = json_decode($jsonData,true);
                        if (array_key_exists('selection', $valuesData)) {
                            if ($valuesData['selection'] !== self::SELECTION_DISABLE) {
                                if (array_key_exists('values', $valuesData)) {
                                    $institutionTypeIds = explode(",", $valuesData['values']);
                                    $list = $this->InstitutionTypes
                                        ->getList()
                                        ->where([
                                            $this->InstitutionTypes->aliasField('id IN ') => $institutionTypeIds
                                        ])
                                        ->toArray();
                                }
                            }
                        }
                    }
                    break;
                case self::STAFF_RELEASE_BY_SECTORS:
                    if (!empty($entity->value)) {
                        $jsonData = stripslashes(html_entity_decode($entity->value));
                        $valuesData = json_decode($jsonData,true);
                        if (array_key_exists('selection', $valuesData)) {
                            if ($valuesData['selection'] !== self::SELECTION_DISABLE) {
                                if (array_key_exists('values', $valuesData)) {
                                    $institutionSectorsIds = explode(",", $valuesData['values']);
                                    $list = $this->InstitutionSectors
                                        ->getList()
                                        ->where([
                                            $this->InstitutionSectors->aliasField('id IN ') => $institutionSectorsIds
                                        ])
                                        ->toArray();
                                }
                            }
                        }
                    }
                    break;
                case self::RESTRICT_STAFF_RELEASE_BETWEEN_SAME_TYPE:
                    $valueOptions = $this->getSelectOptions('general.yesno');
                    $value = array_key_exists($entity->value, $valueOptions) ? $valueOptions[$entity->value] : '';
                    break;
                case self::RESTRICT_STAFF_RELEASE_BETWEEN_DIFFERENT_PROVIDER:
                    $valueOptions = $this->getSelectOptions('general.yesno');
                    $value = array_key_exists($entity->value, $valueOptions) ? $valueOptions[$entity->value] : '';
                    break;
                default:
                    break;
            }
        }

        if (!empty($list)) {
            $value = implode(", ", $list);
        }
        return $value;
    }

    public function onGetDefaultValue(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('code')) {
                switch ($entity->code) {
                    case self::STAFF_RELEASE_BY_TYPES:
                    case self::STAFF_RELEASE_BY_SECTORS:
                        $jsonData = stripslashes(html_entity_decode($entity->default_value));
                        $valuesData = json_decode($jsonData,true);

                        // Assign default valut to be empty for display purpose if its selection disable
                        if (array_key_exists('selection', $valuesData)) {
                            if ($valuesData['selection'] == self::SELECTION_DISABLE) {
                                $entity->default_value = " ";
                            }
                            $entity->value_selection = $valuesData['selection'];
                        }
                        break;
                    case self::RESTRICT_STAFF_RELEASE_BETWEEN_SAME_TYPE:
                        $defaultValueOptions = $this->getSelectOptions('general.yesno');
                        $value = array_key_exists($entity->default_value, $defaultValueOptions) ? $defaultValueOptions[$entity->default_value] : '';
                        break;
                    case self::RESTRICT_STAFF_RELEASE_BETWEEN_DIFFERENT_PROVIDER:
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

        $selectionOptions = ['Disable Selection', 'Enable Selection'];

        if ($entity->has('code')) {
                switch ($entity->code) {
                    case self::STAFF_RELEASE_BY_TYPES:
                    case self::STAFF_RELEASE_BY_SECTORS:
                        if ($action == 'edit') {
                            $attr['type'] = 'select';
                            $attr['options'] = $selectionOptions;
                            $attr['select'] = false;
                            $attr['onChangeReload'] = true;
                            $attr['attr']['label'] = __('Institution Type');
                        } else {
                            $attr['visible'] = false;
                        }
                        break;
                    case self::RESTRICT_STAFF_RELEASE_BETWEEN_SAME_TYPE:
                        $attr['visible'] = false;
                        break;
                    case self::RESTRICT_STAFF_RELEASE_BETWEEN_DIFFERENT_PROVIDER:
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

        if($action == 'edit') {
            if ($entity->has('code')) {
                switch ($entity->code) {
                    case self::STAFF_RELEASE_BY_TYPES:
                        if ($entity->has('value_selection')) {
                            if ($entity->value_selection == self::SELECTION_DISABLE) {
                                $attr['type'] = 'readonly';
                                $attr['value'] = "";
                                $attr['attr'] = ['required' => false];
                                $attr['options'] = NULL;
                            } else {
                                $options = $this->InstitutionTypes
                                    ->getList()
                                    ->toArray();

                                $attr['type'] = 'chosenSelect';
                                $attr['attr'] = ['required' => true];
                                $attr['placeholder'] = __('Select Institution Types');
                                $attr['options'] = $options;
                            }
                        }
                        break;
                    case self::STAFF_RELEASE_BY_SECTORS:
                        if ($entity->has('value_selection')) {
                            if ($entity->value_selection == self::SELECTION_DISABLE) {
                                $attr['type'] = 'readonly';
                                $attr['value'] = "";
                                $attr['attr'] = ['required' => false];
                                $attr['options'] = NULL;
                            } else {
                                $options = $this->InstitutionSectors
                                    ->getList()
                                    ->toArray();

                                $attr['type'] = 'chosenSelect';
                                $attr['attr'] = ['required' => true];
                                $attr['placeholder'] = __('Select Institution Sectors');
                                $attr['options'] = $options;
                            }
                        }
                        break;
                    case self::RESTRICT_STAFF_RELEASE_BETWEEN_SAME_TYPE:
                        $valueOptions = $this->getSelectOptions('general.yesno');
                        $attr['type'] = 'select';
                        $attr['options'] = $valueOptions;
                        break;
                    case self::RESTRICT_STAFF_RELEASE_BETWEEN_DIFFERENT_PROVIDER:
                        $valueOptions = $this->getSelectOptions('general.yesno');
                        $attr['type'] = 'select';
                        $attr['options'] = $valueOptions;
                        break;
                    default:
                        break;
            }
        }
    }
        $attr['attr']['label'] = __('Value');
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

    public function checkIfReleaseEnabled($institutionId = 0)
    {
        $Institutions = TableRegistry::get('Institution.Institutions');
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');

        $isEnable = false;

        // StaffReleaseByTypes
        $institutionTypeId = $Institutions->get($institutionId)->institution_type_id;
        $staffReleaseByTypesJsonString = $ConfigItems->value('staff_release_by_types');
        $staffReleaseByTypesJsonData = stripslashes(html_entity_decode($staffReleaseByTypesJsonString));
        $staffReleaseByTypesValues = json_decode($staffReleaseByTypesJsonData,true);;

        if (array_key_exists('selection', $staffReleaseByTypesValues)) {
            if ($staffReleaseByTypesValues['selection'] !== self::SELECTION_DISABLE) {
                if (array_key_exists('values', $staffReleaseByTypesValues)) {
                    $enableStaffReleasesByTypeIds = explode(",", $staffReleaseByTypesValues['values']);
                    if (in_array($institutionTypeId, $enableStaffReleasesByTypeIds)) {
                        $isEnable = true;
                    }
                }
            }
        }

        // StaffReleaseBySector
        $institutionSectorId = $Institutions->get($institutionId)->institution_sector_id;
        $staffReleaseBySectorsJsonString = $ConfigItems->value('staff_release_by_sectors');
        $staffReleaseBySectorsJsonData = stripslashes(html_entity_decode($staffReleaseBySectorsJsonString));
        $staffReleaseBySectorsValues = json_decode($staffReleaseBySectorsJsonData,true);

        if (array_key_exists('selection', $staffReleaseBySectorsValues)) {
            if ($staffReleaseBySectorsValues['selection'] !== self::SELECTION_DISABLE) {
                if (array_key_exists('values', $staffReleaseBySectorsValues)) {
                    $enableStaffReleasesBySectorsIds = explode(",", $staffReleaseBySectorsValues['values']);
                    if (in_array($institutionSectorId, $enableStaffReleasesBySectorsIds)) {
                        $isEnable = true;
                    } else {
                        $isEnable = false;
                    }
                }
            }
        }
        return $isEnable;
    }

    public function checkStaffReleaseRestrictedBetweenSameType($institutionId = 0, $compareInstitutionId = 0)
    {
        $isRestricted = false;
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $restrictStaffReleaseBetweenSameType = $ConfigItems->value('restrict_staff_release_between_same_type');

        if ($restrictStaffReleaseBetweenSameType) {
            $sameType = $this->compareInstitutionType($institutionId, $compareInstitutionId);

            if (!$sameType) {
                $isRestricted = true;
            }
        }
        return $isRestricted;
    }
    
    public function checkStaffReleaseRestrictedBetweenDifferentProvider($institutionId = 0, $compareInstitutionId = 0)
    {
        $isRestricted = false;
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $restrictStaffReleaseBetweenDifferentProvider = $ConfigItems->value('restrict_staff_release_between_different_provider');

        if ($restrictStaffReleaseBetweenDifferentProvider) {
            $differentType = $this->compareInstitutionDifferentType($institutionId, $compareInstitutionId);

            if (!$differentType) {
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
    
    public function compareInstitutionDifferentType($institutionId = 0, $compareInstitutionId = 0)
    {
        $Institutions = TableRegistry::get('Institution.Institutions');
        $institutionTypeId = $Institutions->get($institutionId)->institution_type_id;
        $compareInstitutionTypeId = $Institutions->get($compareInstitutionId)->institution_type_id;

        return $institutionTypeId != $compareInstitutionTypeId;
    }

}

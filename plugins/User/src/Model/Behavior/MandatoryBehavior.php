<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\RulesChecker;
use Cake\Utility\Inflector;

class MandatoryBehavior extends Behavior
{
    protected $_userRole;
    protected $_info;
    protected $_roleFields;
    protected $_currentNationality;

    public function initialize(array $config)
    {
        $this->_userRole = (array_key_exists('userRole', $config))? $config['userRole']: null;
        $this->_roleFields = (array_key_exists('roleFields', $config))? $config['roleFields']: [];
        if (is_null($this->_userRole)) {
            die('userRole must be set in mandatory behavior');
        }

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');

        $this->_info = [];
        foreach ($this->_roleFields as $key => $value) {
            $currModelName = $this->_userRole.$value;
            $this->_info[$value] = $this->getOptionValue($currModelName);
        }

        $this->_table->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->_table->hasMany('Nationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->_table->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'ControllerAction.Model.add.onInitialize' => 'addOnInitialize',
            'ControllerAction.Model.addEdit.beforePatch' => 'addEditBeforePatch',
            'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
            'ControllerAction.Model.add.onChangeNationality' => 'addOnChangeNationality',
            'ControllerAction.Model.onUpdateFieldContactType' => 'onUpdateFieldContactType',
            'ControllerAction.Model.onUpdateFieldContactValue' => 'onUpdateFieldContactValue',
            'ControllerAction.Model.onUpdateFieldNationality' => 'onUpdateFieldNationality',
            'ControllerAction.Model.onUpdateFieldIdentityType' => 'onUpdateFieldIdentityType',
            'ControllerAction.Model.onUpdateFieldIdentityNumber' => 'onUpdateFieldIdentityNumber'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function getOptionValue($name)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $data = $ConfigItems
            ->find()
            ->where([$ConfigItems->aliasField('code') => $name])
            ->first()
        ;

        $optionType = $data->option_type;
        $value = $data->value;

        $ConfigItemOptions = TableRegistry::get('Configuration.ConfigItemOptions');
        $result = $ConfigItemOptions
            ->find()
            ->where([$ConfigItemOptions->aliasField('option_type') => $optionType, $ConfigItemOptions->aliasField('value') => $value])
            ->first();
        return $result->option;
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        if (array_key_exists('Identities', $this->_info) && $this->_info['Identities'] != 'Excluded') {
            $Nationalities = TableRegistry::get('FieldOption.Nationalities');
            $defaultNationality = $Nationalities->find()
                ->where([$Nationalities->aliasField('default') => 1])
                ->first();

            $defaultIdentityType = '';
            if (!empty($defaultNationality)) {
                // if default nationality can be found
                $this->_table->fields['nationality']['default'] = $defaultNationality->id;
                $defaultIdentityType = $defaultNationality->identity_type_id;
            }

            if (empty($defaultIdentityType)) {
                $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
                $defaultIdentityTypeEntity = $IdentityTypes->find()
                    ->where([$IdentityTypes->aliasField('default') => 1])
                    ->first();
                if (!empty($defaultIdentityTypeEntity)) {
                    $defaultIdentityType = $defaultIdentityTypeEntity->id;
                }
            }

            if (!empty($defaultIdentityType)) {
                $this->_table->fields['identity_type']['default'] = $defaultIdentityType;
            }
        }

        return $entity;
    }

    public function addBeforeAction(Event $event)
    {
        // mandatory associated fields
        $i = 30;
        if (array_key_exists('Contacts', $this->_info) && $this->_info['Contacts'] != 'Excluded') {
            $this->_table->field('contact_type', ['order' => $i++]);
            $this->_table->field('contact_value', ['order' => $i++]);
        }

        if (array_key_exists('Nationalities', $this->_info) && $this->_info['Nationalities'] != 'Excluded') {
            $this->_table->field('nationality', ['order' => $i++]);
        }

        if (array_key_exists('Identities', $this->_info) && $this->_info['Identities'] != 'Excluded') {
            $this->_table->field('identity_type', ['order' => $i++]);
            $this->_table->field('identity_number', ['order' => $i++]);
        } else {
            $this->_table->field('identity_number', ['visible' => false]);
        }

        // need to set the handling for non-mandatory require = false here
        foreach ($this->_info as $key => $value) {
            if ($value == 'Non-Mandatory') {
                // need to set the relevant non-mandatory fields and set it to required = false to remove *
                $singularAndLowerKey = strtolower(Inflector::singularize(Inflector::tableize($key)));
                $model = $this->_table;
                foreach ($model->fields as $fkey => $fvalue) {
                    if (strpos($fkey, $singularAndLowerKey)!==false) {
                        $model->fields[$fkey]['attr']['required'] = false;
                    }
                }
            }
        }
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($this->_table->action == 'add') {
            $newOptions = [];

            $newOptions['associated'] = ['Identities', 'Nationalities', 'Contacts'];

            foreach ($this->_info as $key => $value) {
                if ($value == 'Non-Mandatory') {
                    $newOptions['associated'][$key] = ['validate' => 'NonMandatory'];
                } else {
                    if ($value != 'Excluded') {
                        $newOptions['associated'][] = $key;
                    }
                }

                $tableName = Inflector::tableize($key);

                if (array_key_exists($tableName, $data[$this->_table->alias()])) { //entire form data

                    if (array_key_exists(0, $data[$this->_table->alias()][$tableName])) { //data per form element

                        // going to check all fields.. if something is empty(form fill incomplete).. the data will not be removed and not saved
                        $incompleteField = false;

                        foreach ($data[$this->_table->alias()][$tableName][0] as $ckey => $check) { //value each form element

                            // done for controller v4 for add saving by association 'security_user_id' is pre-set and replaced by cake later with the correct id

                            if (in_array($key, ['Nationalities', 'Contacts'])) {
                                if (array_key_exists($tableName, $data[$this->_table->alias()])) {
                                    foreach ($data[$this->_table->alias()][$tableName] as $tkey => $tvalue) {
                                        // logic to get contact_option_id from contact_type_id as contact_option_id is set as requirePresence, otherwise will have validation error
                                        if ($key == 'Contacts' && $ckey == 'contact_type_id') {
                                            $contactTypeId = $check;
                                            if (!empty($contactTypeId)) {
                                                $ContactTypes = TableRegistry::get('User.ContactTypes');
                                                $contactOptionId = $ContactTypes->get($contactTypeId)->contact_option_id;
                                                $data[$this->_table->alias()][$tableName][$tkey]['contact_option_id'] = $contactOptionId;
                                            }
                                        }
                                        // End
                                        $data[$this->_table->alias()][$tableName][$tkey]['security_user_id'] = '0';
                                    }
                                }
                            }

                            // also need to remove the data if the field is empty
                            if ($value == 'Non-Mandatory') {
                                if (empty($check)) {
                                    $incompleteField = true;
                                }

                                if ($incompleteField) {
                                    unset($data[$this->_table->alias()][$tableName]);
                                }
                            }
                        }
                    }
                }
            }

            $arrayOptions = $options->getArrayCopy();
            $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
            $options->exchangeArray($arrayOptions);
        }
    }

    public function addOnChangeNationality(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $Nationalities = TableRegistry::get('FieldOption.Nationalities');
        $nationalityId = $data[$this->_table->alias()]['nationalities'][0]['nationality_id'];
        $nationality = $Nationalities->findById($nationalityId)->first();
        $defaultIdentityType = (!empty($nationality))? $nationality->identity_type_id: null;
        if (empty($defaultIdentityType)) {
            $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
            $defaultIdentityType = $IdentityTypes->find()
                ->where([$IdentityTypes->aliasField('default') => 1])
                ->first();
            $defaultIdentityType = (!empty($defaultIdentityType))? $defaultIdentityType->id: null;
        }

        $this->_table->fields['nationality']['default'] = $data[$this->_table->alias()]['nationalities'][0]['nationality_id'];

        // overriding the  previous input to put in default identities
        if (isset($this->_table->fields['identity_type'])) {
            $this->_table->fields['identity_type']['default'] = $defaultIdentityType;
        }
        $data[$this->_table->alias()]['identities'][0]['identity_type_id'] = $defaultIdentityType;

        $options['associated'] = [
            'InstitutionStudents' => ['validate' => false],
            'InstitutionStaff' => ['validate' => false],
            'Identities' => ['validate' => false],
            'UserNationalities' => ['validate' => false],
            'Contacts' => ['validate' => false]
        ];
    }

    public function onUpdateFieldContactType(Event $event, array $attr, $action, $request)
    {
        if (!empty($this->_info)) {
            if (array_key_exists('Contacts', $this->_info)) {
                $attr['empty'] = 'Select';
            }
        }

        $contactOptions = TableRegistry::get('User.ContactTypes')
            ->find('list', ['keyField' => 'id', 'valueField' => 'full_contact_type_name'])
            ->find('withContactOptions')
            ->order([
                'ContactOptions.order',
                'ContactTypes.order'
            ])
            ->toArray();
            
        $attr['type'] = 'select';
        $attr['fieldName'] = $this->_table->alias().'.contacts.0.contact_type_id';
        $attr['options'] = $contactOptions;

        return $attr;
    }

    public function onUpdateFieldContactValue(Event $event, array $attr, $action, $request)
    {
        $attr['type'] = 'string';
        $attr['fieldName'] = $this->_table->alias().'.contacts.0.value';

        return $attr;
    }

    public function onUpdateFieldNationality(Event $event, array $attr, $action, $request)
    {
        if (!empty($this->_info)) {
            if (array_key_exists('Nationalities', $this->_info)) {
                $attr['empty'] = 'Select';
            }
        }

        $Nationalities = TableRegistry::get('FieldOption.Nationalities');
        $nationalityOptions = $Nationalities->getList()->toArray();

        $attr['type'] = 'select';
        $attr['options'] = $nationalityOptions;
        $attr['onChangeReload'] = 'changeNationality';
        $attr['fieldName'] = $this->_table->alias().'.nationalities.0.nationality_id';
        // default is set in addOnInitialize

        return $attr;
    }

    public function onUpdateFieldIdentityType(Event $event, array $attr, $action, $request)
    {
        if (!empty($this->_info)) {
            if (array_key_exists('Identities', $this->_info)) {
                $attr['empty'] = 'Select';
            }
        }

        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $identityTypeOptions = $IdentityTypes->getList();
        $attr['type'] = 'select';
        $attr['fieldName'] = $this->_table->alias().'.identities.0.identity_type_id';
        $attr['options'] = $identityTypeOptions->toArray();
        // default is set in addOnInitialize

        return $attr;
    }

    public function onUpdateFieldIdentityNumber(Event $event, array $attr, $action, $request)
    {
        $attr['type'] = 'string';
        $attr['fieldName'] = $this->_table->alias().'.identities.0.number';

        return $attr;
    }

    // public function getMandatoryList() {
    //     $list = [0 => __('No'), 1 => __('Yes')];
    //     return $list;
    // }

    // public function getMandatoryVisibility($selectedFieldType) {
    //     $isMandatory = $this->CustomFieldTypes->find('all')->where([$this->CustomFieldTypes->aliasField('code') => $selectedFieldType])->first()->is_mandatory;
    //     return ($isMandatory == 1 ? true : false);
    // }

    // public function onGetIsMandatory(Event $event, Entity $entity) {
    //     $isMandatory = $this->CustomFieldTypes->find('all')->where([$this->CustomFieldTypes->aliasField('code') => $entity->field_type])->first()->is_mandatory;
    //     $is_mandatory = ($isMandatory == 0) ? '<i class="fa fa-minus"></i>' : ($entity->is_mandatory == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>');
    //     return $is_mandatory;
    // }
}

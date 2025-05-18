<?php
namespace StaffCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Http\ServerRequest;

class StaffCustomFormsTable extends CustomFormsTable
{
    private $dataCount = null;

    public function initialize(array $config): void
    {
        $config['extra'] = [
            'fieldClass' => [
                'className' => 'StaffCustomField.StaffCustomFields',
                'joinTable' => 'staff_custom_forms_fields',
                'foreignKey' => 'staff_custom_form_id',
                'targetForeignKey' => 'staff_custom_field_id',
                'through' => 'StaffCustomField.StaffCustomFormsFields',
                'dependent' => true
            ]
        ];
        parent::initialize($config);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        if ($data->count() > 0) {
            if ($extra->offsetExists('toolbarButtons') && $extra['toolbarButtons']['add']) {
                unset($extra['toolbarButtons']['add']);
            }
        }
    }

    public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $module = $this->CustomModules
            ->find()
            ->where([$this->CustomModules->aliasField('code') => 'Staff'])
            ->first();
        $selectedModule = $module->id;
        $request->getQuery['module'] = $selectedModule;

        $attr['type'] = 'readonly';
        $attr['value'] = $selectedModule;
        $attr['attr']['value'] = $module->name;

        return $attr;
    }

    public function getModuleQuery()
    {
        $query = parent::getModuleQuery();
        return $query->where([$this->CustomModules->aliasField('code') => 'Staff']);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'field_type') {
            return __('Field Type');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'is_mandatory') {
            return __('Is Mandatory');
        } elseif ($field == 'is_unique') {
            return __('Is Unique');
        } elseif ($field == 'validation_rule') {
            return __('Validation Rule');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } elseif ($field == 'custom_module_id') {
            return __('Custom Module');
        } elseif ($field == 'staff_custom_field_id') {
            return __('Custom Fields');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeAction(Event $event)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}

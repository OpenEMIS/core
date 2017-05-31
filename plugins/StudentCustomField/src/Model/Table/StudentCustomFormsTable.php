<?php
namespace StudentCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;

class StudentCustomFormsTable extends CustomFormsTable
{
    private $dataCount = null;

    public function initialize(array $config)
    {
        $config['extra'] = [
            'fieldClass' => [
                'className' => 'StudentCustomField.StudentCustomFields',
                'joinTable' => 'student_custom_forms_fields',
                'foreignKey' => 'student_custom_form_id',
                'targetForeignKey' => 'student_custom_field_id',
                'through' => 'StudentCustomField.StudentCustomFormsFields',
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

    public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request)
    {
        $module = $this->CustomModules
            ->find()
            ->where([$this->CustomModules->aliasField('code') => 'Student'])
            ->first();
        $selectedModule = $module->id;
        $request->query['module'] = $selectedModule;

        $attr['type'] = 'readonly';
        $attr['value'] = $selectedModule;
        $attr['attr']['value'] = $module->name;

        return $attr;
    }

    public function getModuleQuery()
    {
        $query = parent::getModuleQuery();
        return $query->where([$this->CustomModules->aliasField('code') => 'Student']);
    }
}

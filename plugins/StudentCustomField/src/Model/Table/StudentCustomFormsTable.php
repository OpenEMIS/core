<?php
namespace StudentCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;//POCOR-8434

class StudentCustomFormsTable extends CustomFormsTable
{
    private $dataCount = null;

    public function initialize(array $config): void
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

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        //POCOR-8434 starts
        $studentRegisteration = false;
        if($this->request->getQuery('module')){
            $CustomModulesTable = TableRegistry::getTableLocator()->get('CustomField.CustomModules');
            $module = $CustomModulesTable
                        ->find()
                        ->where([$CustomModulesTable->aliasField('id') => $this->request->getQuery('module')])
                        ->first();
            if($module->code == 'Student > Registrations'){
                $studentRegisteration = true;
            }                        
        }//POCOR-8434 ends
        if (($studentRegisteration == false ) && $data->count() > 0) {
            if ($extra->offsetExists('toolbarButtons') && $extra['toolbarButtons']['add']) {
                unset($extra['toolbarButtons']['add']);
            }
        }
    }

    public function onUpdateFieldCustomModuleId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-8434 starts
        if($request->getQuery('module')){
            $CustomModulesTable = TableRegistry::getTableLocator()->get('CustomField.CustomModules');
            $module = $CustomModulesTable
                        ->find()
                        ->where([$CustomModulesTable->aliasField('id') => $request->getQuery('module')])
                        ->first();                        
        }
        $selectedModule = $module->id;
        $request->getQuery['module'] = $selectedModule;
        $attr['type'] = 'readonly';
        $attr['value'] = $selectedModule;
        $attr['attr']['value'] = $module->name;
        //POCOR-8434 ends
        return $attr;
    }

    public function getModuleQuery()
    {
        //POCOR-8434 starts
        $where = [];
        if($this->request->getQuery('module')){
            $CustomModulesTable = TableRegistry::getTableLocator()->get('CustomField.CustomModules');
            $module = $CustomModulesTable
                        ->find()
                        ->where([$CustomModulesTable->aliasField('id') => $this->request->getQuery('module')])
                        ->first();  
            $where = [$this->CustomModules->aliasField('code IN') => ['Student', 'Student > Registrations']];
        }else{
            $where = [$this->CustomModules->aliasField('code IN') => ['Student', 'Student > Registrations']];
        }
        $query = parent::getModuleQuery();
        return $query->where($where);//POCOR-8434 ends
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'custom_module_id') {
            return __('Custom Module');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'apply_to_all') {
            return __('Apply To All');
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
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}

<?php
namespace InstitutionCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;//POCOR-8538 
use Cake\ORM\ResultSet;//POCOR-8538 

class InstitutionCustomFormsTable extends CustomFormsTable {
	public function initialize(array $config): void {
		$config['extra'] = [
			'fieldClass' => [
				'className' => 'InstitutionCustomField.InstitutionCustomFields',
				'joinTable' => 'institution_custom_forms_fields',
				'foreignKey' => 'institution_custom_form_id',
				'targetForeignKey' => 'institution_custom_field_id',
				'through' => 'InstitutionCustomField.InstitutionCustomFormsFields',
				'dependent' => true
			],
			'filterClass' => [
				'className' => 'Institution.Types',
				'joinTable' => 'institution_custom_forms_filters',
				'foreignKey' => 'institution_custom_form_id',
				'targetForeignKey' => 'institution_custom_filter_id',
				'through' => 'InstitutionCustomField.InstitutionCustomFormsFilters',
				'dependent' => true
			]
		];
		parent::initialize($config);
	}

	public function onUpdateFieldCustomModuleId(EventInterface $event, array $attr, $action, ServerRequest $request) {
		$selectedModuleId = $this->request->getQuery('module');
		$plugin_name = $this->request->getAttribute('params')['plugin'];
		if($plugin_name == 'InstitutionCustomField'){
            if(empty($selectedModuleId)){
                $selectedModuleId = $this->request->getData()['InstitutionCustomForms']['custom_module_id'];
            }
        }
		$module = $this->CustomModules
			->find()
			->where([$this->CustomModules->aliasField('code') . ' IN' => ['Institution', 'Institution > Classes'],
				           $this->CustomModules->aliasField('id')=>$selectedModuleId])
			->first();//POCOR-8538
		$selectedModule = $module->id;
		$request->getQuery['module'] = $selectedModule;

		$attr['type'] = 'readonly';
		$attr['value'] = $selectedModule;
		$attr['attr']['value'] = $module->name;

		return $attr;
	}

	public function getModuleQuery() {
		$query = parent::getModuleQuery();
		return $query->where([$this->CustomModules->aliasField('code'). ' IN' => ['Institution', 'Institution > Classes']]);//POCOR-8538
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
	//POCOR-8538 start
	public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
		if($this->request->getQuery('module')){
			$module = $this->CustomModules
				->find()
				->where([$this->CustomModules->aliasField('id')=>$this->request->getQuery('module')])
				->first();//POCOR-8538
			if (isset($extra['toolbarButtons']['add']) && $data->count() ==1 && $module->name=="Institution > Classes") {
				unset($extra['toolbarButtons']['add']);
			}
	    }
    }
	//POCOR-8538 end
	
	
}  

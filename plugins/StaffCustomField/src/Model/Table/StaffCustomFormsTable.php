<?php
namespace StaffCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Network\Request;
use Cake\Event\Event;

class StaffCustomFormsTable extends CustomFormsTable {
	private $dataCount = null;

	public function initialize(array $config) {
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

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function indexAfterAction(Event $event, $data) {
    	$this->dataCount = $data->count();
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'index' && $this->dataCount > 0) {
			if ($toolbarButtons->offsetExists('add')) {
				unset($toolbarButtons['add']);
			}
		}
	}

	public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request) {
		$module = $this->CustomModules
			->find()
			->where([$this->CustomModules->aliasField('code') => 'Staff'])
			->first();
		$selectedModule = $module->id;
		$request->query['module'] = $selectedModule;

		$attr['type'] = 'readonly';
		$attr['value'] = $selectedModule;
		$attr['attr']['value'] = $module->name;

		return $attr;
	}

	public function getModuleQuery() {
		$query = parent::getModuleQuery();
		return $query->where([$this->CustomModules->aliasField('code') => 'Staff']);
	}
}

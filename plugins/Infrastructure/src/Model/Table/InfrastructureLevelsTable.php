<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class InfrastructureLevelsTable extends CustomFormsTable {
	private $_fieldOrder = ['parent_id', 'name', 'description', 'custom_fields'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'Infrastructure.InfrastructureLevels']);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->hasMany('InfrastructureTypes', ['className' => 'Infrastructure.InfrastructureTypes']);
		$this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures']);
		$this->hasMany('CustomFormFields', ['className' => 'Infrastructure.InfrastructureLevelFields', 'foreignKey' => 'infrastructure_level_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomFields', [
			'className' => 'Infrastructure.InfrastructureCustomFields',
			'joinTable' => 'infrastructure_level_fields',
			'foreignKey' => 'infrastructure_level_id',
			'targetForeignKey' => 'infrastructure_custom_field_id'
		]);
		$this->addBehavior('Tree');
	}

	public function beforeAction(Event $event) {
		parent::beforeAction($event);
		$this->fields['lft']['visible'] = false;
		$this->fields['rght']['visible'] = false;
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		parent::indexBeforeAction($event);
		$this->fields['custom_module_id']['visible'] = false;
		$this->_fieldOrder = ['name', 'description', 'custom_fields', 'parent_id'];

		// Hide controls filter and add breadcrumb
		$toolbarElements = [
            ['name' => 'Infrastructure.breadcrumb', 'data' => [], 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);

		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
		if ($parentId != 0) {
			$crumbs = $this
				->find('path', ['for' => $parentId])
				->order([$this->aliasField('lft')])
				->toArray();
			$this->controller->set('crumbs', $crumbs);
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		parent::indexBeforePaginate($event, $request, $options);
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;

		$options['conditions'][] = [
        	$this->aliasField('parent_id') => $parentId
        ];
	}

	public function addEditBeforeAction(Event $event) {
		parent::addEditBeforeAction($event);
		// Setup fields
		// Hide Custom Module
		$this->fields['custom_module_id']['type'] = 'hidden';
		
		$parentId = $this->request->query('parent');
		$this->fields['parent_id']['type'] = 'hidden';

		if (is_null($parentId)) {
			$this->fields['parent_id']['attr']['value'] = 0;
		} else {
			$this->fields['parent_id']['attr']['value'] = $parentId;
			$parentName = $this
				->find('all')
				->select([$this->aliasField('name')])
				->where([$this->aliasField('id') => $parentId])
				->first();
			$this->ControllerAction->field('parent_name', [
				'type' => 'readonly',
				'attr' => ['value' => $parentName->name]
			]);
			array_unshift($this->_fieldOrder, "parent_name");
		}
	}

	public function onGetName(Event $event, Entity $entity) {
		return $event->subject()->Html->link($entity->name, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent' => $entity->id
		]);
	}

	public function onGetParentId(Event $event, Entity $entity) {
		$value = $entity->parent_id == 0 ? ' ' : $entity->parent->name;
		return $value;
	}

	public function getSelectOptions() {
		list($moduleOptions, $selectedModule, $applyToAllOptions, $selectedApplyToAll) = array_values(parent::getSelectOptions());

		$moduleOptions = $this->CustomModules
			->find('list')
			->where([$this->CustomModules->aliasField('model') => $this->InstitutionInfrastructures->registryAlias()])
			->toArray();
		$selectedModule = !is_null($this->request->query('module')) ? $this->request->query('module') : key($moduleOptions);

		return compact('moduleOptions', 'selectedModule', 'applyToAllOptions', 'selectedApplyToAll');
	}
}

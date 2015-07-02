<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;

class InfrastructureLevelsTable extends AppTable {
	private $_fieldOrder = ['parent_id', 'name'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'Infrastructure.InfrastructureLevels']);
		$this->hasMany('InfrastructureTypes', ['className' => 'Infrastructure.InfrastructureTypes']);
	}

	public function beforeAction(Event $event) {
		$this->fields['international_code']['visible'] = false;
		$this->fields['national_code']['visible'] = false;
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'visible', 'name', 'parent_id'
		]);
	}

	public function viewBeforeAction(Event $event) {
		$this->setFieldOrder();
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		$parentId = $this->request->query('parent_id');
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

		$this->setFieldOrder();
	}

	public function onGetParentId(Event $event, Entity $entity) {
		$value = $entity->parent_id == 0 ? ' ' : $entity->parent->name;
		return $value;
	}

	public function onGetName(Event $event, Entity $entity) {
		return $event->subject()->Html->link($entity->name, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent_id' => $entity->id
		]);
	}

	public function setFieldOrder() {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}
}

<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class InfrastructureLevelsTable extends AppTable {
	private $_fieldOrder = ['parent_id', 'name', 'description'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'Infrastructure.InfrastructureLevels']);
		$this->hasMany('InfrastructureTypes', ['className' => 'Infrastructure.InfrastructureTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->addBehavior('Tree');
	}

	public function beforeAction(Event $event) {
		$this->fields['lft']['visible'] = false;
		$this->fields['rght']['visible'] = false;
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		$this->_fieldOrder = ['name', 'description', 'parent_id'];

		// Add breadcrumb
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
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;

		$options['conditions'][] = [
        	$this->aliasField('parent_id') => $parentId
        ];
	}

	public function addEditBeforeAction(Event $event) {
		// Setup fields

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
}

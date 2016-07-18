<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class InfrastructureLevelsTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'Infrastructure.InfrastructureLevels']);
		$this->hasMany('InfrastructureTypes', ['className' => 'Infrastructure.InfrastructureTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->addBehavior('Tree');
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if ($entity->has('editable') && $entity->editable == 0) {
			if (array_key_exists('edit', $buttons)) {
				unset($buttons['edit']);
			}

			if (array_key_exists('remove', $buttons)) {
				unset($buttons['remove']);
			}
		}

		return $buttons;
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('editable', ['visible' => false]);
		$this->field('lft', ['visible' => false]);
		$this->field('rght', ['visible' => false]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$extra['elements']['controls'] = ['name' => 'Infrastructure.breadcrumb', 'data' => [], 'options' => [], 'order' => 1];

		$parentId = $this->request->query('parent');
		if (is_null($parentId)) {
			$query->where([$this->aliasField('parent_id IS NULL')]);
		} else {
			$query->where([$this->aliasField('parent_id') => $parentId]);

			$crumbs = $this
				->find('path', ['for' => $parentId])
				->order([$this->aliasField('lft')])
				->toArray();
			$this->controller->set('crumbs', $crumbs);
		}
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->setupFields($entity);
	}

	public function onGetCode(Event $event, Entity $entity) {
		$hyperlink = $event->subject()->Html->link($entity->code, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent' => $entity->id
		]);

		return $hyperlink;
	}

	public function onUpdateFieldParentId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$parentId = $this->request->query('parent');

			if (!is_null($parentId)) {
				$attr['attr']['value'] = $parentId;
			}
		}

		return $attr;
	}

	public function onUpdateFieldParentName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add' || $action == 'edit') {
			$parentId = $this->request->query('parent');

			if (is_null($parentId)) {
				$attr['visible'] = false;
			} else {
				$parentName = __('All');
				$crumbs = $this
					->find('path', ['for' => $parentId])
					->order([$this->aliasField('lft')])
					->toArray();

				foreach ($crumbs as $key => $crumb) {
					$parentName .= " > " . $crumb->name;
				}

				$attr['value'] = $parentName;
				$attr['attr']['value'] = $parentName;
			}
		}

		return $attr;
	}

	public function getFieldByCode($code, $field) {
		$data = $this
			->find()
			->where([$this->aliasField('code') => $code])
			->first();

		return $data->$field;
	}

	public function getOptions() {
		$levelOptions = $this->find('list')->toArray();
		return $levelOptions;
	}

	private function setupFields(Entity $entity) {
		$this->field('parent_id', ['type' => 'hidden']);
		$this->field('parent_name', [
			'type' => 'readonly',
			'before' => 'code'
		]);

		if ($entity->has('parent_id')) {
			$this->request->query['parent'] = $entity->parent_id;
		}
	}
}

<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class AreaLevelsTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('Areas', ['className' => 'Area.Areas', 'foreign_key' => 'area_level_id']);
		$this->addBehavior('RestrictAssociatedDelete');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('level', ['before' => 'name']);
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['level']['type'] = 'hidden';
	}

	public function onUpdateFieldLevel(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$query = $this->find();
			$results = $query
				->select(['level' => $query->func()->max('level')])
				->all();

			$maxLevel = 0;
			if (!$results->isEmpty()) {
				$data = $results->first();
				$maxLevel = $data->level;
			}

			$attr['attr']['value'] = ++$maxLevel;
		}

		return $attr;
	}
}

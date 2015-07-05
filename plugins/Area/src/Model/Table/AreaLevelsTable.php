<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AreaLevelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('Areas', ['className' => 'Area.Areas']);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$levels = $this
			->find()
			->order(['level' => 'ASC']);

		foreach ($levels as $key => $level) {
			$query = $this->query();
			$query->update()
			    ->set(['level' => ++$key])
			    ->where(['id' => $level->id])
				->execute();
		}
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('level');
		$this->ControllerAction->setFieldOrder('level', 'name');
	}

	public function addEditBeforeAction(Event $event) {
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

<?php
namespace AcademicPeriod\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;

class AcademicPeriodLevelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
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

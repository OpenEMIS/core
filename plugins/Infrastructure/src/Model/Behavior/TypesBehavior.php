<?php 
namespace Infrastructure\Model\Behavior;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class TypesBehavior extends Behavior {
	protected $_defaultConfig = [
		'code' => null
	];

	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 10];
		$events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 1];
		return $events;
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$model = $this->_table;

		if ($model->action == 'index') {
			$selectedLevel = !is_null($model->request->query('level')) ? $model->request->query('level') : '-1';
			$InfrastructureLevels = TableRegistry::get('Infrastructure.InfrastructureLevels');
			$roomId = $InfrastructureLevels->getFieldByCode('ROOM', 'id');

			$code = $this->config('code');
			if (is_null($code)) {
				// call from general, if room selected, redirect to room types
				if ($selectedLevel == $roomId) {
					$url = $model->url('index');
					$url['action'] = 'RoomTypes';

					$event->stopPropagation();
					return $model->controller->redirect($url);
				}
			} else {
				// call from room, if room is not selected, redirect to general
				if ($selectedLevel != $roomId) {
					$url = $model->url('index');
					$url['action'] = 'Types';

					$event->stopPropagation();
					return $model->controller->redirect($url);
				}
			}
		} else {
			unset($extra['elements']['controls']);
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$model = $this->_table;
		$extra['elements']['controls'] = ['name' => 'Infrastructure.controls', 'data' => [], 'options' => [], 'order' => 1];

		$InfrastructureLevels = TableRegistry::get('Infrastructure.InfrastructureLevels');
		$levelOptions = $InfrastructureLevels->getOptions();
		$selectedLevel = $model->queryString('level', $levelOptions);
		$model->advancedSelectOptions($levelOptions, $selectedLevel);
		$model->controller->set(compact('levelOptions', 'selectedLevel'));

		$extra['params']['levelOptions'] = $levelOptions;
		$extra['params']['selectedLevel'] = $selectedLevel;
	}
}

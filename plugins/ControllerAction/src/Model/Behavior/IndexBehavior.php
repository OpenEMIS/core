<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\Core\Configure;
use Cake\Network\Exception\NotFoundException;

class IndexBehavior extends Behavior {
	protected $_defaultConfig = [
		'pageOptions' => [10, 20, 30, 40, 50]
	];

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index'] = 'index';
		$events['ControllerAction.Model.onGetFormButtons'] = ['callable' => 'onGetFormButtons', 'priority' => 5];
		return $events;
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->_table->action == 'index') {
			$buttons->exchangeArray([]);
		}
	}

	public function index(Event $mainEvent, ArrayObject $extra) {
		$model = $this->_table;

		$extra['pagination'] = true;
		$extra['options'] = [];
		$extra['auto_contain'] = true;
		$extra['auto_search'] = true;
		$extra['auto_order'] = true;
		$extra['config']['pageOptions'] = $this->config('pageOptions');
		$query = $model->find();
		$extra['query'] = $query;

		$event = $model->dispatchEvent('ControllerAction.Model.index.beforeAction', [$extra], $this);

		if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->result;
        }

		if ($event->result instanceof Table) {
			$query = $event->result->find();
		}

		$event = $model->controller->dispatchEvent('ControllerAction.Controller.beforeQuery', [$model, $query, $extra], $this);
		$event = $model->dispatchEvent('ControllerAction.Model.index.beforeQuery', [$query, $extra], $this);

		if ($extra['auto_contain']) {
			$contain = $model->getContains('belongsTo', $extra);
			if (!empty($contain)) {
				$query->contain($contain);
			}
		}

		$data = [];
		if ($extra['pagination']) {
			try {
				$data = $model->Paginator->paginate($query, $extra['options']);
			} catch (NotFoundException $e) {
				Log::write('debug', $e->getMessage());
				$action = $model->url('index', 'QUERY');
				if (array_key_exists('page', $action)) {
					unset($action['page']);
				}
				$mainEvent->stopPropagation();
				return $model->controller->redirect($action);
			}
		} else {
			$data = $query->all();
		}

		if (Configure::read('debug')) {
			Log::write('debug', $query->__toString());
		}


		$event = $model->dispatchEvent('ControllerAction.Model.index.afterAction', [$query, $data, $extra], $this);
		if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->result;
        }
		if ($event->result) {
			$data = $event->result;
		}
		$model->controller->set('data', $data);
		return true;
	}
}

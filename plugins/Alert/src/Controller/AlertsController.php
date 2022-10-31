<?php
namespace Alert\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;

class AlertsController extends AppController
{
	public function initialize() {
		parent::initialize();

    }

    public function Alerts() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.Alerts']); }
    public function AlertRules() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertRules']); }
    public function Logs() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertLogs']); }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
	}

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = __('Communications');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Communications', ['plugin' => 'Alert', 'controller' => 'Alerts', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}

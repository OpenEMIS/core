<?php
namespace Alert\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\ORM\Table;

class AlertsController extends AppController
{
	public function initialize(): void {
		parent::initialize();

    }

    public function Alerts() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.Alerts']); }
    public function AlertRules() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertRules']); }
    //POCOR-9509: Add Queue action to view alerts_queue
    public function Queue() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertsQueue']); }
    public function Logs() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertLogs']); }
    public function Notices() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.Notices']); }

    public function beforeFilter(Event|\Cake\Event\EventInterface $event) {
        if ($this->getPlugin() == $this->getPlugin()) {
            $this->Security->setConfig('validatePost', false);
        }
        parent::beforeFilter($event);
    }

	public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra) {
		$header = __('Communications');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Communications', ['plugin' => 'Alert', 'controller' => 'Alerts', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}

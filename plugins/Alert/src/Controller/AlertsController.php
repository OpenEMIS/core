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

		$this->ControllerAction->models = [
			'Questions' => ['className' => 'Alert.SmsMessages'],
			'Responses'	=> ['className' => 'Alert.SmsResponses', 'actions' => ['index']],
			'Logs'		=> ['className' => 'Alert.AlertLogs', 'actions' => ['index']]
		];
    }

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

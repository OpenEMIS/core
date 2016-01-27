<?php
namespace FieldOption\Controller;

use FieldOption\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class FieldOptionsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('FieldOption.FieldOptionValues', ['!search'], ['deleteStrategy' => 'transfer']);
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = 'Field Options';
		
		$this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		
		$this->set('contentHeader', __($header));
	}

	public function onInitialize(Event $event, Table $model) {
		$alias = $model->alias;
		$header = __('Field Options') . ' - ' . $model->getHeader($alias);

		$this->Navigation->addCrumb($model->getHeader($alias));

		$this->set('contentHeader', $header);
	}

	public function NetworkConnectivities() {
		$this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.NetworkConnectivities']);
	}

	public function StaffPositionTitles() {
		$this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffPositionTitles']);
	}
}

<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class NoticesController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Notices');
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Notices');
		$this->set('contentHeader', $header);

		if ($this->request->action == 'index') {
			$order = 1;
			$this->ControllerAction->setFieldOrder('created', $order++);
			$this->ControllerAction->setFieldOrder('message', $order++);
		};	
    }
}

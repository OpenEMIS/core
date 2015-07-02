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
    	$this->Navigation->addCrumb('Notices', ['plugin' => false, 'controller' => 'Notices', 'action' => 'index']);

    	$header = __('Notices');
		$this->set('contentHeader', $header);	
    }
}

<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class LabelsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Labels', ['!remove', '!add']);
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Labels', ['plugin' => false, 'controller' => 'Labels', 'action' => 'index']);

    	$header = __('Labels');
		$this->set('contentHeader', $header);	
    }
}

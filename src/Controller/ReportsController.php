<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class ReportsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
    }
}

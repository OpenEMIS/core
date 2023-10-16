<?php
namespace Error\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class ErrorsController extends AppController{
	public $name = 'Errors';

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
        $this->Auth->allow('error404');
    }

    public function error404() {
    	//$this->layout = 'default';
    }

    public function error403(){
    	//$this->layout = 'default';
    }
}
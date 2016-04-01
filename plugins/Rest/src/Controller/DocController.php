<?php
namespace Rest\Controller;

use Exception;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use App\Controller\AppController;

class DocController extends AppController
{
	public function initialize() {
		parent::initialize();
	}


/***************************************************************************************************************************************************
 *
 * CakePHP events
 *
 ***************************************************************************************************************************************************/
	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		/**
		 * Allow public access to these actions
		 */
		$this->Auth->allow();
	}


/***************************************************************************************************************************************************
 *
 * Controller action functions
 *
 ***************************************************************************************************************************************************/
	public function index() {
		$this->viewBuilder()->layout(false);
	}


}

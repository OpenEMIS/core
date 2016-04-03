<?php
namespace Restful\Controller;

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

		$this->loadComponent('Restful.DocNavigation');
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

	public function beforeRender(Event $event) {
		parent::beforeRender($event);

		if (method_exists($this, 'getView')) {
			$this->getView()->layout('doc');
		} else {
			$this->viewBuilder()->layout('doc');
		}
		$fopen = fopen(RESTFUL_PLUGIN_PATH . DS . 'VERSION.txt', 'r');
		$version = fread($fopen, 1024);
		fclose($fopen);
		$this->set('version', $version);
	}


/***************************************************************************************************************************************************
 *
 * Controller action functions
 *
 ***************************************************************************************************************************************************/
	public function index() {}

	public function listing() {}

	public function viewing() {}

	public function adding() {}

	public function editing() {}

	public function deleting() {}

	public function curl() {}

}

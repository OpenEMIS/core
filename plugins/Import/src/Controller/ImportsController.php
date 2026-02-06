<?php
namespace Import\Controller;

use ArrayObject;
use Import\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class ImportsController extends AppController {
	public function initialize() {
		parent::initialize();
	}

	// public function beforeFilter(Event|\Cake\Event\EventInterface $event) {
	// 	parent::beforeFilter($event);
	// 	$header = 'Field Options';

	// 	$this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);
	// 	$session = $this->request->session();
	// 	$action = $this->request->params['action'];

	// 	$this->set('contentHeader', __($header));
	// }

	// public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra) {

	// }
}

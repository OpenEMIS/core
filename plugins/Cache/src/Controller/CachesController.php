<?php
namespace Cache\Controller;

use Cache\Controller\AppController;
use Cake\Event\Event;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;

class CachesController extends AppController {
	public function initialize() {
		parent::initialize();
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Auth->allow(['clear']);
	}

	public function clear() {
		$this->autoRender = false;
		Cache::clearGroup('labels');
		Cache::clearGroup('_cake_core_');
		Cache::clearGroup('_cake_model_');
		Cache::gc();
		Cache::gc('labels');
		Cache::gc('_cake_core_');
		Cache::gc('_cake_model_');
		$Labels = TableRegistry::get('Labels');
		$Labels->storeLabelsInCache();
	}
}

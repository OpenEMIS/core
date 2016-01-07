<?php
namespace Map\Controller;

use Map\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class MapsController extends AppController {
	public function initialize() {
		parent::initialize();
	}

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Maps');
		$this->set('contentHeader', $header);
    }

	public function index() {
		$model = TableRegistry::get('Institution.Institutions');
		$institutions = $model->find('all')->limit('15')->toArray();
		$InstitutionTypes = TableRegistry::get('Institution.Types');
		$institutionTypes = $InstitutionTypes->getList()->toArray();
// pr($institutionTypes);
		$this->set('model', $model);
		$this->set('institutions', $institutions);
		$this->set('institutionTypes', $institutionTypes);
	}



}

<?php
namespace Map\Controller;

use Map\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;

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
		$institutions = $model->find('all')
							// ->contain(['Types'])
							// ->limit(1000)
							// ->order(['code' => 'DESC'])
							->toArray()
							;
		$InstitutionTypes = TableRegistry::get('Institution.Types');
		$institutionTypes = $InstitutionTypes->getList()->toArray();

		$institutionByType = [];
		$institutionTypeTotal = [];
		$institutionCollection = new Collection($institutions);
		$totalKnownType = 0;
		foreach ($institutionTypes as $id=>$type) {

			$filtered = $institutionCollection->filter(function ($value, $key, $iterator) use ($id) {
			    return $value->institution_type_id == $id;
			});
			$institutionByType[$id] = $filtered->toArray();
			$institutionTypeTotal[$id] = count($filtered->toArray());
			$totalKnownType = $totalKnownType + $institutionTypeTotal[$id];

		}
	
		$filtered = $institutionCollection->filter(function ($value, $key, $iterator) use ($institutionTypes) {
			return !array_key_exists($value->institution_type_id, $institutionTypes);
		});
		$institutionByType['default'] = $filtered->toArray();
		$institutionTypeTotal['default'] = count($filtered->toArray());
	
		$iconColors = [
			'#2b5cac',
			'#ff8338',
			'#049487',
			'#9d2979',
			'#3cc4cb',
			'#d7206f',
			'#c2cb95',
			'#93a8b2',
			'#338ff8',
			'#487049',
			'#9799d2',
			'#4cb3cc',
			'#2044ab',
			'#06fd72',
			'#48781b'
		];

		$this->set( 'model', $model );
		$this->set( 'iconColors', $iconColors );
		$this->set( 'institutions', $institutions );
		$this->set( 'totalInstitutions', count($institutions) );
		$this->set( 'institutionTypes', $institutionTypes );
		$this->set( 'institutionByType', $institutionByType );
		$this->set( 'institutionTypeTotal', $institutionTypeTotal );
	}

	// public function getMarkersData() {
	// 	$model = TableRegistry::get('Institution.Institutions');
	// 	$institutions = $model->find('all')->contain(['Types'])->limit(1000)->order(['code' => 'DESC'])->toArray();
	// 	// $this->ControllerAction->autoRender = false;
	// 	$this->autoRender = false;
	// 	$this->getView()->layout = 'ajax';
	// 	$this->response->type('json');
	// 	$json = json_encode($institutions);
	// 	$this->response->body($json);
	// }

}

<?php
namespace Map\Controller;

use ArrayObject;
use Map\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;

class MapController extends AppController {
	public function initialize() {
		parent::initialize();
	}

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Maps');
		$this->set('contentHeader', $header);
    }

	public function index() {
		$Config = TableRegistry::get('ConfigItems');
		$centerLng = $Config->value('google_map_center_longitude');
		$centerLat = $Config->value('google_map_center_latitude');
		$defaultZoom = $Config->value('google_map_zoom');

		$model = TableRegistry::get('Institution.Institutions');
		$institutions = $model->find('all')
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
		$institutionTypes['default'] = 'Unknown';
		$institutionByType['default'] = $filtered->toArray();
		$institutionTypeTotal['default'] = count($filtered->toArray());
	
		$iconColors = [
			'#DD1B4F',
			'#336600',
			'#0B0BF2',
			'#FF4200',
			'#663399',
			'#00CCFF',
			'#990000',
			'#0ABE17',
			'#FF00CC',
			'#993366'
		];
		$iconColors = new ArrayObject($iconColors);
		if ( count($institutionTypes) > count($iconColors) ) {
			$this->generateRandomColorHex( count($institutionTypes), $iconColors );
		}

		$this->set( 'model', $model );
		$this->set( 'iconColors', $iconColors->getArrayCopy() );
		$this->set( 'institutions', $institutions );
		$this->set( 'totalInstitutions', count($institutions) );
		$this->set( 'institutionTypes', $institutionTypes );
		$this->set( 'institutionByType', $institutionByType );
		$this->set( 'institutionTypeTotal', $institutionTypeTotal );
		$this->set( 'centerLng', $centerLng );
		$this->set( 'centerLat', $centerLat );
		$this->set( 'defaultZoom', $defaultZoom );
	}

	function generateRandomColorHex( $quantity, ArrayObject $iconColors ) {
	    $characters = '0123456789ABCDEF';
	    $charactersLength = strlen($characters);
	    for ( $i = count($iconColors); $i < $quantity; $i++ ) {
		    $randomString = '#';
		    for ( $ii = 0; $ii < 6; $ii++ ) {
		        $randomString .= $characters[rand(0, $charactersLength - 1)];
		    }
		    if ( in_array( $randomString, $iconColors->getArrayCopy() ) ) {
		    	$this->generateRandomColorHex( $quantity, $iconColors );
		    }
		    $iconColors[] = $randomString;
		}
	}

}

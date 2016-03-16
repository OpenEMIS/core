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
		$InstitutionTypes = TableRegistry::get('Institution.Types');
		$institutionTypes = $InstitutionTypes->getList()->toArray();
		$institutionTypes['default'] = 'Unknown';
		$institutionByType = new ArrayObject;
		$totalInstitutions = 0;
		foreach ($institutionTypes as $id=>$type) {
			if ($id=='default') {
				$condition = [$model->aliasField('institution_type_id') .' NOT IN ' => array_keys($institutionTypes)];
			} else {
				$condition = [$model->aliasField('institution_type_id') => $id];
			}
			$numberOfInstitutions = $model->find('all')->where($condition)->count();
			if ($numberOfInstitutions>100) {
				$pages = ceil($numberOfInstitutions / 100);
				$bufferArray = new ArrayObject;
				for ($pageNumber=1; $pageNumber<$pages+1; $pageNumber++) {
					$buffer = $model->connection()
									->newQuery()
									->select([
										'id',
										'institution_type_id',
										'code',
										'name',
										'address',
										'postal_code',
										'longitude',
										'latitude'
									])
									->from('`' . $model->table() . '` AS `' . $model->alias() . '`')
									->where($condition)
									->limit(100)
		    						->page($pageNumber)
		    						->execute()
		    						->fetchAll('assoc')
		    						;
	    			$count = $bufferArray->count();
	    			foreach ($buffer as $key => $value) {
						$bufferArray->offsetSet(($count + $key), $value);
	    			}
	    			unset($buffer);
	    			unset($count);
	    			unset($key);
	    			unset($value);
				}
				$institutionByType->offsetSet($id, $bufferArray);
				$institutionTypeTotal[$id] = $bufferArray->count();
				$totalInstitutions = $totalInstitutions + $bufferArray->count();
				unset($bufferArray);
			} else {
				$buffer = $model->connection()
								->newQuery()
								->select([
									'id',
									'institution_type_id',
									'code',
									'name',
									'address',
									'postal_code',
									'longitude',
									'latitude'
								])
								->from('`' . $model->table() . '` AS `' . $model->alias() . '`')
								->where($condition)
								->execute()
	    						->fetchAll('assoc');
				$institutionByType->offsetSet($id, new ArrayObject($buffer));
				$institutionTypeTotal[$id] = count($buffer);
				$totalInstitutions = $totalInstitutions + count($buffer);
				unset($buffer);
			}
		}

		$iconColors = new ArrayObject([
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
		]);
		if ( count($institutionTypes) > $iconColors->count() ) {
			$this->generateRandomColorHex( count($institutionTypes), $iconColors );
		}

		$this->set( 'iconColors', $iconColors->getArrayCopy() );
		$this->set( 'totalInstitutions', $totalInstitutions );
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

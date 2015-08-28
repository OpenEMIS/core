<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;

class AdvanceSearchBehavior extends Behavior {
	private $_exclude = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
	protected $model = '';
	protected $modelAlias = '';
	protected $data = '';
	protected $_defaultConfig = [
		'' => ''
	];

	public function initialize(array $config) {

	}
	

/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.index.beforePaginate' => 'indexBeforePaginate',
			'ControllerAction.Model.afterAction' => 'afterAction',
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}


/******************************************************************************************************************
**
** CakePhp events
**
******************************************************************************************************************/
	public function afterAction(Event $event) {
		if ($this->_table->action == 'index') {
		    $labels = TableRegistry::get('Labels');
			$filters = [];
			$advancedSearch = false;
			$session = $this->_table->request->session();
			$language = $session->read('System.language');
			$fields = $this->model->schema()->columns();

			foreach ($fields as $key) {
				if (!in_array($key , $this->_exclude)) {
					if ($this->isForeignKey($key)) {
						$label = $labels->getLabel($this->modelAlias, $key, $language);
						$relatedModel = $this->getAssociatedBelongsToModel($key);
						$selected = (is_array($this->data) && isset($this->data[$key])) ? $this->data[$key] : '' ;
						if (!empty($selected) && $advancedSearch == false) {
							$advancedSearch = true;
						}
						$filters[$key] = [
							'label' => ($label) ? $label : $this->_table->getHeader($relatedModel->alias()),
							'options' => $relatedModel->getList(),
							'selected' => $selected
						];
					}
				}
			}

			$this->_table->controller->viewVars['indexElements']['advanced_search'] = [
	            'name' => 'advanced_search',
	            'data' => compact('filters', 'advancedSearch'),
	            'options' => [],
	            'order' => 0
	        ];
		// 	pr('search behavior');
		// 	pr($this->_table->controller->viewVars['indexElements']);
		}
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event) {
		$this->model = $this->_table;
		$this->modelAlias = $this->model->alias();
		// pr($this->_table->request->data());
		$this->data = (isset($this->_table->request->data['AdvanceSearch']) && isset($this->_table->request->data['AdvanceSearch'][$this->modelAlias])) ? $this->_table->request->data['AdvanceSearch'][$this->modelAlias] : [];
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $paginateOptions) {
		$conditions = '';
		$advancedSearch = [];
		
		if (isset($request->data['AdvanceSearch'])) {
			$advancedSearch = $request->data['AdvanceSearch'][$this->model->alias()];
		}
		
		foreach ($advancedSearch as $key=>$value) {
			$pathId = null;
			if( $key == 'area_id' || $key == 'area_administrative_id'){
				$Table = "";
				$tableName = "";
				$tableAlias = "";
				switch ($key) {
					case 'area_id':
						$Table = TableRegistry::get('Area.Areas');
						$tableAlias = 'AreaAreas';
						$tableName = 'areas';
						$id = $advancedSearch['area_id'];
						break;
					case 'area_administrative_id':
						$Table = TableRegistry::get('Area.AreaAdministratives');
						$tableAlias = 'AreaAreaAdministratives';
						$tableName = 'area_administratives';
						$id = $advancedSearch['area_administrative_id'];
						break;
				}
				$Table = TableRegistry::get($tableName);
				if (!empty($id)) {
					$area = $Table->get($id);
					$lft = $area->lft;
					$rght = $area->rght;

					$query
						->innerJoin([$tableAlias => $tableName], [
								$tableAlias.'.id = '. $this->model->aliasField($key),
								$tableAlias.'.lft >=' => $lft,
								$tableAlias.'.rght <=' => $rght,
							]);
				}
        	} else {
				if (!empty($value) && $value>0) {
					$conditions[$this->model->aliasField($key)] = $value;
	        	}
			}
        }
        if (!empty($conditions)) {
        	$query->where($conditions);
        	pr($query->sql());
        }
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	public function getAssociatedBelongsToModel($field) {
		$relatedModel = null;
		foreach ($this->model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					$relatedModel = $assoc;
					break;
				}
			}
		}
		return $relatedModel;
	}

	public function isForeignKey($field) {
		foreach ($this->model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					return true;
				}
			}
		}
		return false;
	}

	public function getAssociatedEntityArrayKey($field) {
		$associationKey = $this->getAssociatedBelongsToModel($field);
		$associatedEntityArrayKey = null;
		if (is_object($associationKey)) {
			$associatedEntityArrayKey = Inflector::underscore(Inflector::singularize($associationKey->alias()));
		} else {
			die($field . '\'s association not found in ' . $this->modelAlias);
		}
		return $associatedEntityArrayKey;
	}


/******************************************************************************************************************
**
** specific field functions
**
******************************************************************************************************************/
	// public function onGetField(Event $event, Entity $entity) {
	// 	$value = str_replace('_id', '', $entity->field);
	// 	return Inflector::humanize($value);
	// }

	// public function onGetOldValue(Event $event, Entity $entity) {
	// 	if (array_key_exists($entity->field_type, $this->_dateTypes)) {
	// 		return $this->formatToSystemConfig($entity->old_value, $entity->field_type);
	// 	}
	// }


/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/
	public function formatToSystemConfig($value, $type) {
	}

}

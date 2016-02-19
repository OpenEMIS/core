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
		'' => '',
		'display_country' => true,
	];

	public function initialize(array $config) {
		$this->_table->addBehavior('Area.Area');
	}
	

/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
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
			$fields = $this->_table->schema()->columns();
			$requestData = $this->_table->request->data;
			$advanceSearchData = isset($requestData['AdvanceSearch']) ? $requestData['AdvanceSearch'] : [];
			$advanceSearchModelData = isset($advanceSearchData[$this->_table->alias()]) ? $advanceSearchData[$this->_table->alias()] : [];

			foreach ($fields as $key) {
				if (!in_array($key , $this->_exclude)) {
					if ($this->isForeignKey($key)) {
						$label = $labels->getLabel($this->_table->alias(), $key, $language);
						$relatedModel = $this->getAssociatedBelongsToModel($key);
						$selected = (isset($advanceSearchModelData['belongsTo']) && isset($advanceSearchModelData['belongsTo'][$key])) ? $advanceSearchModelData['belongsTo'][$key] : '' ;
						if (!empty($selected) && $advancedSearch == false) {
							$advancedSearch = true;
						}

						$filters[$key] = [
							'label' => ($label) ? $label : $this->_table->getHeader($relatedModel->alias()),
							'options' => $relatedModel->getList(),
							'selected' => $selected
						];
						$relatedModelTable = $relatedModel->table();
						if ($relatedModelTable == 'area_administratives') {
							if (!$this->config('display_country')) {
								$worldId = $relatedModel->find()->where([$relatedModel->aliasField('code') => 'World'])->first()->id;
								$options = $relatedModel->find('list')
									->where([
										'OR' => [
											[$relatedModel->aliasField('is_main_country') => 1],
											[$relatedModel->aliasField('parent_id').' IS NOT ' => $worldId]
										],
										[$relatedModel->aliasField('id').' IS NOT ' => $worldId]
									]);
								$filters[$key]['options'] = $options;
							}
						}
					}
				}
			}
			if (! empty ($advanceSearchModelData['isSearch']) ) {
				$advancedSearch = true;
			}

			$searchables = new ArrayObject();
	        // trigger events for additional searchable fields
	        $this->_table->dispatchEvent('AdvanceSearch.onSetupFormField', [$searchables, $advanceSearchModelData], $this);

			$this->_table->controller->viewVars['indexElements']['advanced_search'] = [
	            'name' => 'advanced_search',
	            'data' => compact('filters', 'searchables', 'advancedSearch'),
	            'options' => [],
	            'order' => 0
	        ];
		}
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $paginateOptions) {
		$reset = $request->data('reset');
		if (!empty($reset)) {
			if ($reset == 'Reset') {
				$model = $this->_table;
				$alias = $model->alias();
				// clear session
				if ($model->Session->check($alias.'.advanceSearch.belongsTo')) {
					 $model->Session->delete($alias.'.advanceSearch.belongsTo');	
				}
				if ($model->Session->check($alias.'.advanceSearch.hasMany')) {
					 $model->Session->delete($alias.'.advanceSearch.hasMany');	
				}
				// clear fields value
				foreach ($request->data['AdvanceSearch'][$alias]['belongsTo'] as $key=>$value) {
					$request->data['AdvanceSearch'][$alias]['belongsTo'][$key] = '';
				}
				foreach ($request->data['AdvanceSearch'][$alias]['hasMany'] as $key=>$value) {
					$request->data['AdvanceSearch'][$alias]['hasMany'][$key] = '';
				}
				$request->data['AdvanceSearch'][$alias]['isSearch'] = false;
			}
		}
		return $this->advancedSearchQuery ($request, $query);
	}

	public function advancedSearchQuery (Request $request, Query $query) {
		$conditions = '';
		$advancedSearchBelongsTo = [];
		$advancedSearchHasMany = [];

		$model = $this->_table;
		$alias = $model->alias();

		$advancedSearchBelongsTo = $model->Session->check($alias.'.advanceSearch.belongsTo') ? $model->Session->read($alias.'.advanceSearch.belongsTo') : [];
		$advancedSearchHasMany = $model->Session->check($alias.'.advanceSearch.hasMany') ? $model->Session->read($alias.'.advanceSearch.hasMany') : [];

		if ($request->is(['post', 'put'])) {
			if (isset($request->data['AdvanceSearch']) && isset($request->data['AdvanceSearch'][$alias])) {
				if (isset($request->data['AdvanceSearch'][$alias]['belongsTo'])) {
					$advancedSearchBelongsTo = $request->data['AdvanceSearch'][$alias]['belongsTo'];
				}
				if (isset($request->data['AdvanceSearch'][$alias]['hasMany'])) {
					$advancedSearchHasMany = $request->data['AdvanceSearch'][$alias]['hasMany'];
				}
				$model->Session->write($alias.'.advanceSearch', $request->data['AdvanceSearch'][$alias]);
			}
		}

		if ($model->Session->check($alias.'.advanceSearch')) {
			$request->data['AdvanceSearch'][$alias] = $model->Session->read($alias.'.advanceSearch');
		}

		$areaKeys[] = 'area_id';
		$areaKeys[] = 'area_administrative_id';
		$areaKeys[] = 'birthplace_area_id';
		$areaKeys[] = 'address_area_id';

		foreach ($advancedSearchBelongsTo as $key=>$value) {
			if (!empty($value) && $value>0) {
				if(in_array($key, $areaKeys)){
					switch ($key) {
						case 'area_id':
							$tableName = 'areas';
							$id = $advancedSearchBelongsTo[$key];
							$query->find('Areas', ['id' => $id, 'columnName' => $key, 'table' => $tableName]);
							break;

						case 'area_administrative_id':
						case 'birthplace_area_id':
						case 'address_area_id':
							$tableName = 'area_administratives';
							$id = $advancedSearchBelongsTo[$key];
							$AreaAdministrativeTable = TableRegistry::get('Area.AreaAdministratives');
							$query->find('Areas', ['id' => $id, 'columnName' => $key, 'table' => $tableName]);
							break;
					}
				} else {
					$conditions[$model->aliasField($key)] = $value;
				}
        	}
        }
        if (!empty($conditions)) {
        	$query->where($conditions);
        }

        if (!empty($advancedSearchHasMany)) {
	        // trigger events for additional searchable fields
	        $model->dispatchEvent('AdvanceSearch.onBuildQuery', [$query, $advancedSearchHasMany], $this);
	    }

        return $query;
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	public function getAssociatedBelongsToModel($field) {
		$relatedModel = null;
		foreach ($this->_table->associations() as $assoc) {
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
		foreach ($this->_table->associations() as $assoc) {
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
			die($field . '\'s association not found in ' . $this->_table->alias());
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

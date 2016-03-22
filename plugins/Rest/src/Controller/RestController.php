<?php
namespace Rest\Controller;

use Exception;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use App\Controller\AppController;

class RestController extends AppController
{
	private $_debug = false;
	public $components = [
		'RequestHandler'
	];

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

	public function beforeRender(Event $event) {
		if ($this->_debug) {
			$_serialize = array_merge(['request_method', 'action'], $this->viewVars['_serialize']);
			$this->set([
				'request_method' => $this->request->method(),
				'action' => $this->request->params['action'],
	            '_serialize' => $_serialize
	        ]);
	    }
	}


/***************************************************************************************************************************************************
 *
 * Controller action functions
 *
 ***************************************************************************************************************************************************/
	private $_specialParams = ['type'];
	public function index($model) {
		$target = $this->_instantiateModel($model);
		if ($target) {
			$requestQueries = $this->request->query;
			$listOnly = false;
			if (array_key_exists('type', $requestQueries) && $requestQueries['type']=='list') {
				$listOnly = true;
			}
			$limit = 10;
			if (array_key_exists('type', $requestQueries)) {
				$limit = $requestQueries['limit'];
			}
			$page = 1;
			if (array_key_exists('type', $requestQueries)) {
				$page = $requestQueries['page'];
			}

			if ($listOnly) {
				$query = $target->find('list')->limit($limit)->offset($page);
			} else {
				$query = $target->find()->limit($limit)->offset($page);
			}
			$conditions = [];
			if (!empty($requestQueries)) {
				$conditions = $this->_buildConditions($target, $requestQueries);
			}
			if (is_bool($conditions) && !$conditions) {
				$this->_setError('Extra query fields declared do not exists in '.$target->registryAlias());
			} else {
				if (!empty($conditions)) {
					$query->where($conditions);
				}
				if ($listOnly) {
					$data = $query->toArray();
				} else {
					$data = $query->all();
					$data = $this->_formatBinaryValue($data);
				}
				$this->set([
		            'data' => $data,
		            '_serialize' => ['data']
		        ]);
			}
	    }
	}

	private function _buildConditions($target, $requestQueries) {
		$targetColumns = $target->schema()->columns();
		$conditions = [];
		foreach ($requestQueries as $requestQueryKey => $requestQuery) {
			if (in_array($requestQueryKey, $this->_specialParams)) {
				continue;
			}
			if (!in_array($requestQueryKey, $targetColumns)) {
				return false;
			}
			$conditions[$target->aliasField($requestQueryKey)] = $requestQuery;
		}
		return $conditions;
	}

	public function add($model) {
		$target = $this->_instantiateModel($model);
		if ($target) {
			$entity = $target->newEntity($this->request->data);
	        $target->save($entity);
	        $this->set([
	            'data' => $entity,
	            'error' => $entity->errors(),
	            '_serialize' => ['data', 'error']
	        ]);
	    }
	}

	public function view($model, $id) {
		$target = $this->_instantiateModel($model);
		if ($target) {
			$data = $target->get($id);
			$data = $this->_formatBinaryValue($data);
			$this->set([
	            'data' => $data,
	            '_serialize' => ['data']
	        ]);
	    }
	}

	public function edit($model, $id) {
		$target = $this->_instantiateModel($model);
		if ($target) {
			if ($target->exists([$target->primaryKey() => $id])) {
				$entity = $target->get($id);
	            $entity = $target->patchEntity($entity, $this->request->data);
		        if (empty($entity->errors())) {
		        	$target->save($entity);
			        $this->set([
			            'data' => $entity,
			            'error' => $entity->errors(),
			            '_serialize' => ['data', 'error']
			        ]);
			    } else {
			    	$this->_setError($entity->errors());
			    }
		    } else {
		    	$this->_setError('Record does not exists');
		    }
	    }
	}

	public function delete($model, $id) {
		$target = $this->_instantiateModel($model);
		if ($target) {
			if ($target->exists([$target->primaryKey() => $id])) {
				$entity = $target->get($id);
		        $message = 'Deleted';
		        if (!$target->delete($entity)) {
		            $message = 'Error';
		        }
				$this->set([
		            'result'=> $message,
		            '_serialize' => ['result']
		        ]);
		    } else {
		    	$this->_setError('Record does not exists');
		    }
	    }
	}


/***************************************************************************************************************************************************
 *
 * private functions
 *
 ***************************************************************************************************************************************************/
	private function _instantiateModel($model) {
		$model = str_replace('-', '.', $model);
		$target = TableRegistry::get($model);
		try {
			$data = $target->find('all')->limit('1');
			return $target;
		} catch (Exception $e) {
			$this->_setError();
			return false;
		}
	}

	private function _setError($message = 'Requested Plugin.Model does not exists') {
		$model = str_replace('-', '.', $this->request->params['model']);
		$this->set([
            'model' => $model,
            'error' => $message,
            '_serialize' => ['request_method', 'action', 'model', 'error']
        ]);
	}

	private function _formatBinaryValue($data) {
		if ($data instanceof Entity) {
			foreach ($data->visibleProperties() as $property) {
				if (is_resource($data->$property)) {
					$data->$property = base64_encode("data:image/jpeg;base64,".stream_get_contents($data->$property));						
				}
			}
		} else {
			foreach ($data as $key => $value) {
				foreach ($value->visibleProperties() as $property) {
					if (is_resource($value->$property)) {
						$value->$property = base64_encode("data:image/jpeg;base64,".stream_get_contents($value->$property));						
					}
				}
			}
		}
		return $data;
	}

}

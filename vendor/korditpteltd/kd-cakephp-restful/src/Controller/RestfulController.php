<?php
namespace Restful\Controller;

use Exception;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use App\Controller\AppController;

class RestfulController extends AppController
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

	    if (empty($this->request->params['_ext'])) {
	    	$this->request->params['_ext'] = 'json';
	    }
	}

	public function beforeRender(Event $event) {
		parent::beforeRender($event);
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
	public function nothing() {
		$this->_outputData([]);
	}

	public function index($model) {
		$target = $this->_instantiateModel($model);
		if ($target) {
			$requestQueries = $this->request->query;

			$listOnly = false;
			if (array_key_exists('_finder', $requestQueries) && substr_count($requestQueries['_finder'], 'list')>0) {
				$listOnly = true;
				$query = $this->_parseFindByList($target, $requestQueries);
			} else {
				$query = $target->find();
			}

			if (array_key_exists('_finder', $requestQueries)) {
				$this->_attachFieldSpecificFinders($target, $requestQueries, $query);
				$this->_attachFinders($target, $requestQueries, $query);
			}

			$containments = $this->_setupContainments($target, $requestQueries, $query);

			$conditions = [];
			if (!empty($requestQueries)) {
				$conditions = $this->_setupConditions($target, $requestQueries);
			}
			$fields = [];
			if (!empty($requestQueries)) {
				$fields = $this->_filterSelectFields($target, $requestQueries, $containments);
			}
			if (is_bool($conditions) && !$conditions) {
				$this->_outputError('Extra query parameters declared do not exists in '.$target->registryAlias());
			} else if (is_bool($fields) && !$fields) {
				$this->_outputError('One or more selected fields do not exists in '.$target->registryAlias());
			} else {
				if (!empty($conditions)) {
					$query->where($conditions);
				}
				if (!empty($fields)) {
					$query->select($fields);
				}

				if (array_key_exists('_limit', $requestQueries)) {
					$limit = $requestQueries['_limit'];
					$page = 1;
					if (array_key_exists('_page', $requestQueries)) {
						$page = $requestQueries['_page'];
					}
					$query->limit($limit)->page($page);
				}

				try {
					$data = [];
					if ($listOnly) {
						$data = $query->toArray();
					} else {
						$data = $this->_formatBinaryValue($query->all());
					}
					$this->_outputData($data);
				} catch (Exception $e) {
					$this->_outputError($e->getMessage());
				}
			}
	    }
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
			if ($target->exists([$target->aliasField($target->primaryKey()) => $id])) {
				$requestQueries = $this->request->query;
	
				$query = $target->find();
				$containments = $this->_setupContainments($target, $requestQueries, $query);

				$fields = [];
				if (!empty($requestQueries)) {
					$fields = $this->_filterSelectFields($target, $requestQueries, $containments);
				}
				if (is_bool($fields) && !$fields) {
					$this->_outputError('One or more selected fields do not exists in '.$target->registryAlias());
				} else {
					if (!empty($fields)) {
						$query->select($fields);
					}
					try {
						$data = $query->where([$target->aliasField($target->primaryKey()) => $id])->first();
						$data = $this->_formatBinaryValue($data);
						$this->_outputData($data);
					} catch (Exception $e) {
						$this->_outputError($e->getMessage());
					}
			    }
		    } else {
		    	$this->_outputError('Record does not exists');
		    }
	    }
	}

	public function edit($model, $id) {
		$target = $this->_instantiateModel($model);
		if ($target) {
			if ($target->exists([$target->aliasField($target->primaryKey()) => $id])) {
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
			    	$this->_outputError($entity->errors());
			    }
		    } else {
		    	$this->_outputError('Record does not exists');
		    }
	    }
	}

	public function delete($model, $id) {
		$target = $this->_instantiateModel($model);
		if ($target) {
			if ($target->exists([$target->aliasField($target->primaryKey()) => $id])) {
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
		    	$this->_outputError('Record does not exists');
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
			$this->_outputError();
			return false;
		}
	}

	private function _outputError($message = 'Requested Plugin-Model does not exists') {
		$model = str_replace('-', '.', $this->request->params['model']);
		$this->set([
            'model' => $model,
            'error' => $message,
            '_serialize' => ['request_method', 'action', 'model', 'error']
        ]);
	}

	private function _outputData($data) {
		$this->set([
            'data' => $data,
            '_serialize' => ['data']
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

	private function _parseFindByList(Table $target, array $requestQueries) {
		$finders = explode(',', $requestQueries['_finder']);
		foreach ($finders as $key => $finder) {
			if (substr_count($finder, 'list')>0) {

				$bracketPost = strpos($finder, '[');
				if ($bracketPost>0) {
					$parameters = substr($finder, $bracketPost+1, -1);
					$parameters = explode(';', $parameters);
				} else {
					$parameters = [];
				}

		        $keyField = $target->primaryKey();
		        $valueField = $target->displayField();
		        $groupField = null;
				if (isset($parameters[0]) && !empty($parameters[0])) {
					$keyField = $parameters[0];
				}
				if (isset($parameters[1]) && !empty($parameters[1])) {
					$valueField = $parameters[1];
				}
				if (isset($parameters[2]) && !empty($parameters[2])) {
					$groupField = $parameters[2];
				}
				return $target->find('list', [
			            'keyField' => $keyField,
			            'valueField' => $valueField,
			            'groupField' => $groupField
					]);

			}
		}
	}
	
	private $_specificFields = ['visible', 'active', 'order', 'editable'];
	private function _attachFieldSpecificFinders(Table $target, array $requestQueries, Query $query) {
		$finders = explode(',', $requestQueries['_finder']);
		foreach ($finders as $key => $finder) {
			$strlen = (strpos($finder, '[')>0) ? strpos($finder, '[') : strlen($finder);
			$functionName = strtolower(substr($finder, 0, $strlen));
			if (in_array($functionName, $this->_specificFields)) {
				$targetColumns = $target->schema()->columns();
				if (in_array($functionName, $targetColumns)) {
					$parameters = $this->_setupFinderParams($finder);
					if (method_exists($target, 'find'.ucwords($functionName))) {
						$query->find($functionName, $parameters);
					}
				}
			}
		}
		return $query;
	}
	
	private function _attachFinders(Table $target, array $requestQueries, Query $query) {
		$finders = explode(',', $requestQueries['_finder']);
		foreach ($finders as $key => $finder) {
			$strlen = (strpos($finder, '[')>0) ? strpos($finder, '[') : strlen($finder);
			$functionName = strtolower(substr($finder, 0, $strlen));
			if (!in_array($functionName, array_merge($this->_specificFields, ['list']))) {
				$parameters = $this->_setupFinderParams($finder);
				if (method_exists($target, 'find'.ucwords($functionName))) {
					$query->find($functionName, $parameters);
				} else {
					foreach ($target->behaviors()->loaded() as $behaviorName) {
						$behavior = $target->behaviors()->get($behaviorName);
						if (method_exists($behavior, 'find'.ucwords($functionName))) {
							$query->find($functionName, $parameters);
						}
					}
				}
			}
		}
		return $query;
	}

	private function _setupFinderParams($finder) {
		$strlen = (strpos($finder, '[')>0) ? strpos($finder, '[') : strlen($finder);
		$parameters = substr($finder, $strlen+1, -1);
		if (!empty($parameters)) {
			$parameters = explode(';', $parameters);
			foreach ($parameters as $key => $value) {
				$buffer = explode(':', $value);
				$parameters[$buffer[0]] = $buffer[1];
			}
		} else {
			$parameters = [];
		}
		return $parameters;
	}
	
	private $_specialParams = ['_finder', '_limit', '_page', '_fields', '_contain'];
	private function _setupConditions(Table $target, array $requestQueries) {
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

	private function _setupContainments(Table $target, array $requestQueries, Query $query) {
		$contains = [];
		if (array_key_exists('_contain', $requestQueries)) {
			$contains = array_map('trim', explode(',', $requestQueries['_contain']));
			if (!empty($contains)) {
				$trueExists = false;
				foreach ($contains as $key => $contain) {
					if ($contain=='true') {
						$trueExists = true;
						break;
					}
				}
				if ($trueExists) {
					foreach ($target->associations() as $assoc) {
						$contains[] = $assoc->name();
					}
				}
				$query->contain($contains);
			}
		}
		return $contains;
	}

	private function _filterSelectFields(Table $target, array $requestQueries, array $containments=[]) {
		$targetColumns = $target->schema()->columns();
		if (!array_key_exists('_fields', $requestQueries)) {
			return [];
		}
		$fields = array_map('trim', explode(',', $requestQueries['_fields']));
		foreach ($fields as $key => $field) {
			if (!in_array($field, $targetColumns)) {
				return false;
			} else {
				$fields[$key] = $target->aliasField($field);
			}
		}
		if (!empty($containments)) {
			foreach ($containments as $key => $name) {
				foreach ($target->associations() as $assoc) {
					if ($name == $assoc->name()) {
						$containmentColumns = $assoc->schema()->columns();
						foreach ($containmentColumns as $containmentColumn) {
							$fields[] = $assoc->aliasField($containmentColumn);
						}
					}
				}
			}
		}
		return $fields;
	}

}

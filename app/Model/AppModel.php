<?php
/**
 * Application model for Cake.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {
	public $formatResult = false;
	// ControllerActionBehaviour Properties
	public $render = true;
	public $plugin = true; // deprecated
	public $Session;
	public $Message;
	public $Navigation;
	public $controller;
	public $request;
	public $action;
	public $fields;
	
	public function setField($field, $obj, $order=0) {
		$fields = $this->fields;
		if (empty($fields)) {
			$fields = $this->getFields();
		}
		$key = $field;
		if (array_key_exists($field, $fields)) {
			$key = $obj[$field]['model'] . '.' . $field;
		}
		$fields[$key] = $obj[$field];
		$fields[$key]['order'] = count($fields);
		$this->fields = $fields;
		$this->setFieldOrder($key, $order);
	}
	
	public function getFields($options=array()) {
		$fields = $this->schema();
		$belongsTo = $this->belongsTo;
		
		$i = 0;
		foreach($fields as $key => $obj) {
			$fields[$key]['order'] = $i++;
			$fields[$key]['visible'] = true;
			if (!array_key_exists('model', $fields[$key])) {
				$fields[$key]['model'] = $this->alias;
			}
		}
		
		$fields['id']['type'] = 'hidden';
		$defaultFields = array('modified_user_id', 'modified', 'created_user_id', 'created', 'order');
		foreach ($defaultFields as $field) {
			if (array_key_exists($field, $fields)) {
				if ($field == 'modified_user_id') {
					$fields[$field]['type'] = $field;
					$fields[$field]['dataModel'] = 'ModifiedUser';
				}
				if ($field == 'created_user_id') {
					$fields[$field]['type'] = $field;
					$fields[$field]['dataModel'] = 'CreatedUser';
				}
				$fields[$field]['visible'] = array('view' => true, 'edit' => false);
				$fields[$field]['labelKey'] = 'general';
			}
		}
		if (array_key_exists('name', $fields)) {
			$fields['name']['labelKey'] = 'general';
		}

		$this->fields = $fields;
		return $fields;
	}
	
	public function setFieldOrder($field, $order) {
		$fields = $this->fields;
		$found = false;
		foreach ($fields as $key => $obj) {
			if ($found && $key !== $field) {
				$fields[$key]['order'] = $fields[$key]['order'] + 1;
			} else {
				if ($field === $key) {
					$found = true;
					$fields[$key]['order'] = $order;
				} else if ($fields[$key]['order'] == $order) {
					$found = true;
					$fields[$key]['order'] = $order + 1;
				}
			}
		}		
		$fields[$field]['order'] = $order;
		uasort($fields, array($this->alias, 'sortFields'));
		$this->fields = $fields;
	}
	
	public static function sortFields($a, $b) {
		return $a['order'] >= $b['order'];
	}
	// End ControllerActionBehaviour
	
	public function findList($options=array()) {
		$class = $this->alias;
		
		if(is_bool($options) && $options) {
			$options = array();
			$options['conditions'] = array($class.'.visible' => 1);
		}
		$conditions = !isset($options['conditions']) ? array() : $options['conditions'];
		$fields = !isset($options['fields']) ? array($class . '.id', $class . '.name') : $options['fields'];
		$orderBy = !isset($options['orderBy']) ? 'order' : $options['orderBy'];
		$order = !isset($options['order']) ? 'ASC' : $options['order'];
		$list = $this->find('list', array(
				'fields' => $fields,
				'conditions' => $conditions,
				'order' => array($class . '.' . $orderBy)
			)
		);
		return $list;
	}
	
	public function findOptions($options=array()) {
		$class = $this->alias;
		if(is_bool($options) && $options) {
			$options = array();
			$options['conditions'] = array($class.'.visible' => 1);
		}
		$conditions = !isset($options['conditions']) ? array() : $options['conditions'];
		$order = !isset($options['order']) ? array($class . '.order') : $options['order'];
		
		$this->formatResult = true;
		$list = $this->find('all', array(
			'recursive' => -1,
			'conditions' => $conditions,
			'order' => $order
		));
		return $list;
	}
	
	public function getCurrentDateTime() {
		return date('Y-m-d H:i:s');
	}
	
	public function beforeSave($options = array()) {
		$userId = session_id() !== '' ? CakeSession::read('Auth.User.id') : NULL;
		
		if(empty($this->data[$this->alias][$this->primaryKey])) {
			unset($this->data[$this->alias]['modified']);
			if(!is_null($userId)) {
				$this->data[$this->alias]['created_user_id'] = $userId;
			}
		} else {
			if(!is_null($userId)) {
				$this->data[$this->alias]['modified_user_id'] = $userId;
			}
		}
		return true;
	}
	
	public function afterFind($results, $primary=false) {
		$results = parent::afterFind($results, $primary);
		if($this->formatResult && ($this->findQueryType==='all' || $this->findQueryType==='first')) {
			$data = $this->formatArray($results);
			$this->formatResult = false;
			return $data;
		}
		return $results;
	}
	
	public function formatArray($list) {
		$result = array();
		foreach($list as $record) {
			$data = array();
			foreach($record as $model => $val) {
				$data = array_merge($data, $val);
			}
			$result[] = $data;
		}
		return $result;
	}
	
	public function formatToTable($list) {
		$head = array();
		$records = array();
		$first = current(current($list));
		
		foreach($first as $title => $val) {
			$head[] = $title;
		}
		
		foreach($list as $record) {
			foreach($record as $model => $row) {
				$data = array();
				foreach($row as $name => $value) {
					$data[] = $value;
				}
				$records[] = $data;
			}
		}
		
		$result = array(
			'head' => $head,
			'records' => $records
		);
		return $result;
	}
	
    public function getLastQueries()
    {
        $dbo = $this->getDatasource();
        $logs = $dbo->getLog();

        return $logs;
    }
}

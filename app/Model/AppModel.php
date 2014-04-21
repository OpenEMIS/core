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
	public $render = true; // ControllerActionBehaviour variable
	
	public function findList($options=array()) {
		$class = $this->alias;
		
		if(is_bool($options) && $options) {
			$options = array();
			$options['conditions'] = array($class.'.visible' => 1);
		}
		$conditions = !isset($options['conditions']) ? array() : $options['conditions'];
		$orderBy = !isset($options['orderBy']) ? 'order' : $options['orderBy'];
		$order = !isset($options['order']) ? 'ASC' : $options['order'];
		$list = $this->find('list', array(
				'fields' => array($class . '.id', $class . '.name'),
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

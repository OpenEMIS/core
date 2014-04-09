<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

class FieldOptionBehavior extends ModelBehavior {
	public $optionFields = array(
		'fields' => array(
			array('field' => 'id', 'type' => 'hidden'),
			array('field' => 'name', 'label' => 'Name'),
			array('field' => 'international_code', 'label' => 'International Code'),
			array('field' => 'national_code', 'label' => 'National Code'),
			array('field' => 'visible', 'label' => 'Visible', 'type' => 'select'),
			array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
			array('field' => 'modified', 'label' => 'Modified On', 'edit' => false),
			array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
			array('field' => 'created', 'label' => 'Created On', 'edit' => false)
		)
	);
	
	public function reorder(Model $model, $data) {
		$id = $data[$model->alias]['id'];
		$idField = $model->alias . '.id';
		$orderField = $model->alias . '.order';
		$move = $data[$model->alias]['move'];
		$order = $model->field('order', array('id' => $id));
		$conditions = isset($data['conditions']) ? $data['conditions'] : array();
		$idConditions = array_merge(array($idField => $id), $conditions);
		$updateConditions = array_merge(array($idField . ' <>' => $id), $conditions);
		
		$this->fixOrder($model, $conditions);
		if($move === 'up') {
			$model->updateAll(array($orderField => $order-1), $idConditions);
			$updateConditions[$orderField] = $order-1;
			$model->updateAll(array($orderField => $order), $updateConditions);
		} else if($move === 'down') {
			$model->updateAll(array($orderField => $order+1), $idConditions);
			$updateConditions[$orderField] = $order+1;
			$model->updateAll(array($orderField => $order), $updateConditions);
		} else if($move === 'first') {
			$model->updateAll(array($orderField => 1), $idConditions);
			$updateConditions[$orderField . ' <'] = $order;
			$model->updateAll(array($orderField => $orderField . ' + 1'), $updateConditions);
		} else if($move === 'last') {
			$count = $model->find('count', array('conditions' => $conditions));
			$model->updateAll(array($orderField => $count), $idConditions);
			$updateConditions[$orderField . ' >'] = $order;
			$model->updateAll(array($orderField => $orderField . ' - 1'), $updateConditions);
		}
	}
	
	public function fixOrder(Model $model, $conditions) {
		$count = $model->find('count', array(
			'conditions' => $conditions,
			'group' => array($model->alias . '.order HAVING COUNT(1) > 1')
		));
		if($count > 0) {
			$list = $model->find('list', array(
				'conditions' => $conditions,
				'order' => array($model->alias . '.order')
			));
			$order = 1;
			foreach($list as $id => $name) {
				$model->id = $id;
				$model->saveField('order', $order++);
			}
		}
	}
	
	public function getOption(Model $model, $id) {
		$alias = $model->alias;
		$data = $model->find('first', array(
			'recursive' => 0,
			'fields' => array(
				$alias . '.*',
				'ModifiedUser.first_name',
				'ModifiedUser.last_name',
				'CreatedUser.first_name',
				'CreatedUser.last_name'
			),
			'joins' => array(
				array(
					'table' => 'security_users',
					'alias' => 'ModifiedUser',
					'type' => 'LEFT',
					'conditions' => array('ModifiedUser.id = ' . $alias . '.modified_user_id')
				),
				array(
					'table' => 'security_users',
					'alias' => 'CreatedUser',
					'type' => 'LEFT',
					'conditions' => array('CreatedUser.id = ' . $alias . '.created_user_id')
				)
			),
			'conditions' => array($alias.'.id' => $id)
		));
		
		if($data) {
			$data[$alias]['modified_user'] = trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']);
			$data[$alias]['created_user'] = trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']);
		}
		return $data;
	}
	
	public function getAllOptions(Model $model, $conditions) {
		$data = $model->find('all', array(
			'recursive' => 0,
			'conditions' => $conditions,
			'order' => array($model->alias . '.order')
		));
		return $data;
	}
	
	public function setOptionFields(Model $model, $fields, $overwrite=false) {
		if(!$overwrite) {
			$this->optionFields = array_merge($this->optionFields, $fields);
		} else {
			$this->optionFields = $fields;
		}
	}
	
	public function getOptionFields(Model $model) {
		$fields = $this->optionFields;
		foreach($fields['fields'] as $key => $field) {
			if($field['field'] === 'visible' && $field['type'] === 'select') {
				$fields['fields'][$key]['options'] = array(0 => __('No'), 1 => __('Yes'));
			}
		}
		$fields['model'] = $model->alias;
		return $fields;
	}
}

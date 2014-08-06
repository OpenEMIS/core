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
	public function setup(Model $model, $settings = array()) {
		$validate = array(
			'name' => array(
				'ruleRequired' => array(
					'rule' => 'notEmpty',
					'required' => true,
					'message' => 'Please enter a valid Option'
				)
			)
		);
		
		$schema = $model->schema();
		foreach($validate as $name => $rule) {
			if(!array_key_exists($name, $model->validate) && array_key_exists($name, $schema)) {
				$model->validate[$name] = $rule;
			}
		}
	}
	
	public function reorder(Model $model, $data, $conditions=array()) {
		$id = $data[$model->alias]['id'];
		$idField = $model->alias . '.id';
		$orderField = $model->alias . '.order';
		$move = $data[$model->alias]['move'];
		$order = $model->field('order', array('id' => $id));
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
	
	public function beforeSave(Model $model, $options = array()) {
		$schema = $model->schema();
		$data = current($model->data);
		
		if(isset($schema['default']) && isset($data['default'])) {
			$conditionId = $model->getConditionId();
			$default = $data['default'];
			if($default == 1) {
				$model->updateAll(
					array($model->alias.'.default' => 0),
					array($model->alias.'.'.$conditionId => $data[$conditionId])
				);
			}
		}
		return true;
	}
	
	public function getConditionId(Model $model) {
		return 'field_option_id';
	}
	
	public function getCustomFieldTypes(Model $model) {
		$types = array(
			1 => __('Label'),
			2 => __('Text'),
			3 => __('Dropdown'),
			4 => __('Multiple'),
			5 => __('Textarea')
		);
		return $types;
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
		$fields = $model->getFields();
		
		if (array_key_exists('order', $fields)) {
			$fields['order']['type'] = 'hidden';
			$fields['order']['default'] = 0;
		}
		if (array_key_exists('visible', $fields)) {
			$fields['visible']['labelKey'] = 'FieldOption';
			$fields['visible']['type'] = 'select';
			$fields['visible']['default'] = 1;
			$fields['visible']['options'] = array(1 => __('Yes'), 0 => __('No'));
		}
		if (array_key_exists('default', $fields)) {
			$fields['default']['default'] = 0;
			$fields['default']['type'] = 'select';
			$fields['default']['options'] = array(1 => __('Yes'), 0 => __('No'));
		}
		if (array_key_exists('editable', $fields)) {
			$fields['editable']['visible'] = false;
		}
		if (array_key_exists('field_option_id', $fields)) {
			$fields['field_option_id']['type'] = 'hidden';
		}
		if (array_key_exists('international_code', $fields)) {
			$fields['international_code']['labelKey'] = 'general';
			$fields['international_code']['visible'] = array('index' => true, 'view' => true, 'edit' => true);
		}
		if (array_key_exists('national_code', $fields)) {
			$fields['national_code']['labelKey'] = 'general';
			$fields['national_code']['visible'] = array('index' => true, 'view' => true, 'edit' => true);
		}
		$model->fields = $fields;
		return $fields;
	}
	
	public function getRender(Model $model) {
		return array();
	}
	
	public function postAdd($controller) {
		return false;
	}
	
	public function postEdit($controller) {
		return false;
	}
}

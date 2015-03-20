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

class ReorderBehavior extends ModelBehavior {
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array) $settings);
	}

	public function moveOrder(Model $model, $data, $conditions=array()) {
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
		$count = $model->find('count', array('conditions' => $conditions));
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

	public function afterSave(Model $model, $created, $options = array()) {
		// retain ordering of those with valid order value, new record and records with order = 0 will be at the bottom
		if ($created) {
			if ($this->settings[$model->alias]['parentKey']) {
				$parentKey = $this->settings[$model->alias]['parentKey'];
				$parentId = $model->data[$model->alias][$parentKey];
				$conditions = array( $parentKey => $parentId );
			} else {
				$conditions = array();
			}
			$listWithOrders = $model->find('list', array(
				'conditions' => array_merge($conditions, array( 'order !=' => 0)),
				'order' => array($model->alias . '.order')
			));
			$listWithoutOrders = $model->find('list', array(
				'conditions' => array_merge($conditions, array( 'order' => 0)),
			));
			$order = 1;
			foreach($listWithOrders as $id => $name) {
				$model->id = $id;
				$model->saveField('order', $order++);
			}
			foreach($listWithoutOrders as $id => $name) {
				$model->id = $id;
				$model->saveField('order', $order++);
			}
		}
	}
}

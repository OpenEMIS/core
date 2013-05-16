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


class TrackHistoryBehavior extends ModelBehavior {
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array(
				'historyTable' => $Model->alias.'History'
			);
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}
	
	public function beforeSave(Model $model) {
		if($model->id > 0) {
			$this->insertHistory($model, $model->id);
		}
		return true;
	}
	
	public function beforeDelete(Model $model, $cascade = true) {
		if($model->id > 0) {
			$this->insertHistory($model, $model->id);
		}
		return true;
	}
	
	public function insertHistory(Model $model, $id) {
		$modelName = $model->alias;
		$foreignKey = Inflector::underscore($modelName . 'Id');
		$conditions = array('recursive' => 0, 'conditions' => array($modelName.'.id' => $id));
		$history = $model->find('first', $conditions);
		$history[$modelName]['created_user_id'] = CakeSession::read('Auth.User.id');
		$history[$modelName][$foreignKey] = $id;
		unset($history[$modelName]['id']);
		unset($history[$modelName]['modified']);
		unset($history[$modelName]['modified_user_id']);
		unset($history[$modelName]['created']);
		try {				
			$historyModel = ClassRegistry::init($this->settings[$modelName]['historyTable']);
			$historyModel->create();
			$historyModel->save($history[$modelName]);
		} catch(Exception $ex) {
			$this->log($ex->getMessage(), 'error');
		}
	}
}
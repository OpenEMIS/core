<?php

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
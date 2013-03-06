<?php

class CascadeDeleteBehavior extends ModelBehavior {
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array('cascade' => array());
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}
	
	public function beforeDelete(Model $model, $cascade = true) {
		$cascadeList = $this->settings[$model->alias]['cascade'];
		$foreignKey = Inflector::underscore($model->alias . 'Id');
		$continue = true;
		try {
			foreach($cascadeList as $table) {
				$cascadeModel = ClassRegistry::init($table);
				$this->log(sprintf('Deleting %s of %s (id: %s)', $table, $model->alias, $model->id) , 'debug');
				$cascadeModel->deleteAll(array($foreignKey => $model->id), true, true);
			}
		} catch(Exception $ex) {
			$this->log($ex->getMessage(), 'error');
			$continue = false;
		}
		return $continue;
	}
}
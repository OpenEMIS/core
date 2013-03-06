<?php

class NamedBehavior extends ModelBehavior {
	/*
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array(
				// add default settings here
			);
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}
	*/
	
	public function removeUnnamed(Model $model, &$data) {
		$list = $data[$model->alias];
		foreach($list as $key => $obj) {
			if(isset($obj['name']) && strlen(trim($obj['name'])) == 0) {
				unset($data[$model->alias][$key]);
			}
		}
	}
}
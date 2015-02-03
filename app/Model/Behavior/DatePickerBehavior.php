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

class DatePickerBehavior extends ModelBehavior {
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}
	
	public function beforeSave(Model $model, $options = array()) {
		$format = 'Y-m-d';
		$fields = $this->settings[$model->alias];
		foreach($fields as $field) {
			if(isset($model->data[$model->alias][$field]) && !empty($model->data[$model->alias][$field])) {
				$value = $model->data[$model->alias][$field];
				$model->data[$model->alias][$field] = date($format, strtotime($value));
			}
		}
		return parent::beforeSave($model, $options);
	}
	
	public function afterFind(Model $model, $results, $primary = false) {
		$format = 'd-m-Y';
		$fields = $this->settings[$model->alias];
		foreach($results as $i => $result) {
			foreach($fields as $field) {
				if(isset($result[$model->alias][$field]) && !empty($result[$model->alias][$field]) && ($result[$model->alias][$field] !== '0000-00-00')) {
					$value = $result[$model->alias][$field];
					$results[$i][$model->alias][$field] = date($format, strtotime($value));
				}else{
					$results[$i][$model->alias][$field] = '';
				}
			}
		}
		return $results;
	}

	public function getDate(Model $model, $obj, $field, $format='Y-m-d') {
		$date = $obj[$field];
		return date($format, strtotime($date));
	}
}
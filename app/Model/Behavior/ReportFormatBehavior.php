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

class ReportFormatBehavior extends ModelBehavior {
	public $formatMapping = array(
		'csv' => 'generateCSV'
	);
	
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
		if(!method_exists($Model, 'reportsGetData')) {
			pr('reportsGetData must be implemented.');
		}
	}
	
	public function getSupportedFormats(Model $model) {
		$formats = $this->settings[$model->alias]['supportedFormats'];
		$mapping = $this->formatMapping;
		
		foreach($mapping as $key => $function) {
			if(!in_array($key, $formats)) {
				unset($mapping[$key]);
			}
		}
		return $mapping;
	}
	
	public function generateCSV(Model $model, $index) {
		$data = $model->reportsGetData($index);
		return $data;
	}
	
	public function getFormatFunction(Model $model, $format) {
		if(array_key_exists($format, $this->formatMapping)) {
			return $this->formatMapping[$format];
		}
		return false;
	}
}

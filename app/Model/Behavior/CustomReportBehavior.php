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

class CustomReportBehavior extends ModelBehavior {
	public $fields = array('id', 'order', 'modified_user_id', 'modified', 'created_user_id', 'created');
	
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
			if(isset($settings['_default'])) {
				$this->fields = array_merge($this->fields, $settings['_default']);
			}
			if(!isset($settings[$Model->alias])) {
				$settings[$Model->alias] = $this->fields;
			} else {
				$settings[$Model->alias] = array_merge($settings[$Model->alias], $this->fields);
			}
			$this->settings[$Model->alias] = $settings;
		}
		if (isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
		}
	}
	
	public function getExcludedFields(Model $Model, $associatedModel=false) {
		$fields = $this->fields;
		$settings = $this->settings[$Model->alias];
		if(!$associatedModel) { // parent model
			$fields = $settings[$Model->alias];
		} else {
			if(isset($settings['belongsTo'])) {
				$belongsTo = $settings['belongsTo'];
				if(is_array($belongsTo) && array_key_exists($associatedModel, $belongsTo)) {
					$fields = array_merge($belongsTo[$associatedModel], $fields);
				}
			}
		}
		return $fields;
	}
}

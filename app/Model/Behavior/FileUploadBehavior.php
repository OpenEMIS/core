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

class FileUploadBehavior extends ModelBehavior {
	public function setup(Model $model, $settings = array()) {
		$validate = array(
			'ruleExtension' => array(
				'rule' => array('extension', array('gif', 'jpeg', 'png', 'jpg')),
				'message' => __('Please upload a valid file type.')
			),
			'ruleFileSize' => array(
				'rule' => array('fileSize', '<=')
			)/*,
			'ruleUploadError_1' => array(
				'rule' => array('uploadError', UPLOAD_ERR_FORM_SIZE),
				'message' => 'File Size exceeded'
			)*/
		);
		
		if (!isset($this->settings[$model->alias])) {
			$this->settings[$model->alias] = array();
		}
		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array)$settings);
		if(!empty($this->settings[$model->alias])) {
			$fields = $this->settings[$model->alias];
			foreach($fields as $field) {
				$fieldName = $field['content'];
				if(!isset($model->validate[$fieldName])) {
					$size = isset($field['size']) ? $field['size'] : '1MB';
					$validate['ruleFileSize']['rule'][] = $size;
					$validate['ruleFileSize']['message'] = __('File size must be less than ') . $size;
					$model->validate[$fieldName] = $validate;
				}
			}
		}
	}
	
	public function beforeValidate(Model $model, $options = array()) {
		$fields = $this->settings[$model->alias];
		$alias = $model->alias;
		foreach($fields as $field) {
			$fieldName = $field['content'];
			if(!empty($model->data[$alias][$fieldName])) {
				$file = $model->data[$alias][$fieldName];
				if($file['error'] == 4 && $field['allowEmpty'] == true) {
					unset($model->data[$alias][$fieldName]);
				}
			} else { // if the file is null, remove validation
				unset($model->validate[$fieldName]);
				if(isset($field['name'])) {
					$model->data[$alias][$field['name']] = null;
				}
				$model->data[$alias][$fieldName] = null;
				return true;
			}
		}
		return parent::beforeValidate($model, $options);
	}
	
	public function beforeSave(Model $model, $options = array()) {
		$fields = $this->settings[$model->alias];
		$alias = $model->alias;
		foreach($fields as $field) {
			$fieldName = $field['content'];
			if(!empty($model->data[$alias][$fieldName])) {
				$file = $model->data[$alias][$fieldName];
				if($file['error'] == 0) {
					if(isset($field['name'])) {
						$model->data[$alias][$field['name']] = $file['name'];
					}
					$model->data[$alias][$fieldName] = file_get_contents($file['tmp_name']);
				}
			}
		}
		return parent::beforeSave($model, $options);
	}
}

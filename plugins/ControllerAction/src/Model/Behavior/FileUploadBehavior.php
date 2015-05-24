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

namespace ControllerAction\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class FileUploadBehavior extends Behavior {

	protected $_defaultConfig = [
		'name' => 'file_name',
		'content' => 'file_content',
		'size' => '1MB',
		'allowEmpty' => false,
		'useDefaultName' => true
	];

	public function initialize(array $config) {
		$this->_defaultConfig = array_merge($this->_defaultConfig, $config);
		//pr($this->_defaultConfig);
		/*
		$validate = array(
			'ruleExtension' => array(
				'rule' => array('extension', array('gif', 'jpeg', 'png', 'jpg')),
				'message' => __('Please upload a valid file type.')
			),
			'ruleFileSize' => array(
				'rule' => array('fileSize', '<=')
			)
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
		*/
	}
	
	/*
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
	*/
	
	public function beforeSave(Event $event, Entity $entity) {
		$config = $this->_defaultConfig;

		$fileNameField = $config['name'];
		$fileContentField = $config['content'];

		$file = $entity->$fileContentField;
		$entity->$fileContentField =  null;
		if ($file['error'] == 0) { // success
			if ($config['useDefaultName']) {
				$entity->$fileNameField = $file['name'];
			} else {
				$pathInfo = pathinfo($file['name']);
				$entity->$fileNameField = uniqid() . '.' . $pathInfo['extension'];
			}
			$entity->$fileContentField = file_get_contents($file['tmp_name']);
		}
	}
}

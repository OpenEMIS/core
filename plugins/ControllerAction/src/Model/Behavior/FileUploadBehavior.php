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
use Cake\Validation\Validator;

class FileUploadBehavior extends Behavior {
	protected $_defaultConfig = [
		'name' => 'file_name',
		'content' => 'file_content',
		'size' => '1MB',
		'allowEmpty' => false
	];

	public $fileImagesMap = array(
		'jpeg'	=> 'image/jpeg',
		'jpg'	=> 'image/jpeg',
		'gif'	=> 'image/gif',
		'png'	=> 'image/png'
		// 'jpeg'=>'image/pjpeg',
		// 'jpeg'=>'image/x-png'
	);

	public $fileDocumentsMap = array(
		'rtf' 	=> 'text/rtf',
		'txt' 	=> 'text/plain',
		'csv' 	=> 'text/csv',
		'pdf' 	=> 'application/pdf',
		'ppt' 	=> 'application/vnd.ms-powerpoint',
		'pptx' 	=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'doc' 	=> 'application/msword',
		'docx' 	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls' 	=> 'application/vnd.ms-excel',
		'xlsx' 	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'zip' 	=> 'application/zip',
	);

	public $fileTypesMap = array();

	private $_validator;

	public function initialize(array $config) {
		$this->_defaultConfig = array_merge($this->_defaultConfig, $config);
		$this->config($this->_defaultConfig);

		$this->fileTypesMap = array_merge($this->fileImagesMap, $this->fileDocumentsMap);

		// $this->_validator = new Validator();

		// $this->_validator
		//     ->requirePresence($this->_defaultConfig['name'])
		//     ->notEmpty($this->_defaultConfig['name'], 'Please upload a file');
		    // ->add('title', [
		    //     'length' => [
		    //         'rule' => ['minLength', 10],
		    //         'message' => 'Titles need to be at least 10 characters long',
		    //     ]
		    // ])
		    // ->allowEmpty('published')
		    // ->add('published', 'boolean', [
		    //     'rule' => 'boolean'
		    // ])
		    // ->requirePresence('body')
		    // ->add('body', 'length', [
		    //     'rule' => ['minLength', 50],
		    //     'message' => 'Articles must have a substantial body.'
		    // ]);
		
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
	
	// public function validationDefault(Validator $validator) {
	// 	$validator
	// 	->requirePresence('name')
	// 	->notEmpty('name', 'Please enter a name.')
 //    	->add('name', [
 //    		'unique' => [
	// 	        'rule' => ['validateUnique', ['scope' => 'survey_module_id']],
	// 	        'provider' => 'table',
	// 	        'message' => 'This name is already exists in the system'
	// 	    ]
	//     ])
	//     ->requirePresence('survey_module_id')
	// 	->notEmpty('survey_module_id', 'Please select a module.')
	//     ;

	// 	return $validator;
	// }


	public function getFileTypeForView($filename) {
		$exp = explode('.', $filename);
		$ext = $exp[count($exp) - 1];
		if (array_key_exists($ext, $this->fileImagesMap)) {
			return 'Image';
		} elseif (array_key_exists($ext, $this->fileDocumentsMap)) {
			return 'Document';
		} else {
			return 'Unknown';
		}
	}

	public function getFileType($ext) {
		if (array_key_exists($ext, $this->fileTypesMap)) {
			return $this->fileTypesMap[$ext];
		} else {
			return false;
		}
	}

	/**
	 * Cake V3 returns binary column type data as php resource id instead of the whole file for better performance.
	 * The current work-around is to use native php normal stream functions to read the contents incrementally or all at once.
	 * @link https://groups.google.com/forum/#!topic/cake-php/rgaHYh2iWwU
	 * 
	 * @param  php_resource_file_handler $phpResourceFile acquired from table entity
	 * @return binary/boolean          	 				  returns the binary file if resource exists and returns boolean false if not exists
	 */
	public function getActualFile($phpResourceFile) {
		$file = ''; 
		while (!feof($phpResourceFile)) {
			$file .= fread($phpResourceFile, 8192); 
		} 
		fclose($phpResourceFile);

		return $file;
	}

	/**
	 * @todo if user wants the file or image to be removed, it should be emptied from the record.
	 */
	public function beforeSave(Event $event, Entity $entity) {

		$fileNameField = $this->config('name');
		$fileContentField = $this->config('content');

		$file = $entity->$fileContentField;
		
		$proceed = false;
		if ($entity->isNew()) {
			$proceed = true;
		} elseif (!$entity->isNew() && !empty($file) && !empty($file['tmp_name'])) {
			$proceed = true;
		} elseif(empty($file)) {
			/**
			 * if user wants the file or image to be removed, it should be emptied from the record.
			 */
			$entity->$fileNameField =  null;
			$entity->$fileContentField =  null;
		}

		if (!empty($file)) {
			if ($proceed) {
				if ($file['error'] == 0) { // success
					if ($this->config('useDefaultName')) {
						$entity->$fileNameField = $file['name'];
					} else {
						$pathInfo = pathinfo($file['name']);
						$entity->$fileNameField = uniqid() . '.' . $pathInfo['extension'];
					}					
					$entity->$fileContentField = file_get_contents($file['tmp_name']);
				}
			} else {
				/**
				 * unset this two entities so that no changes will be made on the uploaded record
				 */
				$entity->unsetProperty($fileNameField);
				$entity->unsetProperty($fileContentField);
			}
		}
	}
}

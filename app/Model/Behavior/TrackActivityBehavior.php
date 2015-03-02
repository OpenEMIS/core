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

class TrackActivityBehavior extends ModelBehavior {
	public $exclude = array('id', 'modified_user_id', 'modified', 'created_user_id', 'created');
	public $excludeType = array('binary');

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array(
				'target' => '',
				'key' => '',
				'session' => ''
			);
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}
	
	public function beforeSave(Model $model, $options = array()) {
		if (!empty($model->id)) { // edit operation
			$model->recursive = -1;
		    $oldData = $model->findById($model->id);
		    $data = $model->data;
		    $schema = $model->schema();
		    
		    $ActivityModel = ClassRegistry::init($this->settings[$model->alias]['target']);
		    $obj = array(
		    	'model' => $model->alias,
		    	'model_reference' => $model->id,
		    	$this->settings[$model->alias]['key'] => CakeSession::read($this->settings[$model->alias]['session'])
		    );

			if ($oldData) {
				foreach ($oldData[$model->alias] as $field => $value) {
			    	if (!in_array($field, $this->exclude) && array_key_exists($field, $data[$model->alias])) {
			    		if (array_key_exists($field, $schema) && !in_array($schema[$field]['type'], $this->excludeType)) {
			    			if ($oldData[$model->alias][$field] != $data[$model->alias][$field]) {
			    			
				    			$relatedModelName = Inflector::camelize(str_replace('_id', '', $field));
								$relatedModel = $model->{$relatedModelName};

								// check if related model's table is actually field_option_values by reading its useTable instance
								if (is_object($relatedModel) && $relatedModel->useTable=='field_option_values') {

									// foreignKey value has to be related model's name instead of field_option_values_id which does not exists in $model's column
									$relatedModel->hasMany[$model->alias]['foreignKey'] = $field;
								}
		
								$obj['field'] = $field;
								
								$allData = array('old'=>$oldData, 'new'=>$data);
								foreach ($allData as $allDataKey=>$allDataValue) {

									// if related model exists, get related data
									if (is_object($relatedModel)) {
										$relatedData = $relatedModel->findById($allDataValue[$model->alias][$field]);

										// if related data exists, get the name value instead of its id number.
										if ($relatedData && isset($relatedData[$relatedModelName]['name'])) {
											$obj[$allDataKey.'_value'] = $relatedData[$relatedModelName]['name'];
										} else {

											// Else set the value as an empty space since old_value and new_value column do not except null
											$obj[$allDataKey.'_value'] = ($allDataValue[$model->alias][$field]) ? $allDataValue[$model->alias][$field] : ' ';
										}
									} else {
										// log if relation is missing
										$this->log($field." is not defined in belongsTo ".$model, 'debug');
										$obj[$allDataKey.'_value'] = ($allDataValue[$model->alias][$field]) ? $allDataValue[$model->alias][$field] : ' ';
									}
								}

								$obj['operation'] = 'edit';
								$ActivityModel->create();
								$ActivityModel->save($obj);

				            }
			    		}
			    	}
			    }
			}

		}
		return true;
	}

}

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
		    $data = $model->data[$model->alias];
		    $schema = $model->schema();
		    
		    $ActivityModel = ClassRegistry::init($this->settings[$model->alias]['target']);
		    $obj = array(
		    	'model' => $model->alias,
		    	'model_reference' => $model->id,
		    	$this->settings[$model->alias]['key'] => CakeSession::read($this->settings[$model->alias]['session'])
		    );

			if ($oldData) {
			    foreach ($oldData[$model->alias] as $field => $value) {
			    	if (!in_array($field, $this->exclude) && array_key_exists($field, $data)) {
			    		if (array_key_exists($field, $schema) && !in_array($schema[$field]['type'], $this->excludeType)) {
			    			if ($oldData[$model->alias][$field] != $data[$field]) {
								$obj['field'] = $field;
								$obj['old_value'] = $oldData[$model->alias][$field];
								$obj['new_value'] = $data[$field];
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

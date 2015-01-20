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

App::uses('AppModel', 'Model');

class FieldOptionValue extends AppModel {
	public $actsAs = array('FieldOption');
	public $belongsTo = array(
		'FieldOption',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);
	public $parent = null;
	
	public function setParent($obj) {
		$this->parent = $obj;
	}
	
	public function getModel($obj=null) {
		$model = $this;
		if(is_null($obj)) {
			$obj = $this->parent;
		}
		if(!is_null($obj['params'])) {
			$params = (array) json_decode($obj['params']);
			if(isset($params['model'])) {
				$model = ClassRegistry::init($params['model']);
			}
		}
		return $model;
	}
	
	public function getAllValues($conditions=array()) {
		$model = $this->getModel();
		if($model->alias === $this->alias) {
			$obj = $this->parent;
			$conditions['field_option_id'] = $obj['id'];
		}
		$data = $model->getAllOptions($conditions);
		return $data;
	}
	
	public function getValue($id) {
		$model = $this->getModel();
		$model->recursive = 0;
		$data = $model->findById($id);
		return $data;
	}
	
	public function getDefaultValue() {
		$model = $this->getModel();
		$model->recursive = 0;
		
		$data = array();
		if ($model->alias == $this->alias) {

			$FieldOption = ClassRegistry::init('FieldOption');
			$fieldOptionId = $FieldOption->find('first', 
				array(
					'recursive' => -1,
					'fields' => array('id'),
					'conditions' => array('FieldOption.code' => $model->alias)
				)
			);

			$data = $model->find('first', array(
				'fields' => array('id'),
				'conditions' => array($model->alias.'.field_option_id' => $fieldOptionId['FieldOption']['id']),
				'order' => array($model->alias.'.default DESC')
			));
		} else {
			$data = $model->find('first', array(
				'fields' => array('id'),
				'order' => array($model->alias.'.default DESC')
			));
		}
		if(empty($data)){
			return 0;
		}
		return $data[$model->alias]['id'];
	}
	
	public function saveValue($data) {	
		$model = $this->getModel();
		
		if($model->alias === $this->alias) {
			$obj = $this->parent;
			$data[$model->alias]['field_option_id'] = $obj['id'];
		}
		return $model->save($data);
	}
	
	public function getValueFields($controller=null) {
		$model = $this->getModel();
		$data = $model->getOptionFields($controller);
		return $data;
	}
	
	public function getHeader() {
		$header = $this->parent['parent'];
		$header .= (count($header) > 0 ? ' - ' : '') . $this->parent['name'];
		return $header;
	}
	
	public function getSubOptions() {
		$model = $this->getModel();
		if($model->alias !== $this->alias && method_exists($model, 'getSubOptions')) {
			return $model->getSubOptions();
		}
		return false;
	}
	
	public function getFirstSubOptionKey($suboptions) {
		$key = 0;
		if(!empty($suboptions)) {
			$key = key($suboptions);
			if(is_array($suboptions[$key]) && !empty($suboptions[$key])) {
				$key = key($suboptions[$key]);
			}
		}
		return $key;
	}
        
	public function getList($customOptions=array()) {
		$alias = $this->alias;

		$options = array(
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'field_options',
					'alias' => 'FieldOption',
					'conditions' => array(
						'FieldOption.id = ' . $alias . '.field_option_id',
						"FieldOption.code = '" . $alias . "'"
					)
				)
			),
			'order' => array($alias.'.order')
		);

		$options['conditions'] = array();

		if (array_key_exists('visibleOnly', $customOptions)) {
			$options['conditions'][$alias.'.visible >'] = 0;
		}

		if (array_key_exists('conditions', $customOptions)) {
			$options['conditions'] = array_merge($options['conditions'], $customOptions['conditions']);
		}
		
		$optionData = $this->find('all',$options);

		$result = array();
		if (array_key_exists('listOnly', $customOptions) && $customOptions['listOnly']) {
			foreach ($optionData as $key => $value) {
				$name = __($value[$alias]['name']);
				$result[$value[$alias]['id']] = $name;
			}
		} else {
			foreach ($optionData as $key => $value) {
				$name = __($value[$alias]['name']);
				array_push($result, 
					array(
						'name' => $name, 
						'value' => $value[$alias]['id'],
						'obsolete' => ($value[$alias]['visible']!='0')?false:true,
						'selected' => ($value[$alias]['default']!='0')?true:false
					)
				);
			}
			if (array_key_exists('value', $customOptions)) {
				$value = $customOptions['value'];

				if (is_array($value)) {
					foreach ($result as $okey => $ovalue) {
					if ($ovalue['obsolete'] == '1' && !in_array($ovalue['value'], $value)) {
						unset($result[$okey]);
					}
				}
				} else {
					foreach ($result as $okey => $ovalue) {
						if ($ovalue['obsolete'] == '1' && $ovalue['value']!=$value) {
							unset($result[$okey]);
						}
					}	
				}
			}
		}
		return $result;
	}
	
	public function getIdByName($name) {
		$alias = $this->alias;
		
		$result = $this->find('first', array(
			'conditions' => array($alias.'.name' => $name, 'FieldOption.code' => $alias)
		));
		
		return !empty($result) ? $result[$alias]['id'] : NULL;
	}
}
?>

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

class CensusGrid extends AppModel {
	public $actsAs = array('FieldOption');
	public $belongsTo = array(
		'InstitutionSiteType',
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
	public $hasMany = array('CensusGridXCategory','CensusGridYCategory');
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		)
	);
	
	public function getRender($controller) {
		$views = array('index', 'add', 'view', 'edit');
		$list = $this->InstitutionSiteType->getList();
		$typeOptions = $this->prepend($list, __('All'));
		
		$yesnoOptions = $controller->Option->get('yesno');
		$controller->set('yesnoOptions', $yesnoOptions);
		$controller->set('typeOptions', $typeOptions);
		
		if ($controller->action == 'view') {
			$data = $controller->viewVars['data'];
			$id = $data[$this->alias]['id'];
			$x = $this->CensusGridXCategory->findAllByCensusGridIdAndVisible($id, 1);
			$y = $this->CensusGridYCategory->findAllByCensusGridIdAndVisible($id, 1);
			foreach ($x as $obj) {
				$data['CensusGridXCategory'][] = $obj['CensusGridXCategory'];
			}
			foreach ($y as $obj) {
				$data['CensusGridYCategory'][] = $obj['CensusGridYCategory'];
			}
			$controller->set('data', $data);
		} else if ($controller->action == 'edit') {
			if ($controller->request->is('get')) {
				$data = $controller->request->data;
				$id = $data[$this->alias]['id'];
				
				$x = $this->CensusGridXCategory->findAllByCensusGridIdAndVisible($id, 1);
				$y = $this->CensusGridYCategory->findAllByCensusGridIdAndVisible($id, 1);
				foreach ($x as $obj) {
					$controller->request->data['CensusGridXCategory'][] = $obj['CensusGridXCategory'];
				}
				foreach ($y as $obj) {
					$controller->request->data['CensusGridYCategory'][] = $obj['CensusGridYCategory'];
				}
			}
		}
		
		return $views;
	}
	
	public function getOptionFields() {
		parent::getOptionFields();
		$list = $this->InstitutionSiteType->getList();
		$typeOptions = $this->prepend($list, __('All'));
		
		$this->fields['y_title']['visible'] = false; // not in use atm
		$this->fields['institution_site_type_id']['type'] = 'select';
		$this->fields['institution_site_type_id']['options'] = $typeOptions;
		$this->fields['institution_site_type_id']['labelKey'] = 'InstitutionSite';
		$this->fields['preview'] = array(
			'type' => 'element',
			'element' => '../FieldOption/CensusGrid/preview',
			'override' => true,
			'visible' => true
		);
		$this->setFieldOrder('preview', 1);
		$this->fields['columns'] = array(
			'type' => 'element',
			'element' => '../FieldOption/CensusGrid/columns',
			'visible' => true
		);
		$this->setFieldOrder('columns', 9);
		$this->fields['rows'] = array(
			'type' => 'element',
			'element' => '../FieldOption/CensusGrid/rows',
			'visible' => true
		);
		$this->setFieldOrder('rows', 10);
		return $this->fields;
	}
	
	public function postAdd($controller) {
		$selectedOption = $controller->params->pass[0];
		if (isset($controller->request->data['submit'])) {
			$submit = $controller->request->data['submit'];
			
			switch ($submit) {
				case 'CensusGridXCategory':
				case 'CensusGridYCategory':
					$obj = array('name' => '');
					if (!isset($controller->request->data[$submit])) {
						$controller->request->data[$submit] = array();
					}
					$obj['order'] = count($controller->request->data[$submit]);
					$controller->request->data[$submit][] = $obj;
					break;
					
				case __('Save'):
					$data = $controller->request->data;
					
					$models = array('CensusGridXCategory', 'CensusGridYCategory');
					// remove all records that doesn't have values
					foreach ($models as $m) {
						if (isset($data[$m])) {
							$x = $data[$m];
							foreach ($x as $i => $obj) {
								if (empty($obj['name'])) {
									unset($controller->request->data[$m][$i]);
								}
							}
						}
					}
					
					if ($this->saveAll($controller->request->data)) {
						$controller->Message->alert('general.add.success');
						return $controller->redirect(array('controller' => $controller->name, 'action' => 'view', $selectedOption, $this->getLastInsertID()));
					} else {
						$controller->Message->alert('general.add.failed');
					}
					break;
				
				default:
					break;
			}
		}
		return true;
	}
	
	public function postEdit($controller) {
		$selectedOption = $controller->params->pass[0];
		if (isset($controller->request->data['submit'])) {
			$submit = $controller->request->data['submit'];
			
			switch ($submit) {
				case 'CensusGridXCategory':
				case 'CensusGridYCategory':
					$obj = array('name' => '');
					if (!isset($controller->request->data[$submit])) {
						$controller->request->data[$submit] = array();
					}
					$obj['order'] = count($controller->request->data[$submit]);
					$controller->request->data[$submit][] = $obj;
					break;
					
				case __('Save'):
					$data = $controller->request->data;
					$id = $data[$this->alias]['id'];
					$models = array('CensusGridXCategory', 'CensusGridYCategory');
					foreach ($models as $m) {
						if (isset($data[$m])) {
							$x = $data[$m];
							foreach ($x as $i => $obj) {
								if (empty($obj['name'])) {
									unset($controller->request->data[$m][$i]);
								}
							}
						}
					}
					
					// set all visible to 0 and re-save
					foreach ($models as $model) {
						$this->{$model}->updateAll(
							array($model.'.visible' => 0),
							array($model.'.census_grid_id' => $id)
						);
					}
					
					if ($this->saveAll($controller->request->data)) {
						$controller->Message->alert('general.edit.success');
						return $controller->redirect(array('controller' => $controller->name, 'action' => 'view', $selectedOption, $id));
					} else {
						$controller->Message->alert('general.edit.failed');
					}
					break;
				
				default:
					break;
			}
		}
		return true;
	}
}

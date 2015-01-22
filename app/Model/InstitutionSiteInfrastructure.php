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

class InstitutionSiteInfrastructure extends AppModel {
	public $belongsTo = array(
		'InstitutionSite',
		'Infrastructure.InfrastructureLevel',
		'Infrastructure.InfrastructureType',
		'Infrastructure.InfrastructureOwnership',
		'Infrastructure.InfrastructureCondition',
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
	
	public $hasMany = array(
		'InstitutionSiteInfrastructureCustomValue'
	);
	
	public $validate = array(
		'code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Code'
			)
		),
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name'
			)
		),
		'infrastructure_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Type'
			)
		),
		'infrastructure_ownership_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Ownership'
			)
		),
		'year_acquired' => array(
			'ruleNotLater' => array(
				'rule' => array('compareYear', 'year_disposed'),
				'message' => 'Year Acquired cannot be later than Year Disposed'
			)
		)
	);
	
	public $actsAs = array(
		'ControllerAction2'
	);
	
	public function beforeAction() {
		parent::beforeAction();
	}
	
	public function compareYear($field = array(), $compareField = null) {
		$yearAcquired = $field['year_acquired'];
		$yearDisposed = $this->data[$this->alias][$compareField];
		return $yearAcquired <= $yearDisposed;
	}
	
	public function index($levelId=0) {
		$this->Navigation->addCrumb('Infrastructure');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$levelOptions = $this->InfrastructureLevel->getLevelOptions();
		
		if(!empty($levelOptions)){
			if ($levelId != 0) {
				if (!array_key_exists($levelId, $levelOptions)) {
					$levelId = key($levelOptions);
				}
			} else {
				$levelId = key($levelOptions);
			}
		}
		
		if(empty($levelOptions)){
			$this->Message->alert('InstitutionSiteInfrastructure.noLevel');
		}
		
		$level = $this->InfrastructureLevel->findById($levelId);
		if(!empty($level)){
			$levelName = $level['InfrastructureLevel']['name'];
		}else{
			$levelName = '';
		}
		$parentLevel = $this->InfrastructureLevel->getParentLevel($levelId);
		
		$data = $this->getInfrastructureData($levelId, $institutionSiteId);
		
		$this->setVar(compact('level', 'levelName', 'levelOptions', 'levelId', 'data', 'parentLevel'));
	}
	
	public function add($levelId=0) {
		$this->Navigation->addCrumb('Add Infrastructure');
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$level = $this->InfrastructureLevel->findById($levelId);
		
		if(empty($level)){
			return $this->redirect(array('action' => 'InstitutionSiteInfrastructure', 'index'));
		}
		
		$parentLevel = $this->InfrastructureLevel->getParentLevel($levelId);
		if(!empty($parentLevel)){
			$parentInfraOptions = $this->infrastructureOptionsByLevel($parentLevel['InfrastructureLevel']['id'], $institutionSiteId);
		}else{
			$parentInfraOptions = array();
		}
		
		$typeOptions = $this->InfrastructureType->getTypeOptionsByLevel($levelId);
		$yearOptions = $this->controller->DateTime->yearOptionsByConfig();
		$yearDisposedOptions = $this->controller->DateTime->yearOptionsByConfig(true);
		$currentYear = Date('Y');
		$ownershipOptions = $this->InfrastructureOwnership->getList(1);
		$conditionOptions = $this->InfrastructureCondition->getList(1);
		
		// custom fields start
		$data = ClassRegistry::init('Infrastructure.InfrastructureCustomField')->getCustomFields($levelId);
		$dataValues = array();
		//pr($data);
		$model = 'InfrastructureCustomField';
		$modelOption = 'InfrastructureCustomFieldOption';
		$modelValue = 'InstitutionSiteInfrastructureCustomValue';
		$modelRow = '';
		$modelColumn = '';
		$modelCell = '';
		$action = 'edit'; // for survey
		$viewType = 'form'; // for survey and infrastructure, form/list
		$pageType = 'form'; // for infrastructure, form/view
		// custom fields end
		
		$this->setVar(compact('levelId', 'level', 'parentLevel', 'parentInfraOptions', 'typeOptions', 'yearOptions', 'yearDisposedOptions', 'currentYear', 'ownershipOptions', 'conditionOptions'));
		
		if($this->request->is(array('post', 'put'))) {
			//$postData = $this->request->data['InstitutionSiteInfrastructure'];
			//$postData['institution_site_id'] = $institutionSiteId;
			//$postData['infrastructure_level_id'] = $levelId;

			$postData = $this->InstitutionSiteInfrastructureCustomValue->prepareDataBeforeSave($this->request->data);
			//pr($postData);die;
			if ($this->saveAll($postData)) {	
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => 'InstitutionSiteInfrastructure', 'index', $levelId));
			}else{
				$dataValues = $this->prepareCustomFieldsDataValues($postData);
				$this->request->data = $postData;
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.add.failed');
			}
		}
		
		$this->setVar(compact('data', 'dataValues', 'model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action', 'viewType', 'pageType'));
	}
	
	public function view($id=0) {
		$this->Navigation->addCrumb('View Infrastructure Details');
		
		$commonFieldsData = $this->getInfrastructureWithParent($id);
		
		if (!empty($commonFieldsData)) {
			$this->Session->write($this->alias.'.id', $id);
			
			$parents = array();
			$this->getParents($id, $parents);
			$parentsInOrder = array_reverse($parents);
			
			$levelId = $commonFieldsData['InfrastructureLevel']['id'];
			
			$this->setVar(compact('id', 'commonFieldsData', 'levelId', 'parentsInOrder'));
			
			// custom fields start
			$data = ClassRegistry::init('Infrastructure.InfrastructureCustomField')->getCustomFields($levelId);
			$dataValues = $this->getCustomFieldsValues($id);
			//pr($data);
			//pr($dataValues);
			$model = 'InfrastructureCustomField';
			$modelOption = 'InfrastructureCustomFieldOption';
			$modelValue = 'InstitutionSiteInfrastructureCustomValue';
			$modelRow = '';
			$modelColumn = '';
			$modelCell = '';
			$action = 'edit'; // for survey
			$viewType = 'form'; // for survey and infrastructure, form/list
			$pageType = 'view'; // for infrastructure, form/view

			$this->setVar(compact('data', 'dataValues', 'model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action', 'viewType', 'pageType'));
			// custom fields end
		} else {
			$this->Message->alert('general.notExists');
			$this->redirect(array('action' => $this->_action));
		}
	}

	public function edit($id=0) {
		$this->Navigation->addCrumb('Edit Infrastructure Details');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$commonFieldsData = $this->getInfrastructureWithParent($id);

		if (!empty($commonFieldsData)) {
			$levelId = $commonFieldsData['InfrastructureLevel']['id'];
			
			$parentLevel = $this->InfrastructureLevel->getParentLevel($commonFieldsData['InfrastructureLevel']['id']);
			if(!empty($parentLevel)){
				$parentInfraOptions = $this->infrastructureOptionsByLevel($parentLevel['InfrastructureLevel']['id'], $institutionSiteId);
			}else{
				$parentInfraOptions = array();
			}

			$typeOptions = $this->InfrastructureType->getTypeOptionsByLevel($commonFieldsData['InfrastructureLevel']['id']);
			$yearOptions = $this->controller->DateTime->yearOptionsByConfig();
			$yearDisposedOptions = $this->controller->DateTime->yearOptionsByConfig(true);
			$currentYear = Date('Y');
			$ownershipOptions = $this->InfrastructureOwnership->getList(1);
			$conditionOptions = $this->InfrastructureCondition->getList(1);
			
			// custom fields start
			$data = ClassRegistry::init('Infrastructure.InfrastructureCustomField')->getCustomFields($levelId);
			//pr($data);
			$dataValues = $this->getCustomFieldsValues($id);
			
			$model = 'InfrastructureCustomField';
			$modelOption = 'InfrastructureCustomFieldOption';
			$modelValue = 'InstitutionSiteInfrastructureCustomValue';
			$modelRow = '';
			$modelColumn = '';
			$modelCell = '';
			$action = 'edit'; // for survey
			$viewType = 'form'; // for survey and infrastructure, form/list
			$pageType = 'form'; // for infrastructure, form/view

			// custom fields end

			$this->setVar(compact('id', 'commonFieldsData', 'parentLevel', 'parentInfraOptions', 'typeOptions', 'yearOptions', 'yearDisposedOptions', 'currentYear', 'ownershipOptions', 'conditionOptions'));
			
			if($this->request->is('post') || $this->request->is('put')) {
				//$postData = $this->request->data['InstitutionSiteInfrastructure'];
				$postData = $this->InstitutionSiteInfrastructureCustomValue->prepareDataBeforeSave($this->request->data);
				//pr($postData);die;
				
				$dataSource = $this->getDataSource();
				$dataSource->begin();
				$this->InstitutionSiteInfrastructureCustomValue->deleteAll(array(
					'InstitutionSiteInfrastructureCustomValue.institution_site_infrastructure_id' => $id
				), false);
			
				if ($this->saveAll($postData)) {
					$dataSource->commit();
					
					$this->Message->alert('general.edit.success');
					$this->redirect(array('action' => $this->alias, 'view', $id));
				}else {
					$dataSource->rollback();
					
					$dataValues = $this->prepareCustomFieldsDataValues($postData);
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.edit.failed');
				}
			}else{
				$this->request->data = $commonFieldsData;
			}
			
			$this->setVar(compact('data', 'dataValues', 'model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action', 'viewType', 'pageType'));
		} else {
			$this->Message->alert('general.notExists');
			$this->redirect(array('action' => $this->alias, 'index'));
		}
	}

	public function remove() {
		$this->autoRender = false;
		$id = $this->Session->read($this->alias.'.id');
		$obj = $this->findById($id);
		
		$dataSource = $this->getDataSource();
		$dataSource->begin();
		$this->InstitutionSiteInfrastructureCustomValue->deleteAll(array(
			'InstitutionSiteInfrastructureCustomValue.institution_site_infrastructure_id' => $id
		), false);
		
		if($this->delete($id)) {
			$dataSource->commit();
			$this->Message->alert('general.delete.success');
		} else {
			$dataSource->rollback();
			$this->log($this->validationErrors, 'debug');
			$this->Message->alert('general.delete.failed');
		}
		$this->Session->delete($this->alias.'.id');
		$this->redirect(array('action' => $this->alias, 'index', $obj[$this->alias]['infrastructure_level_id']));
	}
	
	public function getInfrastructureData($levelId, $institutionSiteId){
		$parentLevel = $this->InfrastructureLevel->getParentLevel($levelId);
		if(!empty($parentLevel)){
			$fields = array('InstitutionSiteInfrastructure.*', 'InfrastructureType.*', 'Parent.name');
			
			$joins = array(
				array(
					'table' => 'institution_site_infrastructures',
					'alias' => 'Parent',
					'conditions' => array(
						'Parent.id = InstitutionSiteInfrastructure.parent_id'
					)
				)
			);
		}else{
			$fields = array('InstitutionSiteInfrastructure.*', 'InfrastructureType.*');
			$joins = array();
		}
		
		$data = $this->find('all', array(
			'fields' => $fields,
			'joins' => $joins,
			'conditions' => array(
				'InstitutionSiteInfrastructure.infrastructure_level_id' => $levelId,
				'InstitutionSiteInfrastructure.institution_site_id' => $institutionSiteId
			)
		));
		
		return $data;
	}
	
	public function infrastructureOptionsByLevel($levelId, $institutionSiteId){
		$data = $this->find('list', array(
			'conditions' => array(
				'InstitutionSiteInfrastructure.infrastructure_level_id' => $levelId,
				'InstitutionSiteInfrastructure.institution_site_id' => $institutionSiteId
			)
		));
		
		return $data;
	}
	
	public function getInfrastructureWithParent($id){
		$data = $this->find('first', array(
			'fields' => array('InstitutionSiteInfrastructure.*', 'InfrastructureLevel.*', 'InfrastructureOwnership.*', 'InfrastructureType.*', 'InfrastructureCondition.*', 'Parent.*', 'ModifiedUser.*', 'CreatedUser.*'),
			'joins' => array(
				array(
					'table' => 'institution_site_infrastructures',
					'alias' => 'Parent',
					'type' => 'LEFT',
					'conditions' => array(
						'Parent.id = InstitutionSiteInfrastructure.parent_id'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteInfrastructure.id' => $id
			)
		));
		
		return $data;
	}
	
	private function getParents($infrastructureId, &$arr){
		$data = $this->getInfrastructureWithParent($infrastructureId);
		
		if (!empty($data)) {
			$parentLevel = $this->InfrastructureLevel->getParentLevel($data['InfrastructureLevel']['id']);
			
			if(!empty($data['Parent']['name']) && !empty($parentLevel)){
				$parentLevelName = $parentLevel['InfrastructureLevel']['name'];
				
				$arr[] = array(
					'parentLevel' => $parentLevelName,
					'parent' => $data['Parent']['name']
				);
				
				if(!empty($data['Parent']['id'])){
					$this->getParents($data['Parent']['id'], $arr);
				}
			}
			
		}
	}
	
	public function getCustomFieldsValues($id) {
		$modelValue = 'InstitutionSiteInfrastructureCustomValue';

		$tmp = array();
		$this->contain(array($modelValue));
		$result = $this->findById($id);

		foreach ($result[$modelValue] as $key => $value) {
			$tmp[$value['infrastructure_custom_field_id']][] = $value;
		}

		return $tmp;
	}
	
	public function prepareCustomFieldsDataValues($result) {
		$modelValue = 'InstitutionSiteInfrastructureCustomValue';

		$tmp = array();
		if(isset($result[$modelValue])) {
			foreach ($result[$modelValue] as $key => $obj) {
				$customFieldId = $obj['infrastructure_custom_field_id'];
				$tmp[$customFieldId][] = $obj;
			}
		}

		return $tmp;
	}
}

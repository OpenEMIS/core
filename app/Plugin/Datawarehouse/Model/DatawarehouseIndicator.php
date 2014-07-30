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

class DatawarehouseIndicator extends DatawarehouseAppModel {
	public $actsAs = array('ControllerAction');

	public $paginateLimit = 25;

	public $belongsTo = array(
		'Denominator' => array(
            'className' => 'DatawarehouseIndicator',
            'foreignKey' => 'denominator'
        ),
		'DatawarehouseUnit',
		'DatawarehouseField',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);

	public $hasMany = array(
		'DatawarehouseIndicatorDimension' => array(
			'className' => 'DatawarehouseIndicatorDimension',
			'foreignKey' => 'datawarehouse_indicator_id',
			'dependent' => true
		)
	);


	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter an Indicator name'
			)
		),
		'code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter an Indicator code'
			)
		)
	);

	public $validateNumerator = array(
		'numerator_datawarehouse_field_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Numerator Field'
			)
		)
  	);
	public $validateDenominator = array(
		'denominator_datawarehouse_field_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Denominator Field'
			)
		)
  	);

	public $headerDefault = 'Custom Indicators';


	public function beforeValidate($options = array()) {
		if (isset($this->data[$this->name]['datawarehouse_unit_id']) && $this->data[$this->name]['datawarehouse_unit_id']!="1") {
			if($this->data[$this->name]['type']=='numerator'){
				$this->validate = array_merge($this->validate, $this->validateNumerator);
			}else if($this->data[$this->name]['type']=='denominator'){
				$this->validate = array_merge($this->validate, $this->validateDenominator);
			}
		}
		return true;
	}

	public function indicator($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);
	

		if ($controller->request->is('post')) {
			if (isset($controller->request->data['sortdir']) && isset($controller->request->data['order'])) {
				if ($controller->request->data['sortdir'] != $controller->Session->read('Datawarehouse.Search.sortdir')) {
					$controller->Session->delete('Datawarehouse.Search.sortdir');
					$controller->Session->write('Datawarehouse.Search.sortdir', $controller->request->data['sortdir']);
				}
				if ($controller->request->data['order'] != $controller->Session->read('Datawarehouse.Search.order')) {
					$controller->Session->delete('Datawarehouse.Search.order');
					$controller->Session->write('Datawarehouse.Search.order', $controller->request->data['order']);
				}
			}
		}

		$fieldordername = ($controller->Session->read('Datawarehouse.Search.order')) ? $controller->Session->read('Datawarehouse.Search.order') : array('DatawarehouseIndicator.name');
		$fieldorderdir = ($controller->Session->read('Datawarehouse.Search.sortdir')) ? $controller->Session->read('Datawarehouse.Search.sortdir') : 'asc';
		$order = $fieldordername;
		if($controller->Session->check('Datawarehouse.Search.order')){
			$order = array($fieldordername => $fieldorderdir);
		}

		$controller->Paginator->settings = array(
	        'fields' => array('DatawarehouseIndicator.*', 'DatawarehouseUnit.name', 'DatawarehouseModule.name'),
	        'joins' => array(
		        array(
					'type' => 'INNER',
					'table' => 'datawarehouse_units',
					'alias' => 'DatawarehouseUnit',
					'conditions' => array('DatawarehouseUnit.id = DatawarehouseIndicator.datawarehouse_unit_id')
				),
				array(
					'type' => 'INNER',
					'table' => 'datawarehouse_fields',
					'alias' => 'DatawarehouseField',
					'conditions' => array('DatawarehouseField.id = DatawarehouseIndicator.datawarehouse_field_id')
				),
				array(
					'type' => 'INNER',
					'table' => 'datawarehouse_modules',
					'alias' => 'DatawarehouseModule',
					'conditions' => array('DatawarehouseModule.id = DatawarehouseField.datawarehouse_module_id')
				)
		    ),
		    'conditions'=>array('DatawarehouseIndicator.denominator != 0 OR DatawarehouseIndicator.denominator is null'),
	        'limit' => $this->paginateLimit,
	        'recursive'=> -1,
	        'order' => $order
	    );
		
		$data = $controller->paginate('DatawarehouseIndicator');

		if (empty($data) && !$controller->request->is('ajax')) {
			$controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
		}

		$controller->set('sortedcol', $fieldordername);
		$controller->set('sorteddir', ($fieldorderdir == 'asc') ? 'up' : 'down');
		
		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		if ($controller->request->is('post')) {
			$controller->set('ajax', true);
		}
	} 

	public function indicatorAdd($controller, $params) {
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
	}

	public function indicatorEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}

	public function indicatorView($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);

		$editable = false;
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->find('first',array('recursive'=>2, 'conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->redirect(array('action'=>'indicator'));
		}

		$DatawarehouseModule = ClassRegistry::init('DatawarehouseModule');
		$datawarehouseModuleOptions = $DatawarehouseModule->find('list', array('fields'=> array('id', 'name')));

		$requestData = $controller->Datawarehouse->formatDimension($data, $datawarehouseModuleOptions, $editable);
		
		$controller->request->data = $requestData;
		$controller->set(compact('data', 'editable'));
	}

	private function setup_add_edit_form($controller, $params){
		$datawarehouseUnitOptions = $this->DatawarehouseUnit->find('list', array('fields'=> array('id', 'name')));

		$DatawarehouseModule = ClassRegistry::init('DatawarehouseModule');
		$datawarehouseModuleOptions = $DatawarehouseModule->find('list', array('fields'=> array('id', 'name')));

		$operatorOptions = $controller->Datawarehouse->operatorOptions();

		$typeOptions = array('numerator', 'denominator');
		$currentStep = 0;

		$tabStep = array('indicator', 'numerator', 'review');
		$currentTab = $tabStep[key($tabStep)];
		if($controller->request->is('get')){

		}else{
			$data = $controller->request->data;
			//$data['DatawarehouseIndicator']['datawarehouse_field_id'] = $data['DatawarehouseIndicator']['numerator_datawarehouse_field_id'];

			$saveData['DatawarehouseIndicator'] = $data['DatawarehouseIndicator'];
			$saveData['DatawarehouseIndicator']['editable'] = 1;
			$saveData['DatawarehouseIndicator']['enabled'] = 1;
			$saveData['DatawarehouseIndicator']['type'] = 'Custom';

			//$saveData['DeleteNumeratorDimensionRow'] = isset($data['DeleteNumeratorDimensionRow']) ? $data['DeleteNumeratorDimensionRow'] : array();
			//$saveData['DeleteDenominatorDimensionRow'] = isset($data['DeleteDenominatorDimensionRow']) ? $data['DeleteDenominatorDimensionRow'] : array();

			if($data['DatawarehouseIndicator']['datawarehouse_unit_id']!=1){
				$tabStep = array('indicator', 'numerator', 'denominator', 'review');
			}

			if(!isset($data['save'])){
				//WIZARD
				$errorFlag = false;

				pr($data);
				$currentStep = array_search($data['DatawarehouseIndicator']['type'], $tabStep);
				$nextStep = 0;
				if ($this->saveAll($saveData, array('validate'=>'only'))){
					pr('test');
					if(isset($data['nextStep'])){
						$currentStep = $currentStep+1;
					}else if(isset($data['prevStep'])){
						$currentStep = $currentStep-1;
					}
					$currentTab = $tabStep[$currentStep];
					pr($currentStep);
				}else{
					pr('error');
					$errorFlag = true;
				}
				$controller->set('errorFlag', $errorFlag);
			}else{
				if ($this->saveAll($saveData)){

				}
			}

		}


		foreach($typeOptions as $type){
			$moduleID = isset($controller->request->data['DatawarehouseField'][$type.'_datawarehouse_module_id']) ? $controller->request->data['DatawarehouseField'][$type.'_datawarehouse_module_id'] : key($datawarehouseModuleOptions);
			$operatorOption = isset($controller->request->data['DatawarehouseField'][$type.'_datawarehouse_operator']) ? $controller->request->data['DatawarehouseField'][$type.'_datawarehouse_operator'] : key($operatorOptions);
			$dimensionOption = isset($controller->request->data['DatawarehouseField'][$type.'_datawarehouse_field_id']) ? $controller->request->data['DatawarehouseField'][$type.'_datawarehouse_field_id'] : null;
	
			$datawarehouseSubgroupOptions = $controller->Datawarehouse->getSubgroupOptions($moduleID, $dimensionOption);
			$selectedSubgroup  = isset($controller->request->data['DatawarehouseField'][$type.'_datawarehouse_subgroup_id']) ? $controller->request->data['DatawarehouseField'][$type.'_datawarehouse_subgroup_id'] : array_keys($datawarehouseSubgroupOptions);
			
			$datawarewarehouseDimensionOptions = $controller->Datawarehouse->getDimensionOptions($moduleID);
			
			$datawarehouseOperatorFieldOptions = array();
			$datawarehouseFieldOptions = array();
			
			$controller->Datawarehouse->populateDimensionOption($moduleID, $operatorOption, $datawarehouseFieldOptions, $datawarehouseOperatorFieldOptions);
			$controller->set($type.'DatawarehouseDimensionOptions', $datawarewarehouseDimensionOptions);
			$controller->set($type.'DatawarehouseSubgroupOptions', $datawarehouseSubgroupOptions);
			$controller->set($type.'DatawarehouseOperatorFieldOptions', $datawarehouseOperatorFieldOptions);
			$controller->set($type.'DatawarehouseFieldOptions', $datawarehouseFieldOptions);

			$controller->set($type.'SelectedSubgroup', $selectedSubgroup);
		}

		$controller->set(compact('datawarehouseUnitOptions', 'datawarehouseModuleOptions', 'operatorOptions', 'editable', 'tabStep', 'currentStep', 'currentTab'));
		
	}

}

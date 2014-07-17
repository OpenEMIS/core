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

class DatawarehouseIndicator extends DataProcessingAppModel {
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
		'DatawarehouseIndicatorCondition' => array(
			'className' => 'DatawarehouseIndicatorCondition',
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
		),
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
			$this->validate = array_merge($this->validate, $this->validateDenominator);
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

		$editable = true;
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$data = $this->find('first',array('recursive'=>2, 'conditions' => array($this->name.'.id' => $id)));
			if(empty($data) && !empty($id)){
				$controller->redirect(array('action'=>'indicatorAdd'));
			}

			$requestData =  $controller->Datawarehouse->formatDimension($data, $datawarehouseModuleOptions);
			if(isset($data['DatawarehouseIndicator'])){
				$requestData['DatawarehouseIndicator'] = array_merge($requestData['DatawarehouseIndicator'], $data['DatawarehouseIndicator']);
			}
			$denominator = array();
			if(isset($data['Denominator'])){
				$denominator = $data['Denominator'];
			}
			$requestData['Denominator'] = $denominator;
			$controller->request->data = $requestData;
		}else{
			$data = $controller->request->data;
			$data['DatawarehouseIndicator']['datawarehouse_field_id'] = $data['DatawarehouseIndicator']['numerator_datawarehouse_field_id'];

			$saveData['DatawarehouseIndicator'] = $data['DatawarehouseIndicator'];
			$saveData['DatawarehouseIndicator']['editable'] = 1;
			$saveData['DatawarehouseIndicator']['enabled'] = 1;
			$saveData['DatawarehouseIndicator']['type'] = 'Custom';

			$deleteDenominator = false;
			if(isset($data['DatawarehouseIndicator']['datawarehouse_unit_id']) && $data['DatawarehouseIndicator']['datawarehouse_unit_id']!="1"){
				$saveData['Denominator'] = $saveData['DatawarehouseIndicator'];
				if(isset($data['DatawarehouseIndicator']['denominator_datawarehouse_field_id'])){
					$saveData['Denominator']['datawarehouse_field_id'] = $data['DatawarehouseIndicator']['denominator_datawarehouse_field_id'];
				}
				if(isset($data['Denominator']['id'])){
					$saveData['Denominator']['id'] = $data['Denominator']['id'];
				}
				$denominatorDimensionIndicatorCondition = array();
				if(isset($data['DenominatorDatawarehouseDimension'])){
					foreach($data['DenominatorDatawarehouseDimension'] as $d){
						$temp = array();
						if(isset($d['value'])){
							$temp['operator'] = $d['operator'];
							$temp['datawarehouse_dimension_id'] = $d['datawarehouse_dimension_id'];
							$temp['value'] = $d['value'];
							if(isset($d['id'])){
								$temp['id'] = $d['id'];
							}
							if(isset($d['datawarehouse_indicator_id'])){
								$temp['datawarehouse_indicator_id'] = $d['datawarehouse_indicator_id'];
							}
							$denominatorDimensionIndicatorCondition[] = $temp;
						}
					}
				}
				$saveData['Denominator']['DatawarehouseIndicatorCondition'] = $denominatorDimensionIndicatorCondition;
			}else{
				if(isset($data['Denominator']['id'])){
					$saveData['DatawarehouseIndicator']['denominator'] = 0;
					$deleteDenominator = true;
				}
			}

			//000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
			$numeratorDimensionIndicatorCondition = array();
			if(isset($data['NumeratorDatawarehouseDimension'])){
				foreach($data['NumeratorDatawarehouseDimension'] as $d){
					$temp = array();
					if(isset($d['value'])){
						$temp['operator'] = $d['operator'];
						$temp['datawarehouse_dimension_id'] = $d['datawarehouse_dimension_id'];
						$temp['value'] = $d['value'];
						if(isset($d['id'])){
							$temp['id'] = $d['id'];
						}
						if(isset($d['datawarehouse_indicator_id'])){
							$temp['datawarehouse_indicator_id'] = $d['datawarehouse_indicator_id'];
						}
						$numeratorDimensionIndicatorCondition[] = $temp;
					}
				}
			}
			$saveData['DatawarehouseIndicatorCondition'] = $numeratorDimensionIndicatorCondition;
			//000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000

			$saveData['DeleteNumeratorDimensionRow'] = isset($data['DeleteNumeratorDimensionRow']) ? $data['DeleteNumeratorDimensionRow'] : array();
			$saveData['DeleteDenominatorDimensionRow'] = isset($data['DeleteDenominatorDimensionRow']) ? $data['DeleteDenominatorDimensionRow'] : array();
			if ($this->saveAll($saveData)){
				if(isset($saveData['DeleteNumeratorDimensionRow'])){
					$deletedId = array();
					foreach($saveData['DeleteNumeratorDimensionRow'] as $key=>$value){
						$deletedId[] = $value['id'];
					}
					$this->DatawarehouseIndicatorCondition->deleteAll(array('DatawarehouseIndicatorCondition.id' => $deletedId), false);
				}
				if(isset($saveData['DeleteDenominatorDimensionRow'])){
					$deletedId = array();
					foreach($saveData['DeleteDenominatorDimensionRow'] as $key=>$value){
						$deletedId[] = $value['id'];
					}
					$this->DatawarehouseIndicatorCondition->deleteAll(array('DatawarehouseIndicatorCondition.id' => $deletedId), false);
				}
				if($deleteDenominator){
					$this->delete($data['Denominator']['id']);
				}
				//000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
				
				if(isset($saveData['Denominator']['DatawarehouseIndicatorCondition']) && !empty($saveData['Denominator']['DatawarehouseIndicatorCondition'])){
					$saveDenominator['DatawarehouseIndicatorCondition'] = $saveData['Denominator']['DatawarehouseIndicatorCondition'];
					$saveDenominator['DatawarehouseIndicator'] = $saveData['Denominator'];
					if(!isset($saveData['Denominator']['id'])){
						$saveDenominator['DatawarehouseIndicator']['id'] = $this->Denominator->getLastInsertId();
					}
					unset($saveDenominator['DatawarehouseIndicator']['DatawarehouseIndicatorCondition']);
					$this->saveAll($saveDenominator);
				}


				if(empty($controller->request->data[$this->name]['id'])){
				  	$controller->Message->alert('general.add.success');
				}else{	
				  	$controller->Message->alert('general.edit.success');
				}
				return $controller->redirect(array('action' => 'indicator'));
				//000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
			}else{
				
				if(array_key_exists('numerator_datawarehouse_field_id', $this->validationErrors)){
					$numeratorErrorMsg = $this->validationErrors['numerator_datawarehouse_field_id'][0];
					$controller->set('numeratorErrorMsg', $numeratorErrorMsg);
				}
				if(array_key_exists('denominator_datawarehouse_field_id', $this->validationErrors)){
					$denominatorErrorMsg = $this->validationErrors['denominator_datawarehouse_field_id'][0];
					$controller->set('denominatorErrorMsg', $denominatorErrorMsg);
				}
			}
		}

		foreach($typeOptions as $type){
			$moduleID = isset($controller->request->data['DatawarehouseField'][$type.'_datawarehouse_module_id']) ? $controller->request->data['DatawarehouseField'][$type.'_datawarehouse_module_id'] : null;
			$operatorOption = isset($controller->request->data['DatawarehouseField'][$type.'_datawarehouse_operator']) ? $controller->request->data['DatawarehouseField'][$type.'_datawarehouse_operator'] : null;

			$datawarehouseOperatorFieldOptions = array();
			$datawarehouseFieldOptions = array();
			
			$controller->Datawarehouse->populateDimensionOption($moduleID, $operatorOption, $datawarehouseFieldOptions, $datawarehouseOperatorFieldOptions);

			$controller->set($type.'DatawarehouseOperatorFieldOptions', $datawarehouseOperatorFieldOptions);
			$controller->set($type.'DatawarehouseFieldOptions', $datawarehouseFieldOptions);
		}

		$controller->set(compact('datawarehouseUnitOptions', 'datawarehouseModuleOptions', 'operatorOptions', 'editable'));
		
	}

}

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
		'datawarehouse_numerator_field_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Numerator Field'
			)
		)
	);

	public $headerDefault = 'Indicators';

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

	private function setup_add_edit_form($controller, $params){
		$datawarehouseUnitOptions = $this->DatawarehouseUnit->find('list', array('fields'=> array('id', 'name')));

		$DatawarehouseModule = ClassRegistry::init('DatawarehouseModule');
		$datawarehouseModuleOptions = $DatawarehouseModule->find('list', array('fields'=> array('id', 'name')));

		$datawarehouseOperatorFieldOptions = array();
		$datawarehouseFieldOptions = array();
	

		if($controller->request->is('get')){
			
		}else{
			$saveData = $controller->request->data;
			$saveData['DatawarehouseIndicator']['datawarehouse_field_id'] = $saveData['DatawarehouseIndicator']['datawarehouse_numerator_field_id'];

			if ($this->saveAll($saveData)){

			}else{
				$moduleID = $saveData['DatawarehouseField']['datawarehouse_module_id'];
				$operatorOption = $saveData['DatawarehouseField']['datawarehouse_operator'];

				if(!empty($moduleID)){
					$data = $controller->Datawarehouse->getFieldOptionByModuleId($moduleID);
					if(!empty($data)){
						foreach($data as $d){
			                 $datawarehouseFieldOptions[$d['DatawarehouseField']['name']] = Inflector::camelize(strtolower($d['DatawarehouseField']['name']));
                    		 $datawarehouseOperatorFieldOptions[$d['DatawarehouseField']['type']] = Inflector::camelize(strtolower($d['DatawarehouseField']['type']));
			            }
					}
				}

				if(!empty($operatorOption)){
					$data = $controller->Datawarehouse->getFieldOptionByOperatorId($moduleID, $operatorOption);
					if(!empty($data)){
						$datawarehouseFieldOptions = array();
						foreach($data as $d){
		                   	$datawarehouseFieldOptions[$d['DatawarehouseField']['id']] = Inflector::camelize(strtolower($d['DatawarehouseField']['name']));
			            }
					}
				}

				if(array_key_exists('datawarehouse_numerator_field_id', $this->validationErrors)){
					$numeratorErrorMsg = $this->validationErrors['datawarehouse_numerator_field_id'][0];
					$controller->set('numeratorErrorMsg', $numeratorErrorMsg);
				}
				/*
				if(!empty($operatorOption)){
					$data = $DatawarehouseModule->getFieldOptionByOperatorId($moduleID, $operatorOption);

					if(!empty($data)){
			            foreach($data as $d){
			                $datawarehouseFieldOptions[$d['DatawarehouseField']['id']] = Inflector::camelize(strtolower($d['DatawarehouseField']['name']));
			            }
			        }
				}*/
				//$data = $DatawarehouseModule->getFieldOptionByModuleId($moduleID);


				//$data = $DatawarehouseModule->getFieldOptionByOperatorId($moduleID, $operatorOption);
			}
		}

		$controller->set(compact('datawarehouseUnitOptions', 'datawarehouseModuleOptions', 'datawarehouseOperatorFieldOptions', 'datawarehouseFieldOptions'));
		
	}

}

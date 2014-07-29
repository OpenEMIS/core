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

App::uses('Component', 'Controller');

class IndicatorComponent extends Component {
	public $components = array('Logger', 'Utility');

	public $runLimit = 1000;
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->init();
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) { }
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) { }
	
	//called after Controller::render()
	public function shutdown(Controller $controller) { }
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) { }
	
	public function init() {
		$this->DatawarehouseModule = ClassRegistry::init('DatawarehouseModule');
		$this->DatawarehouseDimension = ClassRegistry::init('DatawarehouseDimension');
		$this->Logger->init('indicator');
	}

	public function getFieldOptionByModuleId($moduleID){
        if(!empty($moduleID)){
             $data = $this->DatawarehouseModule->find('all', array(
                'fields' => array('DatawarehouseModule.*', 'DatawarehouseField.*'),
                'joins' => array(
                    array(
                        'type' => 'INNER',
                        'table' => 'datawarehouse_fields',
                        'alias' => 'DatawarehouseField',
                        'conditions' => array('DatawarehouseModule.id = DatawarehouseField.datawarehouse_module_id')
                    ),
                ),
                'conditions'=>array('DatawarehouseModule.id'=>$moduleID),
                'recursive'=> -1
                )
            );

            return $data;
        }
    }

    public function getFieldOptionByOperatorId($moduleID, $operatorOption){
         if(!empty($moduleID) && !empty($operatorOption)){
             $data = $this->DatawarehouseModule->find('all', array(
                'fields' => array('DatawarehouseModule.*', 'DatawarehouseField.*'),
                'joins' => array(
                    array(
                        'type' => 'INNER',
                        'table' => 'datawarehouse_fields',
                        'alias' => 'DatawarehouseField',
                        'conditions' => array('DatawarehouseModule.id = DatawarehouseField.datawarehouse_module_id')
                    ),
                ),
                'conditions'=>array('DatawarehouseModule.id'=>$moduleID, 'DatawarehouseField.type'=>$operatorOption),
                'recursive'=> -1
                )
            );

         	return $data;
         }
    }

    public function getDimensionOptions($moduleID){
    	$data = $this->DatawarehouseDimension->find('list', array(
            'fields' => array('DatawarehouseDimension.id', 'DatawarehouseDimension.name'),
            'conditions'=>array('DatawarehouseDimension.datawarehouse_module_id'=>$moduleID),
            'recursive'=> -1
            )
        );

     	return $data;
    }

    public function operatorOptions(){
    	$operatorOptions['='] = '=';
    	$operatorOptions['>'] = '>';
    	$operatorOptions['>='] = '>=';
    	$operatorOptions['<'] = '<';
    	$operatorOptions['<='] = '<=';
    	$operatorOptions['!='] = '!=';
    	return $operatorOptions;
    }

     public function getDimensionValueOption($dimensionOption){
 		$dimension = $this->DatawarehouseDimension->find('first', 
 			array(
 				'fields'=>array('DatawarehouseDimension.*', 'DatawarehouseModule.*'),
 				'joins' => array(
                    array(
                        'type' => 'INNER',
                        'table' => 'datawarehouse_modules',
                        'alias' => 'DatawarehouseModule',
                        'conditions' => array('DatawarehouseModule.id = DatawarehouseDimension.datawarehouse_module_id')
                    ),
                ),
 				'conditions'=>array('DatawarehouseDimension.id'=>$dimensionOption)
 			)

 		);
 		$fieldOption = array();
 		if(!empty($dimension)){
 			$modelName = $dimension['DatawarehouseDimension']['model'];
 			$parentModel = $dimension['DatawarehouseDimension']['model'];
 			$fieldName = $dimension['DatawarehouseDimension']['field'];
 			$joins = $dimension['DatawarehouseDimension']['joins'];

 			$join = array();
 			if(!empty($joins)){
 				$parentModel = $dimension['DatawarehouseModule']['model'];
				eval("\$join = array($joins);");
 			}

 			$dimensionModel = ClassRegistry::init($parentModel);
 			
	    	$data = $dimensionModel->find('all', array(
	            'fields' => array('DISTINCT ' . $modelName.'.'.$fieldName, $modelName.'.'.$fieldName),
	            'joins' => $join,
	            'recursive'=> -1
	            )
	        );


	        if(!empty($data)){
	        	foreach($data as $d){
	        		$fieldOption[$d[$modelName][$fieldName]] = $d[$modelName][$fieldName];
	        	}
	        }
 		}
     	return $fieldOption;
    }

    public function formatDimension($data, $datawarehouseModuleOptions, $editable=true){
    	$requestData = array();


		//000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
		if(isset($data['DatawarehouseField']['datawarehouse_module_id'])){
			$numeratorModuleID = $data['DatawarehouseField']['datawarehouse_module_id'];
			$numeratorDatawarehouseDimensionOptions = $this->getDimensionOptions($numeratorModuleID);
			$requestData['DatawarehouseField']['numerator_datawarehouse_module_id'] = ($editable ? $numeratorModuleID : $datawarehouseModuleOptions[$numeratorModuleID]);
			$requestData['DatawarehouseField']['numerator_datawarehouse_operator'] = $data['DatawarehouseField']['type'];
			$requestData['DatawarehouseField']['numerator_datawarehouse_field'] =  ($editable) ? $data['DatawarehouseField']['id'] : ucwords($data['DatawarehouseField']['name']);
			$requestData['DatawarehouseIndicator']['numerator_datawarehouse_field_id'] = $data['DatawarehouseField']['id'];

			if(!empty($data['DatawarehouseIndicatorCondition'])){
				foreach($data['DatawarehouseIndicatorCondition'] as $key=>$val){
					$data['DatawarehouseIndicatorCondition'][$key]['dimension_name'] = $numeratorDatawarehouseDimensionOptions[$val['datawarehouse_dimension_id']];

				}
			}

			$requestData['NumeratorDatawarehouseDimension'] = $data['DatawarehouseIndicatorCondition'];
		}
		//000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
		if(isset($data['Denominator']) && !empty($data['Denominator']['id'])){
			$denominatorModuleID = $data['DatawarehouseField']['datawarehouse_module_id'];
			$denominatorDatawarehouseDimensionOptions = $this->getDimensionOptions($denominatorModuleID);
			$requestData['DatawarehouseField']['denominator_datawarehouse_module_id'] = ($editable ? $denominatorModuleID : $datawarehouseModuleOptions[$denominatorModuleID]);
			$requestData['DatawarehouseField']['denominator_datawarehouse_operator'] =  $data['Denominator']['DatawarehouseField']['type'];
			$requestData['DatawarehouseField']['denominator_datawarehouse_field'] = ($editable) ? $data['Denominator']['DatawarehouseField']['id'] : ucwords($data['Denominator']['DatawarehouseField']['name']);
			$requestData['DatawarehouseIndicator']['denominator_datawarehouse_field_id'] = $data['Denominator']['DatawarehouseField']['id'];
		
			if(!empty($data['Denominator']['DatawarehouseIndicatorCondition'])){
				foreach($data['Denominator']['DatawarehouseIndicatorCondition'] as $key=>$val){
					$data['Denominator']['DatawarehouseIndicatorCondition'][$key]['dimension_name'] = $numeratorDatawarehouseDimensionOptions[$val['datawarehouse_dimension_id']];
				}
				$requestData['DenominatorDatawarehouseDimension'] = $data['Denominator']['DatawarehouseIndicatorCondition'];
			}
		}

		//000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
		return $requestData;
    }


    public function populateDimensionOption($moduleID, $operatorOption, &$datawarehouseFieldOptions, &$datawarehouseOperatorFieldOptions){
    	if(!empty($moduleID)){
			$data = $this->getFieldOptionByModuleId($moduleID);
			if(!empty($data)){
				foreach($data as $d){
	                 $datawarehouseFieldOptions[$d['DatawarehouseField']['name']] = Inflector::camelize(strtolower($d['DatawarehouseField']['name']));
            		 $datawarehouseOperatorFieldOptions[$d['DatawarehouseField']['type']] = Inflector::camelize(strtolower($d['DatawarehouseField']['type']));
	            }
			}
		}

		if(!empty($operatorOption)){
			$data = $this->getFieldOptionByOperatorId($moduleID, $operatorOption);
			if(!empty($data)){
				$datawarehouseFieldOptions = array();
				foreach($data as $d){
                   	$datawarehouseFieldOptions[$d['DatawarehouseField']['id']] = Inflector::camelize(strtolower($d['DatawarehouseField']['name']));
	            }
			}
		}
    }

    //000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000

    public function getReportList($format=true, $order=array('DatawarehouseIndicator.name')){
		$DatawarehouseIndicator = ClassRegistry::init('DatawarehouseIndicator');

    	$data = $DatawarehouseIndicator->find('all',
	    	array(
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
		        'recursive'=> -1,
		        'order' => $order
	       	)
		);
		if($format){
			$data = $this->formatTable($data);
		}
		return $data;
    }

    private function formatTable($data){
        $tmp = array();
		foreach($data as $k => $val){
			$module = 'Custom';

			$tmp['Reports'][$module][$val['DatawarehouseIndicator']['name']] = $val['DatawarehouseIndicator'];
		}
		return $tmp;
	}
    
	//000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000

    public function generateIndicator($id, $userId=0) {

    }
	
}
?>
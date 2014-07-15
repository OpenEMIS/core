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

class DatawarehouseComponent extends Component {
	public $components = array('Logger', 'Utility');
	
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
 		pr($dimension);
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

 			pr($join);
 			$dimensionModel = ClassRegistry::init($parentModel);
 			
 	
	    	$data = $dimensionModel->find('all', array(
	            'fields' => array('DISTINCT ' . $modelName.'.'.$fieldName, $modelName.'.'.$fieldName),
	            'joins' => $join,
	            'recursive'=> -1
	            )
	        );

	        pr($data);


	        if(!empty($data)){
	        	foreach($data as $d){
	        		$fieldOption[$d[$modelName][$fieldName]] = $d[$modelName][$fieldName];
	        	}
	        }
 		}
     	return $fieldOption;
    }

	
}
?>
<?php

App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('Sanitize', 'Utility');
class DatawarehouseController extends DatawarehouseAppController {

    public $modules = array(
        'indicator' => 'Datawarehouse.DatawarehouseIndicator'
    ); 
	
	public $components = array('Paginator', 'Datawarehouse.Datawarehouse', 'DataProcessing.Indicator');
	
	private function getLogPath(){
		//return ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results/logs/';
		return ROOT.DS.'app'.DS.'webroot'.DS.'logs'.DS.'reports'.DS;
	}
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Data Processing', array('controller' => $this->name, 'action' => 'build'));
	}

    public function test(){
        $this->autoRender = false;
        $this->Indicator->generateIndicator(4, 0);
    }

    public function ajax_populate_by_module($moduleID, $type){
        $this->autoRender = false;
        if(!empty($moduleID)){
            $data = $this->Indicator->getFieldOptionByModuleId($moduleID);
            $dimensionOption = $this->Indicator->getDimensionOptions($moduleID);
            $operatorOptions = array();
            $fieldOptions = array();

            if(!empty($data)){
                foreach($data as $d){
                    $fieldOptions[$d['DatawarehouseField']['name']] = Inflector::camelize(strtolower($d['DatawarehouseField']['name']));
                    $operatorOptions[$d['DatawarehouseField']['type']] = Inflector::camelize(strtolower($d['DatawarehouseField']['type']));
                }
            }
            $jsonData['fieldOption'] = $fieldOptions;
            $jsonData['operatorOption'] = $operatorOptions;


            $this->set('type', $type);
            $this->set($type.'DatawarehouseDimensionOptions', $dimensionOption);

            $View = new View($this);
            $dimensionRow =  $View->element('dimensionOption');
            $jsonData['dimensionRow'] = $dimensionRow;
            return json_encode($jsonData);
        }
    }

    public function ajax_populate_by_operator($moduleID, $operatorOption){
         $this->autoRender = false;
         if(!empty($moduleID) && !empty($operatorOption)){
            $data = $this->Datawarehouse->getFieldOptionByOperatorId($moduleID, $operatorOption);
            $fieldOptions = array();
            if(!empty($data)){
                foreach($data as $d){
                    $fieldOptions[$d['DatawarehouseField']['id']] = Inflector::camelize(strtolower($d['DatawarehouseField']['name']));
                }
            }
            $jsonData['fieldOption'] = $fieldOptions;
            return json_encode($jsonData);
         }
    }

    public function ajax_add_dimension_row(){
        $this->layout = 'ajax';
        $moduleID = $this->params->query['module_id'];
        if(!empty($moduleID)){
            $this->set('index', $this->params->query['index']);
            $this->set('type', $this->params->query['type']);
            $this->set('datawarehouseDimensionOptions', $this->Datawarehouse->getDimensionOptions($moduleID));
            $this->set('operatorOptions', $this->Datawarehouse->operatorOptions());
            $this->set('valueOptions', array());
            $this->render('/Elements/datawarehouse_dimension_row');
        }
    }

    public function ajax_populate_by_dimension($dimensionOption){
         $this->autoRender = false;
         if(!empty($dimensionOption)){
            $data = $this->Datawarehouse->getDimensionValueOption($dimensionOption);
            $jsonData['dimensionValueOption'] = $data;
            return json_encode($jsonData);
         }
    }

    public function ajax_populate_subgroup($moduleID, $dimensionId){
         $this->autoRender = false;
         $dimensionOption = explode(",", $dimensionId);
         $this->Datawarehouse->getSubgroupOptions($moduleID, $dimensionOption);
        
    }
}
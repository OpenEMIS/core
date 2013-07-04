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

App::uses('AppController', 'Controller');
App::uses('AppTask', 'Console/Command/Task');
App::uses('IndicatorComponent', 'DataProcessing.Controller/Component');

class IndTask extends AppTask {
	public $limit = 1000;
	public $fileFP;
	public $tasks = array('Common');
	public $Batch;
	public $Controller;
	public $BatchIndicatorResult;
	
	/****
	 * CSV Starts 
	 */
	
	function initialize() { 
		$this->BatchIndicatorResult = ClassRegistry::init('DataProcessing.BatchIndicatorResult');
    } 
	public function prepareIndCSV($settings){
		
        
		$header = 'Indicator,Sub Groups,Area,Time Period,Data Value,Classification';
		$arrHeader = explode(',', $header);
        $this->Common->translateArrayValues($arrHeader);
		$batchReportId = $settings['batchProcessId'];
		$reportId = $settings['reportId'];
        $name = $settings['name'];
        $module = $settings['module'];
        $category = $settings['category'];
        $line = '';
        $filename = $reportId."_".$batchReportId."_".str_replace(' ', '_', $name).'.csv';
        //$path =  WWW_ROOT.DS.$module.DS;
        //$path = ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$category).DS.$module.DS;
		$path = $this->Common->getResultPath().str_replace(' ','_',$category).DS.$module.DS;
        //$type = ($batch == 0)?'w':'a';//if first run truncate the file to 0
		$type = 'w+';
        $this->fileFP = fopen($path.$filename, $type);
		// fputs ($this->fileFP, $header."\n");
		fputs ($this->fileFP, implode(",", $arrHeader)."\n");
       
		
	}
	
	public function writeIndCSV($data,$settings){
		
        $tpl = $settings['header'];
        $arrTpl = explode(',',$tpl);
		
		//if ($batch == 0){ fputs ($this->fileFP, $tpl."\n"); }
        foreach($data as $k => $arrv){
			$line = '';
			foreach($arrTpl as $column){
					$line .= "'".$this->Common->cleanContent($arrv[$column])."'".',';
			}
			$line .= "\n";
			fputs ($this->fileFP, $line);
        }
		
	}
	
	public function closeIndCSV($settings){
		 fclose ($this->fileFP);
	}
	
	public function genIND($settings){
		
		//pr($settings);
		$indicatorRec = $this->getIndicatorsByName($settings['name']);
		
		
		
		$Indicator = new IndicatorComponent(new ComponentCollection);
		$Indicator->init();
		$Indicator->run(array('indicators' => array($indicatorRec['BatchIndicator']['id'])));

        # Use to generate data dump into csv format.
//		$tpl = $settings['tpl'];
//		$procId = $settings['batchProcessId'];
//		$count = $this->BatchIndicatorResult->find('count');
//		$recusive = ceil($count / $this->limit);
//		$this->prepareIndCSV($settings);
//		for($i=0;$i<$recusive;$i++){
//			$offset = ($this->limit*$i);
//			try{
//				$this->BatchIndicatorResult->bindModel(array(
//					'belongsTo'=> array(
//						'Area'=>array('foreignKey' => 'area_id'),
//						'BatchIndicator'=>array('foreignKey' => 'batch_indicator_id')
//						)
//					)
//				);
//				$data = $this->BatchIndicatorResult->find('all',array('fields'=>array(
//					'BatchIndicator.name AS Indicator',
//					'BatchIndicatorResult.subgroups AS SubGroup',
//					'Area.name AS AreaName',
//					'BatchIndicatorResult.timeperiod AS TimePeriod',
//					'BatchIndicatorResult.data_value AS DataValue',
//					'BatchIndicatorResult.classification AS Classification'),'limit'=>$this->limit,'offset'=>$offset));
//
//			} catch (Exception $e) {
//				// Update the status for the Processed item to (-1) ERROR
//				$errLog = $e->getMessage();
//				$this->Common->updateStatus($procId,'-1');
//				$this->Common->createLog($this->Common->getLogPath().$procId.'.log',$errLog);
//			}
//
//			$this->Common->formatData($data);
//
//			$this->writeIndCSV($data, $settings);
//
//			echo json_encode(array('processed_records'=>($offset+$this->limit),'batch'=>($i+1)));
//		}
//
//		$this->closeIndCSV($settings);
		
		
		

	}
	
	public function getIndicatorsByName($name){
		$BatchIndicator = ClassRegistry::init('BatchIndicator');
		$data = $BatchIndicator->find('first',array('conditions'=>array('name'=>$name)));
		return $data;
	}
	
}
	
?>

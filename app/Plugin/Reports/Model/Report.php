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

App::import('model','BatchIndicator');
class Report extends ReportsAppModel {
	public $hasMany = array('DataProcessing.BatchReport');
	
	public function getReportsWithIndicators($arrIds){
		$BatchIndicator = ClassRegistry::init('BatchIndicator');
		$data = $this->find('all',array('conditions'=>array('id'=>$arrIds)));
		//pr($data);die;
		foreach($data as &$arr){
			if($arr['Report']['module'] == 'Indicator'){
				$ar = $BatchIndicator->find('first',array('conditions'=>array('name'=>$arr['Report']['name'])));
				try{
					$arr = array_merge($arr,$ar);
				}  catch (Exception $e){
				}
			}
		}
		return $data;
	}

	public function getCustomReportsWithIndicators($arrIds){
		$datawarehouseIndicator = ClassRegistry::init('Datawarehouse.DatawarehouseIndicator');
		$data = $datawarehouseIndicator->find('all',array('recursive'=>-1, 'conditions'=>array('id'=>$arrIds)));
		
		return $data;
	}
	
	public function getQueuedRunningReport(){
		$BatchProcess = ClassRegistry::init('DataProcessing.BatchProcess');
		$queuedOrRunning = $BatchProcess->find('all',array('conditions'=>array('status'=>array(1,2))));
		$ret =array();
		foreach($queuedOrRunning as $arrBatch){
			$ret[] = array('BatchProcessId'=>$arrBatch['BatchProcess']['id'],'Filename'=>$arrBatch['BatchProcess']['file_name'],'Status'=>$arrBatch['BatchProcess']['status']);
		}
		return $ret;
	}
	
	public function fixCheckBoxVal(&$arr){
		$tmp = array();
		foreach($arr as $k => $val){
			$tmp = array_merge($tmp,explode(',',$val));
		}
		$arr = $tmp;
	}
	public function processRequest($arrIds, $indicator=true){
		$this->fixCheckBoxVal($arrIds);
		$BatchIndicator = ClassRegistry::init('BatchIndicator');
		$data = array();
		if(!$indicator){
			$data = $this->getCustomReportsWithIndicators($arrIds);
		}else{
			$data = $this->getReportsWithIndicators($arrIds);
		}
		$QR = $this->getQueuedRunningReport();

		$this->insertToBatchProcess($data, $indicator);
	}
	
	private function insertToBatchProcess($data, $indicator){
		$BatchProcess = ClassRegistry::init('BatchProcess');
		$tmp = array();
		
		foreach($data as $k => $v){
			$BatchProcess->create();
			if($indicator){
				$tmp = array(
				'name'=>$v['Report']['name'],
				//'file_name'=>  ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$v['Report']['category']).DS.$v['Report']['module'].DS.str_replace(' ', '_', $v['Report']['name']).'.'.$v['Report']['file_type'],
				'start_date'=>date('Y-m-d H:i:s'),
				'finish_date'=>'0000-00-00 00:00:00',
				'reference_id' => $v['Report']['id'],
				'reference_table' => 'reports',
				'status'=>1
				);
			}else{
				$tmp = array(
					'name'=>$v['DatawarehouseIndicator']['name'],
					//'file_name'=>  ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$v['Report']['category']).DS.$v['Report']['module'].DS.str_replace(' ', '_', $v['Report']['name']).'.'.$v['Report']['file_type'],
					'start_date'=>date('Y-m-d H:i:s'),
					'finish_date'=>'0000-00-00 00:00:00',
					'reference_id' => $v['DatawarehouseIndicator']['id'],
					'reference_table' => 'datawarehouse_indicators',
					'status'=>1
				);
			}
			$rec = $BatchProcess->save($tmp);
			if(isset($rec['BatchProcess']['id'])){
				$BatchProcess->id = $rec['BatchProcess']['id'];
				$BatchProcess->saveField('file_name',APP.WEBROOT_DIR.DS.'logs'.DS.'reports'.DS.$rec['BatchProcess']['id'].'.log');
			}
		}
	}	
}
?>

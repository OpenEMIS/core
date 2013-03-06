<?php
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
	public function processRequest($arrIds){
		$this->fixCheckBoxVal($arrIds);
		$BatchIndicator = ClassRegistry::init('BatchIndicator');
		$data = $this->getReportsWithIndicators($arrIds);
		$QR = $this->getQueuedRunningReport();
		$this->insertToBatchProcess($data);
	}
	
	private function insertToBatchProcess($data){
		$BatchProcess = ClassRegistry::init('BatchProcess');
		$tmp = array();
		
		foreach($data as $k => $v){
			$BatchProcess->create();
			$tmp = array(
				'name'=>$v['Report']['name'],
				//'file_name'=>  ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$v['Report']['category']).DS.$v['Report']['module'].DS.str_replace(' ', '_', $v['Report']['name']).'.'.$v['Report']['file_type'],
				'start_date'=>date('Y-m-d h:i:s'),
				'finish_date'=>'0000-00-00 00:00:00',
				'reference_id' => $v['Report']['id'],
				'reference_table' => 'reports',
				'status'=>1
				);
			$rec = $BatchProcess->save($tmp);
			if(isset($rec['BatchProcess']['id'])){
				$BatchProcess->id = $rec['BatchProcess']['id'];
				$BatchProcess->saveField('file_name',APP.WEBROOT_DIR.DS.'logs'.DS.'reports'.DS.$rec['BatchProcess']['id'].'.log');
			}
		}
	}	
}
?>

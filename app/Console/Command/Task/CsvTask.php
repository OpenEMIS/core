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

//App::import('Core', 'Controller'); 
//App::import('Component', 'Email'); 
App::uses('AppTask', 'Console/Command/Task');


class CsvTask extends AppTask {
	public $limit = 1000;
	public $fileFP;
	public $tasks = array('Common');
	/****
	 * CSV Starts
	 */
	
	
	public function prepareCSV($settings){
		//echo ">>>".pr($settings);die;
		
        $tpl = $settings['tpl'];
        $name = $settings['name'];
        $module = $settings['module'];
        $category = $settings['category'];
		$batchReportId = $settings['batchProcessId'];
		$reportId = $settings['reportId'];
		
        $arrTpl = explode(',', $tpl);
        $this->Common->translateArrayValues($arrTpl, " ");
        
        foreach($arrTpl AS $key => $column){
            if($column == 'Identification No'){
                $arrTpl[$key] = 'OpenEMIS ID';
                break;
            }
        }

        $line = '';
        $filename = $reportId."_".$batchReportId."_".str_replace(' ', '_', $name).'.csv'; 
        $module = str_replace(' ', '_', $module);
        //$path =  WWW_ROOT.DS.$module.DS;
        $path = $this->Common->getResultPath().str_replace(' ','_',$category).DS.$module.DS;
		
        //$type = ($batch == 0)?'w':'a';//if first run truncate the file to 0
		
		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}
		
		$type = 'w+';
        $this->fileFP = fopen($path.$filename, $type);
		fputs ($this->fileFP, implode(',',$arrTpl)."\n");
       
		
	}
	
        public function getPreCleanContentFunc(){
            return function(&$field){
                if (preg_match('/,/', $field)){ 
                    $field= '"' . $field . '"';
                }
                return false;
            };
        }
        
    public function writeCSV($data, $settings) {
        //$batch = $settings['batch'];
        $tpl = $settings['tpl'];
        $arrTpl = explode(',', $tpl);
        $preclean = $this->getPreCleanContentFunc();
        //if ($batch == 0){ fputs ($this->fileFP, $tpl."\n"); }
        if (empty($settings['custom3LayerFormat']) || !$settings['custom3LayerFormat']) {
            foreach ($data as $k => $arrv) {
                $line = '';
                foreach ($arrTpl as $column) {
                    $line .= $this->Common->cleanContent($arrv[$column], array('preclean' => $preclean)) . ',';
                }

                $line .= "\n";
                fputs($this->fileFP, $line);
            }
        }
        else{
            foreach ($data as $arrSingleResult) {
                $line = '';
                foreach ($arrSingleResult as $table => $arrFields) {

                    foreach ($arrFields as $col) {
                        $line .= $this->Common->cleanContent($col, array('preclean' => $preclean)) . ',';
                    }
                    if(empty($arrSingleResult)){
                        $line .= "\n";
                    }
                }
                
                $line .= "\n";
                fputs($this->fileFP, $line);
            }
        }
    }

    public function closeCSV(){
//		$configItem = ClassRegistry::init('ConfigItem');
//		$dateFormat = $configItem->getValue('date_format');
//		$timeFormat = $configItem->getValue('time_format');
//		
//		$dateNow = new DateTime(date("Y-m-d"));
//		$timeNow = new DateTime(date("H:i:s"));
//		$dateFormatted = $dateNow->format($dateFormat);
//		$timeFormatted = $timeNow->format($timeFormat);
//		
//		$dateTimeFormatted = $dateFormatted . ' ' . $timeFormatted;
		
        $line = "\n";
        $line .= __("Report Generated").": "  . date("Y-m-d H:i:s");
        fputs ($this->fileFP, $line);
		fclose ($this->fileFP);
	}
	
	
	public function genCSV($settings){
        $tpl = $settings['tpl'];
		$procId = $settings['batchProcessId'];
		$arrCount = $this->Common->getCount($settings['reportId']);
		$recusive = ceil($arrCount['total'] / $this->limit);
		$this->prepareCSV($settings);
		for($i=0;$i<$recusive;$i++){
			$sql = $settings['sql'];
			$offset = ($this->limit*$i);
			$offsetStr = $offset;
			$offsetStr = (string)$offsetStr;
			//str_replace('all',)
			$cond = '\'offset\'=>'.$offset.',\'limit\'=>$this->limit';
			$sql = str_replace('{cond}',$cond,$sql);
			try{
				eval($sql);
			} catch (Exception $e) {
				// Update the status for the Processed item to (-1) ERROR
				$errLog = $e->getMessage();
				$this->Common->updateStatus($procId,'-1');
				$this->Common->createLog($this->Common->getLogPath().$procId.'.log',$errLog);
			}
                        
                        if(empty($settings['custom3LayerFormat']) || !$settings['custom3LayerFormat']){
                            $this->Common->formatData($data);//pr($data);
                        }
			$this->writeCSV($data, $settings);
			echo json_encode(array('processed_records'=>($offset+$this->limit),'batch'=>($i+1)));
		}
		
		$this->closeCSV();
    }
    
    public function genCSVCustom($settings){
        $tpl = $settings['tpl'];
		$procId = $settings['batchProcessId'];
		$arrCount = $this->Common->getCountCustom($settings['reportId']);
		$recusive = ceil($arrCount['total'] / $this->limit);
		$this->prepareCSV($settings);
		for($i=0;$i<$recusive;$i++){
			$sql = $settings['sql'];
			$offset = ($this->limit*$i);
			$offsetStr = $offset;
			$offsetStr = (string)$offsetStr;
			//str_replace('all',)
			$cond = '\'offset\'=>'.$offset.',\'limit\'=>$this->limit';
			$sql = str_replace('{cond}',$cond,$sql);
			try{
				eval($sql);
			} catch (Exception $e) {
				// Update the status for the Processed item to (-1) ERROR
				$errLog = $e->getMessage();
				$this->Common->updateStatus($procId,'-1');
				$this->Common->createLog($this->Common->getLogPath().$procId.'.log',$errLog);
			}
                        
                        if(empty($settings['custom3LayerFormat']) || !$settings['custom3LayerFormat']){
                            $this->Common->formatData($data);//pr($data);
                        }
			$this->writeCSV($data, $settings);
			echo json_encode(array('processed_records'=>($offset+$this->limit),'batch'=>($i+1)));
		}
		
		$this->closeCSV();
    }
}
	
?>

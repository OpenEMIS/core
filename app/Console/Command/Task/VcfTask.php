<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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
class VcfTask extends AppTask {
	public $limit = 1000;
	public $fileFP;
	public $tasks = array('Common');
	
	
	/****
	 * VCF ver 4.0 Starts
	 */
	public function prepareVCF($settings){
        
        $name = $settings['name'];
        $module = $settings['module'];
        $category = $settings['category'];
		$batchReportId = $settings['batchProcessId'];
		$reportId = $settings['reportId'];
		
        $line = '';
        $filename = $reportId."_".$batchReportId."_".str_replace(' ', '_', $name).'.vcf'; 
        //$path =  WWW_ROOT.DS.$module.DS;
        //$path = ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$category).DS.$module.DS;
		$path = $this->Common->getResultPath().str_replace(' ','_',$category).DS.$module.DS;
        //$type = ($batch == 0)?'w':'a';//if first run truncate the file to 0
		$type = 'w+';
        $this->fileFP = fopen($path.$filename, $type);
		
	}
	
	public function writeVCF($data,$settings){
		//$batch = $settings['batch'];
        $tpl = $settings['tpl'];
		
		$YMD = date('Ymd');
		$HIS = date('his');
        foreach($data as $k => $arrv){
			$line =$tpl;
			foreach($arrv as $index => $column){
					$line = str_replace("{".$index."}",$column,$line);
			}
			$line = str_replace("{first_name}",'',$line);
			$line = str_replace("{last_name}",'',$line);
			$line = str_replace("{YYYYMMDD}",$YMD,$line);
			$line = str_replace("{HIS}",$HIS,$line)."\n";
			
			$line = str_replace("{Street}",'',$line);
			$line = str_replace("{Locality}",'',$line);
			$line = str_replace("{Region}",'',$line);
			$line = str_replace("{ZipCode}",'',$line);
			$line = str_replace("{country}",'',$line);
			
			
			fputs ($this->fileFP, $line);
        }
       
		
	}
	
	public function closeVCF(){
		 fclose ($this->fileFP);
	}
	
	public function genVCF($settings){
		$tpl = $settings['tpl'];
        
		$procId = $settings['batchProcessId'];
		
		$arrCount = $this->Common->getCount($settings['reportId']);
		$recusive = ceil($arrCount['total'] / $this->limit);

		$this->prepareVCF($settings);
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
			$this->Common->formatData($data);
			
			$this->writeVCF($data, $settings);
			
			 echo json_encode(array('processed_records'=>($offset+$this->limit),'batch'=>($i+1)));
		}
		
		$this->closeVCF();
		
    }
}
	
?>

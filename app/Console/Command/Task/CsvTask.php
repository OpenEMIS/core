<?php  
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

        $line = '';
        $filename = $reportId."_".$batchReportId."_".str_replace(' ', '_', $name).'.csv'; 
        $module = str_replace(' ', '_', $module);
        //$path =  WWW_ROOT.DS.$module.DS;
        $path = $this->Common->getResultPath().str_replace(' ','_',$category).DS.$module.DS;
		
        //$type = ($batch == 0)?'w':'a';//if first run truncate the file to 0
		$type = 'w+';
        $this->fileFP = fopen($path.$filename, $type);
		fputs ($this->fileFP, implode(',',$arrTpl)."\n");
       
		
	}
	
	public function writeCSV($data,$settings){
		//$batch = $settings['batch'];
        $tpl = $settings['tpl'];
        $arrTpl = explode(',',$tpl);

		//if ($batch == 0){ fputs ($this->fileFP, $tpl."\n"); }
        foreach($data as $k => $arrv){
			$line = '';
			pr ($arrTpl);
			foreach($arrTpl as $column){
					$line .= $this->Common->cleanContent($arrv[$column]).',';
			}
			$line .= "\n";
			fputs ($this->fileFP, $line);
        }
	}
	
	public function closeCSV(){
        $line = "\n";
        $line .= "Report Generated: " . date("Y-m-d H:i:s");
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
			$this->Common->formatData($data);
			$this->writeCSV($data, $settings);
			echo json_encode(array('processed_records'=>($offset+$this->limit),'batch'=>($i+1)));
		}
		
		$this->closeCSV();
    }
}
	
?>

<?php

class BatchShell extends AppShell {
    
    public $uses = array('BatchProcess','Reports.Report','Reports.BatchReport');
	
	public $tasks = array('Common','Kml','Csv','Vcf','Ind');
    
    public function main(){

    }
	
    public function _welcome(){
		
    }
    public function run() {
        App::import('Component', 'Session');

    	if(sizeof($this->args) == 1) {
			Configure::write('Config.language', $this->args[0]);
		}

        //GET ALL the QUEUED UP Process
        $data = $this->BatchProcess->find('list',array('fields'=>array('id','reference_id'),'conditions'=>array('status'=>1,'reference_table'=>'reports')));

        //PROCESS each item in QUEUE
        foreach($data as $id => $ReportRef_id){
			
           try{
			   $errLog = '';
			   
			   
			   /*$name2 = str_replace('_',' ',$name);
			   $ext = pathinfo($name2, PATHINFO_EXTENSION); 
			   $name = pathinfo($name2, PATHINFO_FILENAME);*/
			   
               //DOUBLE CHECK if Status was Aborted or what - else just proceed with the Next QUEUE
               $check = $this->BatchProcess->find('first',array('conditions'=>array('id'=>$id)));
               if($check['BatchProcess']['status'] != 1) continue;

               // Update the status for the one Being Processed to (2) Processing
               $this->Common->updateStatus($id,2);
			   
			   
               //Get the Report Id from Report Table
               //$ReportRec = $this->Report->find('first',array('conditions'=>array('name'=>$name,'file_type'=>$ext)));
			   $ReportRec = $this->Report->findById($ReportRef_id);
			   
			   
			   $this->genReport($ReportRec,$id);
	
               // Update the status for the Processed item to (3) Complete
               $this->Common->updateStatus($id,3);

           } catch (Exception $e) {
               // Update the status for the Processed item to (-1) ERROR
			    $errLog .= $e->getMessage();
               $this->Common->updateStatus($id,'-1');
           }
		   
           $logs = $this->BatchProcess->getDatasource()->getLog();
           $logs = print_r($logs,true);
           $this->Common->createLog($this->Common->getLogPath().$id.'.log',$logs.$errLog);
        }
    }
	
    public function genReport($reportRec,$BatchProcessId){
	
		$this->autoRender = false;
		$file_type = $reportRec['Report']['file_type'];
		$settings = array(
                        'module'=>$reportRec['Report']['module'],
                        'category'=>$reportRec['Report']['category'],
                        'name'=>$reportRec['Report']['name'],
                        'sql'=>@$reportRec['BatchReport'][0]['query'],
                        'tpl'=>@$reportRec['BatchReport'][0]['template'],
						'header'=>@$reportRec['Report']['header'],
						'footer'=>$reportRec['Report']['footer'],
						'batchProcessId' =>$BatchProcessId,
						'reportId' =>$reportRec['Report']['id']);
		
		
        if( $file_type == 'csv'){
			
			$this->Csv->genCSV($settings);
        }elseif($file_type == 'kml'){
			
			 $this->Kml->genKML($settings);
		}elseif($file_type == 'vcf'){
			
			 $this->Vcf->genVCF($settings);
        }elseif($file_type == 'ind'){

			 $this->Ind->genIND($settings);
        }elseif($file_type='yearbook'){
			echo "yearbook";
			if(sizeof($this->args) == 1) {
				$this->dispatchShell('yearbook',$this->args[0]);
			} else {
				$this->dispatchShell('yearbook');
			}
		}
		
    }
}

?>

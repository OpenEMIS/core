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

class BatchShell extends AppShell {
    
    public $uses = array('BatchProcess','Reports.Report','Reports.BatchReport');
	
	public $tasks = array('Common','Kml','Csv','Vcf','Ind','Est', 'Pdf');
    
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
			   
			   //DOUBLE CHECK if Status was Aborted while report is being process - else just proceed with the Next QUEUE
               $check = $this->BatchProcess->find('first',array('conditions'=>array('id'=>$id)));
               if($check === false) continue;
			   
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
        }else if( $file_type == 'csv_custom'){
			$this->Csv->genCSVCustom($settings);
        }elseif($file_type == 'kml'){
			$this->Kml->genKML($settings);
		}elseif($file_type == 'vcf'){
			$this->Vcf->genVCF($settings);
        }elseif($file_type == 'ind' || $file_type == 'cus'){
			$this->Ind->genIND($settings);
        }elseif($file_type == 'pdf'){
			$this->Pdf->genPDF($settings);
        }elseif($file_type == 'est'){
			$this->Est->genEST($settings);
        }
		
    }
}

?>

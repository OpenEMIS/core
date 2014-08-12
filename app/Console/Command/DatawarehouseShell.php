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

class DatawarehouseShell extends AppShell {
    public $uses = array('DataProcessing.BatchProcess','Datawarehouse.DatawarehouseIndicator');
	
  	public $tasks = array('Common');
      
    public function main(){

    }

    public function _welcome(){
  	
    }
    public function run() {
        App::import('Component', 'Session');

        $language = $this->args[0];
        $areaLevelID = $this->args[1];
        $schoolYearID =$this->args[2]; 

        Configure::write('Config.language', $language);




        //GET ALL the QUEUED UP Process
        $data = $this->BatchProcess->find('list',array('fields'=>array('id','reference_id'),'conditions'=>array('status'=>1,'reference_table'=>'datawarehouse_indicators')));  
        //PROCESS each item in QUEUE
       foreach($data as $id => $ReportRef_id){
	       
          try{
    			     $errLog = '';
      			   
      			   
                //DOUBLE CHECK if Status was Aborted or what - else just proceed with the Next QUEUE
                $check = $this->BatchProcess->find('first',array('conditions'=>array('BatchProcess.id'=>$id)));
          
               
                if($check['BatchProcess']['status'] != 1) continue;

                // Update the status for the one Being Processed to (2) Processing
                $this->Common->updateStatus($id,2);
         
        
                $settings['areaLevelId'] = $areaLevelID;
                $settings['schoolYearId'] = $schoolYearID;
                
                $settings['indicatorId'] = $ReportRef_id;
                $this->genReport($id, $settings);
      			   
        			  //DOUBLE CHECK if Status was Aborted while report is being process - else just proceed with the Next QUEUE
                $check = $this->BatchProcess->find('first',array('conditions'=>array('BatchProcess.id'=>$id)));
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
	
    public function genReport($BatchProcessId, $settings){ 
    		$this->autoRender = false;
        try {
          $this->BatchProcess->start($BatchProcessId);
          $settings['onBeforeGenerate'] = array('callback' => array($this->BatchProcess, 'check'), 'params' => array($BatchProcessId));
          $settings['onError'] = array('callback' => array($this->BatchProcess, 'error'), 'params' => array($BatchProcessId));
          
     
          $format = 'Datawarehouse';
          $componentObj = 'DevInfoComponent';
          App::uses($componentObj, $format.'.Controller/Component');
          $component = new $componentObj(new ComponentCollection);
          $component->init();
          $log = $component->export($settings);
          $this->BatchProcess->completed($BatchProcessId, $log);
        } catch(Exception $ex) {
          echo $ex->getMessage() . "\n\n";
        }
		
    }
}

?>

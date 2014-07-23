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

class PopulationController extends AppController {
    public $headerSelected = 'Administration';
    
	public $uses = Array(
        'Area',
        'AreaLevel',
        'Population'
	);
	
	public $components = array(
        'DateTime'
    );

	public function beforeFilter() {
        parent::beforeFilter();
		$this->bodyTitle = "Administration";
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		
	}
	
	public function index() {
		$this->Navigation->addCrumb('Population');
        $highestLevel = array();
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1', 'Area.visible' => 1)));
        $this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
        $highestLevel[] = $topArea;

        if($this->request->is('post')) {
            for ($i = 0; $i < count($this->request->data['Population'])-1; $i++) {
                //echo 'area_level_'. $i . ': '. $this->request->data['Population']['area_level_'.$i] .'<br/>';
                $area = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $this->request->data['Population']['area_level_'.$i], 'Area.visible' => 1)));
                
                $this->Utility->unshiftArray($area, array('0'=>'--'.__('Select').'--'));
                $highestLevel[] = $area;
                //echo '<br/>';
            }

            $this->set('selectedYear', (isset($this->request->data['year']))? $this->request->data['year']:intval(date('Y')));
            if(end($this->request->data['Population']) == 0 ){
                array_pop($this->request->data['Population']);
            }
            $this->set('initAreaSelection', (isset($this->request->data['Population']) && count($this->request->data['Population']) > 0)?$this->request->data['Population']: null);
        }
		
		$currentYear = intval(date('Y'));
		$selectedYear = isset($this->params->pass[0])? intval($this->params->pass[0]) : $currentYear;
		
		$yearList = $this->DateTime->generateYear();
		krsort($yearList);
		
		$areaId = isset($this->params->pass[1])? intval($this->params->pass[1]) : 0;
		
        $data = $this->Utility->formatResult($this->Population->getPopulationData($selectedYear, $areaId));
		
		$this->set(compact('selectedYear', 'levels', 'highestLevel', 'yearList', 'areaId', 'data'));

		//$this->set('levels', $levels);
        //$this->set('highestLevel',$areas);	

	}

	public function edit() {
		$this->Navigation->addCrumb('Population', array('controller' => 'Population', 'action' => 'index'));
        $this->Navigation->addCrumb('Edit');

        $areas = array();
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1', 'Area.visible' => 1)));
        $this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
        $areas[] = $topArea;
		
		$currentYear = intval(date('Y'));
		$selectedYear = isset($this->params->pass[0])? intval($this->params->pass[0]) : $currentYear;
		
		$areaId = isset($this->params->pass[1])? intval($this->params->pass[1]) : 0;
		
        if($this->request->is('post')) {
			$deletedIdStr = $this->request->data['Population']['idsToBeDeleted'];
			unset($this->request->data['Population']['idsToBeDeleted']);
			$idsArr = array();
			if(!empty($deletedIdStr)){
				$idsArr = explode(',', $deletedIdStr);
			}
			//pr($idsArr);die;
			
			if(!empty($this->request->data['Population'])){
				$populationData = $this->request->data['Population'];
				//pr($populationData);die;
				
				foreach($populationData AS $row){
					$id = intval($row['id']);
					$age = intval($row['age']);
					$source = $row['source'];
					
					if($age > 0 && !empty($source)){
						$existingRecords = $this->Population->getRecordsCount($age, $selectedYear, $source, $areaId);
						
						if($id == 0){
							if($existingRecords == 0){
								$this->Population->create();

								$row['data_source'] = 0;
								$row['year'] = $selectedYear;
								$row['area_id'] = $areaId;
								
								$this->Population->save(array('Population' => $row));
							}
						}else{
							if($existingRecords == 1){
								$this->Population->save(array('Population' => $row));
							}
						}
					}
				}
			}
			
			foreach($idsArr AS $id){
				if(!empty($id)){
					$this->Population->delete($id);
				}
			}
			
			return $this->redirect(array('action' => 'index', $selectedYear, $areaId));
        }
		
		$yearList = $this->DateTime->generateYear();
		krsort($yearList);
		
		$data = $this->Utility->formatResult($this->Population->getPopulationData($selectedYear, $areaId));
		
		$this->set(compact('selectedYear', 'levels', 'highestLevel', 'yearList', 'areaId', 'data'));
	}
	
	public function loadData() {
		$this->layout = false;
		
		$year = isset($this->params->pass[0]) ? intval($this->params->pass[0]) : date('Y');
		$areaId = isset($this->params->pass[1]) ? intval($this->params->pass[1]) : 0;
		
        $data = $this->Utility->formatResult($this->Population->getPopulationData($year, $areaId));
        $this->set(compact('data'));
    }
	
	public function loadForm() {
		$this->layout = false;
		
		$year = isset($this->params->pass[0]) ? intval($this->params->pass[0]) : date('Y');
		$areaId = isset($this->params->pass[1]) ? intval($this->params->pass[1]) : 0;
		
        $data = $this->Utility->formatResult($this->Population->getPopulationData($year, $areaId));
        $this->set(compact('data'));
    }
	
	public function addFormRow() {
		$this->layout = false;
		
		$newRowIndex = isset($this->params->pass[0]) ? intval($this->params->pass[0]) : 0;
		
        $this->set(compact('newRowIndex'));
    }

}

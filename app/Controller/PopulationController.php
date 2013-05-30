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
    public $headerSelected = 'Settings';
    
	public $uses = Array(
        'Area',
        'AreaLevel',
        'Population'
	);

	public function beforeFilter() {
        parent::beforeFilter();
		$this->bodyTitle = "Settings";
		$this->Navigation->addCrumb('Settings', array('controller' => 'Setup', 'action' => 'index'));
		
	}
	
	public function index() {
		$this->Navigation->addCrumb('Population');
        $areas = array();
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1', 'Area.visible' => 1)));
        $this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
        $areas[] = $topArea;
/*
        // add new population
        if($this->request->is('post')){
        	//echo '<pre>';
            //var_dump($this->request->data);
            //echo '</pre>';
            if(isset($this->request->data['previousAction']) && $this->request->data['previousAction'] == 'edit'){

            }
                //$this->Population->savePopulationData($this->request->data['Population']);
        }
        */

        if($this->request->is('post')) {
            for ($i = 0; $i < count($this->request->data['Population'])-1; $i++) {
                //echo 'area_level_'. $i . ': '. $this->request->data['Population']['area_level_'.$i] .'<br/>';
                $area = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $this->request->data['Population']['area_level_'.$i], 'Area.visible' => 1)));
                
                $this->Utility->unshiftArray($area, array('0'=>'--'.__('Select').'--'));
                $areas[] = $area;
                //echo '<br/>';
            }

            $this->set('selectedYear', (isset($this->request->data['year']))? $this->request->data['year']:intval(date('Y')));
            if(end($this->request->data['Population']) == 0 ){
                array_pop($this->request->data['Population']);
            }
            $this->set('initAreaSelection', (isset($this->request->data['Population']) && count($this->request->data['Population']) > 0)?$this->request->data['Population']: null);
        }


		$this->set('levels', $levels);
        $this->set('highestLevel',$areas);	

	}

	public function edit() {
		$this->Navigation->addCrumb('Population', array('controller' => 'Population', 'action' => 'index'));
        $this->Navigation->addCrumb('Edit');

        $areas = array();
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1', 'Area.visible' => 1)));
        $this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
        $areas[] = $topArea;

        if($this->request->is('post')) {
        	//echo '<pre>';
        	//var_dump($this->request->data);
            for ($i = 0; $i < count($this->request->data['Population'])-1; $i++) {
                //echo 'area_level_'. $i . ': '. $this->request->data['Population']['area_level_'.$i] .'<br/>';
                $area = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $this->request->data['Population']['area_level_'.$i], 'Area.visible' => 1)));
                
                $this->Utility->unshiftArray($area, array('0'=>'--'.__('Select').'--'));
                $areas[] = $area;
                //echo '<br/>';
            }
        	//echo '</pre>';


            $this->set('selectedYear', (isset($this->request->data['year']))? $this->request->data['year']:intval(date('Y')));
            if(end($this->request->data['Population']) == 0 ){
                array_pop($this->request->data['Population']);
            }
            $this->set('initAreaSelection', (isset($this->request->data['Population']))?$this->request->data['Population']: null);
        }

        $this->set('levels', $levels);
        $this->set('highestLevel',$areas);    

	}

	public function viewAreaChildren($id) {
        $this->autoRender = false;
        $value =$this->Area->find('list',array('conditions'=>array('Area.parent_id' => $id, 'Area.visible' => 1)));
        $this->Utility->unshiftArray($value, array('0'=>'--'.__('Select').'--'/*, '1' => 'ALL '*/));
        echo json_encode($value);
        
    }

	public function viewData($year = null, $areaId, $parentAreaId = 0) {
        $this->autoRender = false;
        $year = (is_null($year))? intval(date('Y')): $year ;
        
        //$params = array(
        //	'fields' => array('Population.id', 'Population.age', 'Population.year', 'Population.source', 'Population.male', 'Population.female', 'Population.area_id')
        //);

        //$params['conditions']['Population.year'] = $year;

        if($parentAreaId > 0 && $areaId == 0){
	        //$params['conditions']['Population.area_id'] = $parentAreaId;
            $areaId = $parentAreaId;
        }//else{
	        //$params['conditions']['Population.area_id'] = $areaId;
        //}


	    //$value =$this->Population->find('all', $params);

        //echo json_encode($value);
        //echo $areaId;
        $data = $this->Utility->formatResult($this->Population->getPopulationData($year, $areaId));
        //var_dump($data);die();
        echo json_encode($data);
        
    }

    public function populationAjax() {
        $this->autoRender = false;
        $return = array();
        $return = $this->Population->savePopulationData($this->data);
        // check if there data are updated/inserted
        //   if data are updated/inserted pass msg "Records has been added/updated successfully".
        // else 
        //   tell the user that data was not not inserted or updated.
        echo json_encode($return);
    }
}

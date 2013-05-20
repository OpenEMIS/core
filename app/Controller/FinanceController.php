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

class FinanceController extends AppController {
	public $uses = Array(
		'Area',
		'AreaLevel',
		'PublicExpenditure',
		'PublicExpenditureEducationLevel'
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Settings';
		$this->Navigation->addCrumb('Finance', array('controller' => 'Finance', 'action' => 'index'));
	}
	
	public function index() {
		$this->Navigation->addCrumb('Total Public Expenditure');
		
		$result = $this->PublicExpenditure->getPublicExpenditureByYearAndArea(2012, 1);
		// pr($result);

		$areas = array();
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1', 'Area.visible' => 1)));
        $this->Utility->unshiftArray($topArea, array('0'=>'--Select--'));
        $areas[] = $topArea;

        if($this->request->is('post')) {
            for ($i = 0; $i < count($this->request->data['Finance'])-1; $i++) {
                //echo 'area_level_'. $i . ': '. $this->request->data['Finance']['area_level_'.$i] .'<br/>';
                $area = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $this->request->data['Finance']['area_level_'.$i], 'Area.visible' => 1)));
                
                $this->Utility->unshiftArray($area, array('0'=>'--Select--'));
                $areas[] = $area;
                //echo '<br/>';
            }

            $this->set('selectedYear', (isset($this->request->data['year']))? $this->request->data['year']:intval(date('Y')));
            if(end($this->request->data['Finance']) == 0 ){
                array_pop($this->request->data['Finance']);
            }
            $this->set('initAreaSelection', (isset($this->request->data['Finance']) && count($this->request->data['Finance']) > 0)?$this->request->data['Finance']: null);
        }
//        array_pop($levels);
//        array_pop($areas);
		$this->set('levels', $levels);
		$this->set('highestLevel', $areas);
	}

	public function edit($id = null) {
		$this->bodyTitle = 'National Denominators';
		
		$areas = array();
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1', 'Area.visible' => 1)));
        $this->Utility->unshiftArray($topArea, array('0'=>'--Select--'));
        $areas[] = $topArea;
		
		if($this->request->is('post')) {
        	//echo '<pre>';
        	// var_dump($this->request->data);
            for ($i = 0; $i < count($this->request->data['Finance'])-1; $i++) {
                //echo 'area_level_'. $i . ': '. $this->request->data['Finance']['area_level_'.$i] .'<br/>';
                $area = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $this->request->data['Finance']['area_level_'.$i], 'Area.visible' => 1)));
                
                $this->Utility->unshiftArray($area, array('0'=>'--'.__('Select').'--'));
                $areas[] = $area;
                //echo '<br/>';
            }
        	//echo '</pre>';


            $this->set('selectedYear', (isset($this->request->data['year']))? $this->request->data['year']:intval(date('Y')));
            if(end($this->request->data['Finance']) == 0 ){
                array_pop($this->request->data['Finance']);
            }
            $this->set('initAreaSelection', (isset($this->request->data['Finance']))?$this->request->data['Finance']: null);
        }

		$this->set('levels', $levels);
		$this->set('highestLevel', $areas);
	}

	public function viewGNP($year = null, $countryId = 0) {
		$this->autoRender = false;
		$year = (is_null($year))? intval(date('Y')): $year ;

		$gnpData = $this->PublicExpenditure->find('first', array(
				'conditions' => array('year' => $year),
        		'fields' => array('gross_national_product'),
        		'order' => array('gross_national_product DESC')
			));
		$gnpData['PublicExpenditure']['currency'] = $this->Session->read('configItem.currency');

		if (!$gnpData || $countryId == 0) {
			$gnpData['PublicExpenditure']['gross_national_product'] = null;
		}
		echo json_encode($gnpData['PublicExpenditure']);
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

        $data = array();
        if($parentAreaId > 0 && $areaId == 0){
            $areaId = $parentAreaId;
        }

        $data = $this->PublicExpenditure->getPublicExpenditureData($year, $parentAreaId, $areaId);

//         pr($data); die();
		echo json_encode($data);
    }

	public function financeAjax() {
		$this->autoRender = false;
		
		if($this->request->is('post')) {
			$records = $this->data;
			$keys = array();
			$gnp = 0;
			$year = 0;

			foreach ($records as $record) {
				$gnp = $record['gross_national_product'];
				$year = $record['year'];
				if ($record['id'] > 0) {
					$this->PublicExpenditure->save($record);
				} else if ($record['id'] == 0) {
					$this->PublicExpenditure->save($record);
					$keys[$record['index']] = $this->PublicExpenditure->id;
				}
			}

			$this->PublicExpenditure->saveGNP($year, $gnp);

			return json_encode($keys);
		}
	}

	public function financePerEducationAjax() {
		$this->autoRender = false;
		
		if($this->request->is('post')) {

			$records = $this->data;
			$keys = array();
			$education_level_id = 0;
			$value = 0;
			$year = 0;

			foreach ($records as $record) {
				$value = $record['value'];
				$education_level_id = $record['education_level_id'];
				$year = $record['year'];
				if ($record['id'] > 0) {
					$this->PublicExpenditureEducationLevel->save($record);
				} else if ($record['id'] == 0) {
					$this->PublicExpenditureEducationLevel->save($record);
					$keys[$record['index']] = $this->PublicExpenditureEducationLevel->id;
				}
			}


			return json_encode($keys);
		}
	}

	public function financePerEducationLevel() {
		$this->Navigation->addCrumb('Total Public Expenditure Per Education Level');

		// pr($this->PublicExpenditureEducationLevel->getEducationLevels());
		// pr($this->PublicExpenditureEducationLevel->getPublicExpenditureByYearAndArea(2012,1,51));

		$areas = array();
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1', 'Area.visible' => 1)));
        $this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
        $areas[] = $topArea;

        $educationLevels = $this->PublicExpenditureEducationLevel->getEducationLevels();

        if($this->request->is('post')) {
            for ($i = 0; $i < count($this->request->data['Finance'])-1; $i++) {
                //echo 'area_level_'. $i . ': '. $this->request->data['Finance']['area_level_'.$i] .'<br/>';
                $area = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $this->request->data['Finance']['area_level_'.$i], 'Area.visible' => 1)));
                
                $this->Utility->unshiftArray($area, array('0'=>'--'.__('Select').'--'));
                $areas[] = $area;
            }

            $this->set('selectedYear', (isset($this->request->data['year']))? $this->request->data['year']:intval(date('Y')));
            if(end($this->request->data['Finance']) == 0 ){
                array_pop($this->request->data['Finance']);
            }
            $this->set('initAreaSelection', (isset($this->request->data['Finance']) && count($this->request->data['Finance']) > 0)?$this->request->data['Finance']: null);
        }

		$this->set('levels', $levels);
		$this->set('highestLevel', $areas);
		$this->set('eduLevels', $educationLevels);

	}

	public function financePerEducationLevelEdit() {
		$this->Navigation->addCrumb('Total Public Expenditure Per Education Level');
		
		$areas = array();
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1', 'Area.visible' => 1)));
        $this->Utility->unshiftArray($topArea, array('0'=>'--'.__('Select').'--'));
        $areas[] = $topArea;
		
        $educationLevels = $this->PublicExpenditureEducationLevel->getEducationLevels();

		if($this->request->is('post')) {
        	//echo '<pre>';
        	// var_dump($this->request->data);
            for ($i = 0; $i < count($this->request->data['Finance'])-1; $i++) {
                //echo 'area_level_'. $i . ': '. $this->request->data['Finance']['area_level_'.$i] .'<br/>';
                $area = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $this->request->data['Finance']['area_level_'.$i], 'Area.visible' => 1)));
                
                $this->Utility->unshiftArray($area, array('0'=>'--'.__('Select').'--'));
                $areas[] = $area;
                //echo '<br/>';
            }
        	//echo '</pre>';


            $this->set('selectedYear', (isset($this->request->data['year']))? $this->request->data['year']:intval(date('Y')));
            if(end($this->request->data['Finance']) == 0 ){
                array_pop($this->request->data['Finance']);
            }
            $this->set('initAreaSelection', (isset($this->request->data['Finance']))?$this->request->data['Finance']: null);
        }

		$this->set('levels', $levels);
		$this->set('highestLevel', $areas);
		$this->set('eduLevels', $educationLevels);
	}

	public function viewPerEducationData($year = null, $areaId, $parentAreaId = 0) {
        $this->autoRender = false;
        $year = (is_null($year))? intval(date('Y')): $year ;

        $data = array();
        if($parentAreaId > 0 && $areaId == 0){
            $areaId = $parentAreaId;
        }

        // $data = $this->PublicExpenditure->getPublicExpenditureData($year, $parentAreaId, $areaId);
        $data = $this->PublicExpenditureEducationLevel->getPublicExpenditureData($year, $parentAreaId, $areaId);

        // pr($data); 
        // die();
		echo json_encode($data);
    }
}
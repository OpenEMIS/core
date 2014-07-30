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

class DashArea extends DashboardsAppModel {
	public $useDbConfig = 'di6';
	public $alias = 'DIArea';
	public $actsAs = array('Tree');
	//public $useTable = 'ut_area_en';
	public $countryIndicator = array('Jordan' => '2ed8e897-7d7f-4970-a3ae-4c2e40277fdc');
	
	public function getCountry(){
		$this->setSource('ut_area_'.$this->setupUseTableLang());
		
		$options['conditions'] = array('Area_Parent_NId' => -1);
		$data = $this->find('first', $options);

		return $data;
	}
	
	public function getAreasByLevel($level, $mode = 'all', $withCode = true){
		$this->setSource('ut_area_'.$this->setupUseTableLang());
		
		$options['conditions'] = array('Area_Level' => $level);
		
		$data = $this->find('all', $options);
		if($mode == 'list'){
			$listData = array();
			foreach($data as $item){
				$item = $item['DIArea'];
				if($withCode){
					$listData[$item['Area_NId']] = sprintf('%s - %s', $item['Area_ID'],$item['Area_Name']);
				}
				else{
					$listData[$item['Area_NId']] = $item['Area_Name'];
				}
			}
			
			$data = $listData;
		}
		return $data;
	}
	
	public function getAreaByAreaGId($gid = NULL) {
		$this->setSource('ut_area_'.$this->setupUseTableLang());
		if (empty($gid)) {
			$gid = $this->countryIndicator['Jordan'];
		}

		$options['conditions'] = array('Area_GId' => $gid);
		$data = $this->find('first', $options);

		return $data;
	}
	
	public function getAreaById($id, $mode = 'all') {
		$this->setSource('ut_area_'.$this->setupUseTableLang());

		$options['conditions'] = array('Area_Nid' => $id);
		$options['fields'] = array('Area_Nid' ,'Area_ID' ,'Area_Name');
		$data = $this->find('all', $options);

		if($mode == 'list'){
			$data = $this->processAreaData($data, false);
		}
		
		return $data;
	}

	public function getAllChildByLevel($id, $lvl, $mode = 'all', $withCode){
		$this->setSource('ut_area_'.$this->setupUseTableLang());
		$options['conditions'] = array('Area_NId' => $id);
		$parentData = $this->find('first', $options);
		
		$options = array();
		$options['conditions'] = array('lft > ' => $parentData['DIArea']['lft'], 'rght < '=> $parentData['DIArea']['rght'], 'Area_Level' => $lvl);
		$data = $this->find('all', $options);
		if($mode == 'list'){
			$data = $this->processAreaData($data, $withCode);
		}
		return $data;
	}
	
	public function getChildLevel($mode = 'all', $id = -1, $withCode){
		$this->setSource('ut_area_'.$this->setupUseTableLang());
		$options['conditions'] = array('Area_Parent_NId' => $id);
		$options['order'] = array('Area_ID');
		$data = $this->find('all', $options);
		
		if($mode == 'list'){
			$data = $this->processAreaData($data, $withCode);
		}
		
		return $data;
	}
	
	public function getAreaName($id){
		$this->setSource('ut_area_'.$this->setupUseTableLang());
		$data = $this->find('first', array( 'conditions' => array('Area_NId' => $id), 'fields' => array('Area_Name')));
		return $data['DIArea']['Area_Name'];
	}
	
	public function getParentInfo($id){
		$this->setSource('ut_area_'.$this->setupUseTableLang());
		$parentData = $this->find('first', array( 'conditions' => array('Area_NId' => $id), 'fields' => array('Area_Parent_NId')));
		$parentID = $parentData['DIArea']['Area_Parent_NId'];
		$data = $this->find('first', array( 'conditions' => array('Area_NId' => $parentID)));
		return $data;
	}

	
	private function processAreaData($data, $withCode){
		$listData = array();
		foreach($data as $item){
			$item = $item['DIArea'];
			if($withCode){
				$listData[$item['Area_NId']] = sprintf('%s - %s', $item['Area_ID'],$item['Area_Name']);
			}
			else{
				$listData[$item['Area_NId']] = $item['Area_Name'];
			}
		}

		return $listData;
	}
}

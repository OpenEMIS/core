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

class JORArea extends DashboardsAppModel {
	public $useDbConfig = 'dashboardJor';
	public $useTable = 'ut_area_en';
	
	public function getChildLevel($mode = 'all', $id = -1){
		$options['conditions'] = array('Area_Parent_NId' => $id);
		$data = $this->find('all', $options);
		
		if($mode == 'list'){
			$listData = array();
			foreach($data as $item){
				$item = $item['JORArea'];
				$listData[$item['Area_NId']] = sprintf('%s - %s', $item['Area_ID'],$item['Area_Name']);
			}
			
			$data = $listData;
		}
		return $data;
	}
	
	public function getAreaName($id){
		$data = $this->find('first', array( 'conditions' => array('Area_NId' => $id), 'fields' => array('Area_Name')));
		return $data['JORArea']['Area_Name'];
	}
	
	public function getParentInfo($id){
		$parentData = $this->find('first', array( 'conditions' => array('Area_NId' => $id), 'fields' => array('Area_Parent_NId')));
		$parentID = $parentData['JORArea']['Area_Parent_NId'];
		$data = $this->find('first', array( 'conditions' => array('Area_NId' => $parentID)));
		return $data;
	}


	/*public function createRecord($data) {
		$model = array(
			'Data' => array(
				'Start_Date' => null,
				'End_Date' => null,
				'Data_Denominator' => 0,
				'FootNote_NId' => -1,
				'IC_IUS_Order' => null
			)
		);
		
		$model['Data'] = array_merge($model['Data'], $data);
		
		$this->create();
		$this->save($model);
	}*/
}

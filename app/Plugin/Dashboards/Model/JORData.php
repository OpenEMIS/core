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

class JORData extends DashboardsAppModel {
	public $useDbConfig = 'dashboardJor';
	public $useTable = 'ut_data';
	
	
	public function getData($areaId, $filter = array()){
		$options['conditions'] = array('Area_NId' => $areaId );
		if(!empty($filter)){
			$options['conditions'] = array_merge($options['conditions'], $filter);
		}
		$data = $this->find('all', $options);
		
		return $data;
	}
	
	public function getFDData($areaId,$yearId, $filter = array()){
		
		$options['joins'] = array(
			array(
				'table' => 'ut_area_en',
				'alias' => 'JORArea',
				'type' => 'LEFT',
				'conditions' => array(
					'JORData.Area_NId = JORArea.Area_NId',
				)
			)
		);
		
		$options['conditions'] = array('JORData.TimePeriod_NId' => $yearId,'JORArea.Area_Parent_NId' => $areaId );
		if(!empty($filter)){
			$options['conditions'] = array_merge($options['conditions'], $filter);
		}
		
		$data = $this->find('all', $options);

		return $data;
	}
	
	public function getTotalKGData($areaId, $filter = array()){
		$options['conditions'] = array('Area_NId' => $areaId );
		if(!empty($filter)){
			$options['conditions'] = array_merge($options['conditions'], $filter);
		}
		
		$options['joins'] = array(
			array(
				'table' => 'ut_indicator_en',
				'alias' => 'JORIndicator',
				'type' => 'LEFT',
				'conditions' => array(
					'JORData.Indicator_NId = JORIndicator.Indicator_NId',
				)
			)
		);
		
		$options['fields'] = array('JORData.*', 'JORIndicator.Indicator_Name');
		
		$data = $this->find('all', $options);
		
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

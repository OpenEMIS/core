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

class JORIndicatorUnitSubgroup extends DashboardsAppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_indicator_unit_subgroup';
	
	public function getIUSNid($indicatorIds, $unitNIds, $subgroupValGId) {
		$options['joins'] = array(
			array(
				'table' => 'ut_unit_en',
				'alias' => 'JORUnit',
				'type' => 'LEFT',
				'conditions' => array(
					'JORUnit.Unit_NId = JORIndicatorUnitSubgroup.Unit_NId',
				)
			),
			array(
				'table' => 'ut_subgroup_vals_en',
				'alias' => 'JORSubgroupVals',
				'type' => 'LEFT',
				'conditions' => array(
					'JORSubgroupVals.Subgroup_Val_NId = JORIndicatorUnitSubgroup.Subgroup_Val_NId',
				)
			)
		);
		$options['fields'] = array('JORIndicatorUnitSubgroup.IUSNId','JORIndicatorUnitSubgroup.Indicator_NId','JORIndicatorUnitSubgroup.Unit_NId','JORIndicatorUnitSubgroup.Subgroup_Val_NId');
		$options['conditions'] = array('JORIndicatorUnitSubgroup.Indicator_NId' => $indicatorIds, 'JORIndicatorUnitSubgroup.Unit_NId' => $unitNIds, 'JORSubgroupVals.Subgroup_Val_GId' => $subgroupValGId);
		//pr($options);die;
		$data = $this->find('all', $options);

		$finalData = array();
		foreach($data as $obj){
			$finalData[] = $obj['JORIndicatorUnitSubgroup']['IUSNId'];
		}
		
		return $finalData;
	}
}

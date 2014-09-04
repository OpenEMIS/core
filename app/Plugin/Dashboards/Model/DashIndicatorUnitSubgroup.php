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

class DashIndicatorUnitSubgroup extends DashboardsAppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_indicator_unit_subgroup';
	public $alias = 'IndicatorUnitSubgroup';
	
	/*public function getIUSNid($indicatorIds, $unitNIds, $subgroupValGId) {
		$options['joins'] = array(
			array(
				'table' => 'ut_unit_en',
				'alias' => 'Unit',
				'type' => 'LEFT',
				'conditions' => array(
					'Unit.Unit_NId = IndicatorUnitSubgroup.Unit_NId',
				)
			),
			array(
				'table' => 'ut_subgroup_vals_en',
				'alias' => 'SubgroupVals',
				'type' => 'LEFT',
				'conditions' => array(
					'SubgroupVals.Subgroup_Val_NId = IndicatorUnitSubgroup.Subgroup_Val_NId',
				)
			)
		);
		$options['fields'] = array('IndicatorUnitSubgroup.IUSNId','IndicatorUnitSubgroup.Indicator_NId','IndicatorUnitSubgroup.Unit_NId','IndicatorUnitSubgroup.Subgroup_Val_NId');
		$options['conditions'] = array('IndicatorUnitSubgroup.Indicator_NId' => $indicatorIds, 'IndicatorUnitSubgroup.Unit_NId' => $unitNIds, 'SubgroupVals.Subgroup_Val_GId' => $subgroupValGId);
		//pr($options);die;
		$data = $this->find('all', $options);

		$finalData = array();
		foreach($data as $obj){
			$finalData[] = $obj['IndicatorUnitSubgroup']['IUSNId'];
		}
		
		return $finalData;
	}*/
	
	
	public function getIUSByIndividualGId($indicatorGId, $unitGId, $subgroupValGId) {
		$options['joins'] = array(
			array(
				'table' => 'ut_unit_en',
				'alias' => 'Unit',
				//'type' => 'LEFT',
				'conditions' => array(
					'Unit.Unit_NId = IndicatorUnitSubgroup.Unit_NId',
				)
			),
			array(
				'table' => 'ut_subgroup_vals_en',
				'alias' => 'SubgroupVal',
				//'type' => 'LEFT',
				'conditions' => array(
					'SubgroupVal.Subgroup_Val_NId = IndicatorUnitSubgroup.Subgroup_Val_NId',
				)
			),
			array(
				'table' => 'ut_indicator_en',
				'alias' => 'Indicator',
				//'type' => 'LEFT',
				'conditions' => array(
					'Indicator.Indicator_NId = IndicatorUnitSubgroup.Indicator_NId',
				)
			)
			
		);
		$options['fields'] = array('DISTINCT SubgroupVal.Subgroup_Val_NId', 'SubgroupVal.Subgroup_Val', 'Unit.Unit_Name', 'Indicator.Indicator_Name', 'IndicatorUnitSubgroup.IUSNId');
	//	$options['fields'] = array('IndicatorUnitSubgroup.IUSNId','IndicatorUnitSubgroup.Indicator_NId','IndicatorUnitSubgroup.Unit_NId','IndicatorUnitSubgroup.Subgroup_Val_NId');
		$options['conditions'] = array('Indicator.Indicator_GId' => $indicatorGId, 'Unit.Unit_GId' => $unitGId, 'SubgroupVal.Subgroup_Val_GId' => $subgroupValGId);
		//pr($options);die;
		$data = $this->find('all', $options);

	/*	$finalData = array();
		foreach($data as $obj){
			$finalData[] = $obj['IndicatorUnitSubgroup']['IUSNId'];
		}*/
		
		return $data;
	}
}

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

class VisualizerIndicatorUnitSubgroup extends VisualizerAppModel {

	public $useDbConfig = 'di6';
	public $useTable = 'ut_indicator_unit_subgroup';
	public $alias = 'IndicatorUnitSubgroup';
	
	public function getUnits($indicatorId, $order = NULL) {
		$order = (empty($order))? NULL:array($order);
		
		$data = $this->find('all', array(
			'conditions' => array('IndicatorUnitSubgroup.Indicator_NId' => $indicatorId),
			'fields' => array('DISTINCT Unit.Unit_NId', 'Unit.Unit_Name', 'Indicator.Indicator_Name'),
				'joins' => array(
					array(
						'table' => 'ut_unit_en',
						'alias' => 'Unit',
						'conditions' => array('Unit.Unit_NId = IndicatorUnitSubgroup.Unit_NId')
					),
					array(
						'table' => 'ut_indicator_en',
						'alias' => 'Indicator',
						'conditions' => array('Indicator.Indicator_NId = IndicatorUnitSubgroup.Indicator_NId')
					),
				),
			'order' => $order
		));
		
		return $data;
	}

	public function getDimensions($options, $order = NULL){
		$order = (empty($order))? NULL:array($order);
		$_qOptions['order'] = $order; 
		if(isset($options['IUS'])){
			$ius = $options['IUS'];
			$_qOptions['conditions'] = array('IndicatorUnitSubgroup.IUSNId' => $ius);
		}
		else{
			$indicatorId = $options['indicators'];
			$unitIds = $options['units'];
			$_qOptions['conditions'] = array('IndicatorUnitSubgroup.Indicator_NId' => $indicatorId, 'IndicatorUnitSubgroup.Unit_NId'=> $unitIds);
		}
		
		$_qOptions['fields'] = array('DISTINCT SubgroupVal.Subgroup_Val_NId', 'SubgroupVal.Subgroup_Val', 'Unit.Unit_Name', 'Indicator.Indicator_Name', 'IndicatorUnitSubgroup.IUSNId');
		$_qOptions['joins'] = array(
					array(
						'table' => 'ut_subgroup_vals_en',
						'alias' => 'SubgroupVal',
						'conditions' => array('SubgroupVal.Subgroup_Val_NId = IndicatorUnitSubgroup.Subgroup_Val_NId')
					),
					array(
						'table' => 'ut_unit_en',
						'alias' => 'Unit',
						'conditions' => array('Unit.Unit_NId = IndicatorUnitSubgroup.Unit_NId')
					),
					array(
						'table' => 'ut_indicator_en',
						'alias' => 'Indicator',
						'conditions' => array('Indicator.Indicator_NId = IndicatorUnitSubgroup.Indicator_NId')
					),
				);
		
		/*$data = $this->find('all', array(
			'conditions' => array('IndicatorUnitSubgroup.Indicator_NId' => $indicatorId, 'IndicatorUnitSubgroup.Unit_NId'=> $unitIds),
			'fields' => array('DISTINCT SubgroupVal.Subgroup_Val_NId', 'SubgroupVal.Subgroup_Val', 'Unit.Unit_Name', 'Indicator.Indicator_Name', 'IndicatorUnitSubgroup.IUSNId'),
				'joins' => array(
					array(
						'table' => 'ut_subgroup_vals_en',
						'alias' => 'SubgroupVal',
						'conditions' => array('SubgroupVal.Subgroup_Val_NId = IndicatorUnitSubgroup.Subgroup_Val_NId')
					),
					array(
						'table' => 'ut_unit_en',
						'alias' => 'Unit',
						'conditions' => array('Unit.Unit_NId = IndicatorUnitSubgroup.Unit_NId')
					),
					array(
						'table' => 'ut_indicator_en',
						'alias' => 'Indicator',
						'conditions' => array('Indicator.Indicator_NId = IndicatorUnitSubgroup.Indicator_NId')
					),
				)
		));*/
		$data = $this->find('all', $_qOptions);
		
		return $data;
	}
}

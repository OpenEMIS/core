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

class DashData extends DashboardsAppModel {

	public $useDbConfig = 'di6';
	public $useTable = 'ut_data';
	public $alias = 'DIData';

/*	public function getData($filter = array(), $joinMethod = 'inner') {
		$options['fields'] = array(
			'TimePeriod.TimePeriod_NId', 'TimePeriod.TimePeriod',
			'DIArea.Area_NId', 'DIArea.Area_ID', 'DIArea.Area_Name',
			'Indicator.Indicator_NId', 'Indicator.Indicator_Name',
			'Unit.Unit_NId', 'Unit.Unit_Name',
			'SubgroupVal.Subgroup_Val_NId', 'SubgroupVal.Subgroup_Val',
			'IndicatorClassification.IC_NId', 'IndicatorClassification.IC_Name',
			'DIData.Data_NId', 'DIData.Data_Value'
		);
		$options['joins'] = array(
			array(
				'table' => 'ut_timeperiod',
				'alias' => 'TimePeriod',
				'type' => $joinMethod,
				'conditions' => array('TimePeriod.TimePeriod_NId = DIData.TimePeriod_NId')
			),
			array(
				'table' => 'ut_area_en',
				'alias' => 'DIArea',
				'type' => $joinMethod,
				'conditions' => array('DIArea.Area_NId = DIData.Area_NId')
			),
			array(
				'table' => 'ut_indicator_en',
				'alias' => 'Indicator',
				'type' => $joinMethod,
				'conditions' => array('Indicator.Indicator_NId = DIData.Indicator_NId')
			),
			array(
				'table' => 'ut_unit_en',
				'alias' => 'Unit',
				'type' => $joinMethod,
				'conditions' => array('Unit.Unit_NId = DIData.Unit_NId')
			),
			array(
				'table' => 'ut_subgroup_vals_en',
				'alias' => 'SubgroupVal',
				'type' => $joinMethod,
				'conditions' => array('SubgroupVal.Subgroup_Val_NId = DIData.Subgroup_Val_NId')
			),
			array(
				'table' => 'ut_indicator_classifications_en',
				'alias' => 'IndicatorClassification',
				'type' => $joinMethod,
				'conditions' => array('IndicatorClassification.IC_NId = DIData.Source_NId')
			),
		);
		if (!empty($filter)) {
			$options['conditions'] = $filter; //array_merge($options['conditions'], $filter);
		}
		
		
		$data = $this->find('all', $options);

		return $data;
	}

	public function getFDData($areaId, $yearId, $filter = array()) {
		$options['joins'] = array(
			array(
				'table' => 'ut_area_' . $this->setupUseTableLang(),
				'alias' => 'DIArea',
				'type' => 'LEFT',
				'conditions' => array(
					'DIData.Area_NId = DIArea.Area_NId',
				)
			)
		);

		$options['conditions'] = array('DIData.TimePeriod_NId' => $yearId, 'DIArea.Area_Parent_NId' => $areaId);
		if (!empty($filter)) {
			$options['conditions'] = array_merge($options['conditions'], $filter);
		}

		$data = $this->find('all', $options);

		return $data;
	}

	public function getTotalKGData($areaId, $filter = array()) {
		$options['conditions'] = array('Area_NId' => $areaId);
		if (!empty($filter)) {
			$options['conditions'] = array_merge($options['conditions'], $filter);
		}

		$options['joins'] = array(
			array(
				'table' => 'ut_indicator_' . $this->setupUseTableLang(),
				'alias' => 'Indicator',
				'type' => 'LEFT',
				'conditions' => array(
					'DIData.Indicator_NId = Indicator.Indicator_NId',
				)
			)
		);

		$options['fields'] = array('DIData.*', 'Indicator.Indicator_Name');

		$data = $this->find('all', $options);

		return $data;
	}

	/* public function createRecord($data) {
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
	  } */
	
	public function getQueryOptionsSetup($params, $joinMethod = 'inner'){
		$ius = $params['IUS'];
		$areaIds = $params['area'];
		$timeperiodIds = $params['timeperiod'];
		$sourceIds = $params['source'];
		
		$paginateOptions = array(
			'conditions' => array('DIData.IUSNId' => $ius, 'DIData.Area_NId' => $areaIds, 'DIData.TimePeriod_NId' => $timeperiodIds, 'DIData.Source_NId' => $sourceIds),
			'fields' => array(
				'TimePeriod.TimePeriod_NId','TimePeriod.TimePeriod',
				'DIArea.Area_NId', 'DIArea.Area_ID', 'DIArea.Area_Name',
				'Indicator.Indicator_NId', 'Indicator.Indicator_Name',
				'Unit.Unit_NId', 'Unit.Unit_Name',
				'SubgroupVal.Subgroup_Val_NId', 'SubgroupVal.Subgroup_Val',
				'IndicatorClassification.IC_NId', 'IndicatorClassification.IC_Name',
				'DIData.Data_NId', 'DIData.Data_Value'
				),
			'joins' => array(
				array(
					'table' => 'ut_timeperiod',
					'alias' => 'TimePeriod',
					'type' => $joinMethod,
					'conditions' => array('TimePeriod.TimePeriod_NId = DIData.TimePeriod_NId')
				),
				array(
					'table' => 'ut_area_en',
					'alias' => 'DIArea',
					'type' => $joinMethod,
					'conditions' => array('DIArea.Area_NId = DIData.Area_NId')
				),
				array(
					'table' => 'ut_indicator_en',
					'alias' => 'Indicator',
					'type' => $joinMethod,
					'conditions' => array('Indicator.Indicator_NId = DIData.Indicator_NId')
				),
				array(
					'table' => 'ut_unit_en',
					'alias' => 'Unit',
					'type' => $joinMethod,
					'conditions' => array('Unit.Unit_NId = DIData.Unit_NId')
				),
				array(
					'table' => 'ut_subgroup_vals_en',
					'alias' => 'SubgroupVal',
					'type' => $joinMethod,
					'conditions' => array('SubgroupVal.Subgroup_Val_NId = DIData.Subgroup_Val_NId')
				),
				array(
					'table' => 'ut_indicator_classifications_en',
					'alias' => 'IndicatorClassification',
					'type' => $joinMethod,
					'conditions' => array('IndicatorClassification.IC_NId = DIData.Source_NId')
				),
			)
		);
		
		return $paginateOptions;
	}
	
	public function getLatestSourceID($ius,$timeperiod){
		$data = $this->find('first', array(
			'conditions' => array('DIData.IUSNID' => $ius, 'DIData.TimePeriod_NId' => $timeperiod),
			'group' => array('DIData.Source_NId' ),
			'fields' => array('DIData.Source_NId'),
			'order' => 'DIData.Source_NId DESC'
		));
		return $data;
	}
	
}

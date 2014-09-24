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

class VisualizerIndicatorClassification extends VisualizerAppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_indicator_classifications_en';
	public $alias = 'IndicatorClassification';
	
	public function getSource($ius, $timeperiod, $order = NULL) {
		$order = (empty($order))? NULL:array($order);
		$data = $this->find('all', array(
			'fields' => array('DISTINCT IndicatorClassification.IC_NId', 'IndicatorClassification.IC_Name'),
			'conditions' => array('Data.IUSNId' => $ius,'Data.TimePeriod_NId' => $timeperiod),
			'joins' => array(
				array(
					'table' => 'ut_data',
					'alias' => 'Data',
					'conditions' => array('Data.Source_NId = IndicatorClassification.IC_NId')
				),
			),
			'order' => $order
		));
		
		return $data;
	}
	
	public function getListOfClassification( $order = NULL) {
		$order = (empty($order))? NULL:array($order);
		$data = $this->find('list', array(
			'fields' => array('IndicatorClassification.IC_NId', 'IndicatorClassification.IC_Name'),
			'conditions' => array('IndicatorClassification.IC_Type !=' => 'SR'),
			'joins' => array(
				array(
					'table' => 'ut_indicator_classifications_ius',
					'alias' => 'IndicatorClassificationIUS',
					'conditions' => array('IndicatorClassificationIUS.IC_NId = IndicatorClassification.IC_NId')
				),
				array(
					'table' => 'ut_indicator_unit_subgroup',
					'alias' => 'IndicatorUnitSubgroup',
					'conditions' => array('IndicatorUnitSubgroup.IUSNId = IndicatorClassificationIUS.IUSNId')
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
}

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

class VisualizerIndicator extends VisualizerAppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_indicator_en';
	public $alias = 'Indicator';
	
	public function getIndicators($_options){
		$options['fields'] = array('Indicator.Indicator_NId', 'Indicator.Indicator_Name', 'Indicator.Indicator_Info', 'IndicatorClassification.IC_Name');
		$options['joins'] = array(
			array(
				'table' => 'ut_indicator_unit_subgroup',
				'alias' => 'IndicatorUnitSubgroup',
				'conditions' => array('IndicatorUnitSubgroup.Indicator_NId = Indicator.Indicator_NId')
			),
			array(
				'table' => 'ut_indicator_classifications_ius',
				'alias' => 'IndicatorClassificationIUS',
				'conditions' => array('IndicatorClassificationIUS.IUSNId = IndicatorUnitSubgroup.IUSNId')
			),
			array(
				'table' => 'ut_indicator_classifications_en',
				'alias' => 'IndicatorClassification',
				'conditions' => array('IndicatorClassification.IC_NId = IndicatorClassificationIUS.IC_NId')
			),
		);
		
		$options['group'] = array('IndicatorClassification.IC_Name','Indicator.Indicator_Name');
		$options['conditions'] = array('IndicatorClassification.IC_Type != '=> 'SR');
		$options = array_merge($options,$_options);
		$data = $this->find('all',$options);
		
		return $data;
	}
}

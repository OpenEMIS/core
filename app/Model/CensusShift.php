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

App::uses('AppModel', 'Model');

class CensusShift extends AppModel {
	public $belongsTo = array(
		'CensusClass'
	);
	
	public function getShiftId($institutionSiteId, $yearId) {
		$data = $this->find('list', array(
			'fields' => array('CensusShift.id'),
			'joins' => array(
				array(
					'table' => 'census_classes',
					'alias' => 'CensusClass',
					'conditions' => array(
						'CensusClass.id = CensusShift.census_class_id'
					)
				)
			),
			'conditions' => array('CensusClass.institution_site_id' => $institutionSiteId, 'CensusClass.school_year_id' => $yearId)
		));
		return $data;
	}
	
	public function mergeSingleGradeData(&$class, $data) {
		foreach($class as $key => &$obj) {
			
			$shift = array();
			$source = 0;
			$shift_pk = array();

			foreach($data as $value) {
				if($value['census_class_id'] == $obj['id']) {
					if(isset($value['shift_id'])){
						$shiftId = $value['shift_id'];
						$shiftValue = $value['value'];

						$shift['shift_' . $shiftId] = $shiftValue;
						$shift_pk['shift_pk_' . $shiftId] = $value['id'];
					}
				
					$source = $value['source'];
				}
				
				$obj = array_merge($obj, array_merge($shift, $shift_pk, array('shift_source' => $source)));
			}
		}
	}
	
	public function getData($institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusShift.id',
				'CensusShift.census_class_id',
				'CensusShift.shift_id',
				'CensusShift.value',
				'CensusShift.source',
			),
			'joins' => array(
				array(
					'table' => 'census_classes',
					'alias' => 'CensusClass',
					'conditions' => array(
						'CensusClass.id = CensusShift.census_class_id'
					)
				)
			),
			'conditions' => array(
				'CensusClass.school_year_id' => $yearId,
				'CensusClass.institution_site_id' => $institutionSiteId
			)
		));
		
		return $data;
	}
}
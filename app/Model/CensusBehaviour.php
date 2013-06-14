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

class CensusBehaviour extends AppModel {
	public $belongsTo = array(
		'SchoolYear',
		'StudentBehaviourCategory',
		'InstitutionSite'
	);
	
	public function getCensusData($siteId, $yearId) {
		$StudentBehaviourCategory = ClassRegistry::init('Students.StudentBehaviourCategory');
		$StudentBehaviourCategory->formatResult = true;
		
		$data = $StudentBehaviourCategory->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'CensusBehaviour.id', 'CensusBehaviour.male', 'CensusBehaviour.female', 
				'CensusBehaviour.source', 'StudentBehaviourCategory.name', 'StudentBehaviourCategory.id AS student_behaviour_category_id'
			),
			'joins' => array(
				array(
					'table' => 'census_behaviours',
					'alias' => 'CensusBehaviour',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusBehaviour.institution_site_id = ' . $siteId,
						'CensusBehaviour.school_year_id = ' . $yearId,
						'CensusBehaviour.student_behaviour_category_id = StudentBehaviourCategory.id'
					)
				)
			),
			'conditions' => array('StudentBehaviourCategory.visible' => 1),
			'order' => array('StudentBehaviourCategory.order')
		));
		return $data;
	}
	
	public function saveCensusData($data, $institutionSiteId) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);
		
		foreach($data as $obj) {
			$obj['school_year_id'] = $yearId;
			$obj['institution_site_id'] = $institutionSiteId;
			if($obj['id'] == 0) {
				$this->create();
			}
			$save = $this->save(array('CensusBehaviour' => $obj));
		}
	}
}
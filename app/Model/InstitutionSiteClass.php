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

class InstitutionSiteClass extends AppModel {
	public $belongsTo = array('SchoolYear');
	
	public function isNameExists($name, $institutionSiteId, $yearId) {
		$count = $this->find('count', array(
			'conditions' => array(
				'InstitutionSiteClass.name LIKE' => $name,
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId,
				'InstitutionSiteClass.school_year_id' => $yearId
			)
		));
		return $count>0;
	}
	
	public function getClass($classId, $institutionSiteId=0) {
		$conditions = array('InstitutionSiteClass.id' => $classId);
		
		if($institutionSiteId > 0) {
			$conditions['InstitutionSiteClass.institution_site_id'] = $institutionSiteId;
		}
		
		$obj = $this->find('first', array('conditions' => $conditions));
		return $obj;
	}
	
	public function getListOfClasses($yearId, $institutionSiteId) {
		$InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
		$InstitutionSiteClassGradeStudent = ClassRegistry::init('InstitutionSiteClassGradeStudent');
		
		$classes = $this->find('list', array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => array(
				'InstitutionSiteClass.school_year_id' => $yearId,
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteClass.name')
		));
		
		$data = array();
		foreach($classes as $id => $name) {
			$data[$id] = array(
				'name' => $name,
				'grades' => $InstitutionSiteClassGrade->getGradesByClass($id),
				'gender' => $InstitutionSiteClassGradeStudent->getGenderTotalByClass($id)
			);
		}
		return $data;
	}
	
	public function getClassOptions($yearId, $institutionSiteId, $gradeId=false) {
		$options = array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => array(
				'InstitutionSiteClass.school_year_id' => $yearId,
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteClass.name')
		);
		
		if($gradeId!==false) {
			$options['joins'] = array(
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id',
						'InstitutionSiteClassGrade.education_grade_id = ' . $gradeId
					)
				)
			);
			$options['group'] = array('InstitutionSiteClass.id');
		}
		$data = $this->find('list', $options);
		return $data;
	}
}

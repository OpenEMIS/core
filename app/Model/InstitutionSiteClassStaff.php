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

class InstitutionSiteClassStaff extends AppModel {
	public $useTable = 'institution_site_class_staff';
	
	public $belongsTo = array(
		'Staff.Staff'
	);
	
	// used by InstitutionSite.classesEdit/classesView
	public function getStaffs($classId, $mode = 'all') {
		$data = $this->find('all', array(
			'recursive' => 0,
			'fields' => array(
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.last_name'
			),
			'conditions' => array('InstitutionSiteClassStaff.institution_site_class_id' => $classId),
			'order' => array('Staff.first_name')
		));

		if ($mode == 'list') {
			$list = array();
			foreach ($data as $obj) {
				$id = $obj['Staff']['id'];
				$list[$id] = sprintf('%s %s', $obj['Staff']['first_name'], $obj['Staff']['last_name']);
			}
			return $list;
		} else {
			return $data;
		}
	}

	// used by InstitutionSite.staffView/teachersEdit
	public function getClasses($teacherId, $institutionSiteId) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'EducationLevel.name', 'InstitutionSiteClass.name'
			),
			'joins' => array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClass.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteClass.id = InstitutionSiteClassStaff.institution_site_class_id'
					)
				),
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array('InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				)
			),
			'conditions' => array('InstitutionSiteClassStaff.staff_id' => $teacherId),
			'group' => array('EducationLevel.id', 'InstitutionSiteClass.id'),
			'order' => array('EducationLevel.order')
		));
		return $data;
	}

	public function getStaffsByInstitutionSiteId($institutionSiteId) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.last_name'
			),
			'joins' => array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClass.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteClass.id = InstitutionSiteClassStaff.institution_site_class_id'
					)
				),
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('Staff.id = InstitutionSiteClassStaff.staff_id')
				),
			),
			'order' => array('Staff.first_name')
		));
		$list = array();
		foreach ($data as $obj) {
			$id = $obj['Staff']['id'];
			$teacherName = $obj['Staff']['first_name'] . ' ' . $obj['Staff']['last_name'];
			$list[$id] = sprintf('%s %s', $obj['Staff']['first_name'], $obj['Staff']['last_name']);
		}
		return $list;
	}

	public function getStaff($teacherId) {
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array(
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.last_name',
			),
			'joins' => array(
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('Staff.id = InstitutionSiteClassStaff.staff_id')
				)
			),
			'conditions' => array('Staff.id' => $teacherId),
			'order' => array('Staff.first_name')
		));


		return $data;
	}

}

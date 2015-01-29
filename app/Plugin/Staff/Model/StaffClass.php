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

class StaffClass extends AppModel {
	public $useTable = 'institution_site_class_staff';
	
	public $actsAs = array(
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'Staff.Staff',
		'InstitutionSiteClass'
	);
	
	public $hasMany = array(
		'InstitutionSiteClassStudent'
	);
	
	public function index() {
		$this->Navigation->addCrumb('Classes');
		$alias = $this->alias;
		$staffId = $this->Session->read('Staff.id');

		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				"$alias.*", 'AcademicPeriod.name', 'InstitutionSite.name', 'InstitutionSiteSection.*', 'InstitutionSiteClass.*',
				'EducationSubject.name'
			),
			'joins' => array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						"InstitutionSiteClass.id = $alias.institution_site_class_id"
					)
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array(
						"InstitutionSite.id = InstitutionSiteClass.institution_site_id"
					)
				),
				array(
					'table' => 'institution_site_section_classes',
					'alias' => 'InstitutionSiteSectionClass',
					'conditions' => array(
						"InstitutionSiteSectionClass.institution_site_class_id = InstitutionSiteClass.id"
					)
				),
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array(
						"InstitutionSiteSection.id = InstitutionSiteSectionClass.institution_site_section_id"
					)
				),
				array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'conditions' => array(
						"EducationSubject.id = InstitutionSiteClass.education_subject_id"
					)
				),
				array(
					'table' => 'academic_periods',
					'alias' => 'AcademicPeriod',
					'conditions' => array(
						"AcademicPeriod.id = InstitutionSiteClass.academic_period_id",
						"AcademicPeriod.available = 1"
					)
				)
			),
			'conditions' => array(
				"$alias.staff_id" => $staffId,
				"$alias.status = 1"
			),
			'order' => array("AcademicPeriod.order")
		));
		
		foreach($data as $i => $obj) {
			$classId = $obj[$this->alias]['institution_site_class_id'];
			$data[$i][$this->alias]['gender'] = $this->InstitutionSiteClassStudent->getGenderTotalByClass($classId);
		}
		pr($data);
		
		$this->setVar(compact('data'));
	}
}

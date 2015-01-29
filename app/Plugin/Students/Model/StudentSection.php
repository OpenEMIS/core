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

class StudentSection extends AppModel {
	public $useTable = 'institution_site_section_students';
	
	public $actsAs = array(
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'Students.Student',
		'InstitutionSiteSection'
	);
	
	public function index() {
		$this->Navigation->addCrumb('Sections');
		$alias = $this->alias;
		$studentId = $this->Session->read('Student.id');

		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'AcademicPeriod.name', 'InstitutionSite.name', 'InstitutionSiteSection.name', 
			),
			'joins' => array(
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array(
						"InstitutionSiteSection.id = $alias.institution_site_section_id"
					)
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array(
						"InstitutionSite.id = InstitutionSiteSection.institution_site_id"
					)
				),
				array(
					'table' => 'academic_periods',
					'alias' => 'AcademicPeriod',
					'conditions' => array(
						"AcademicPeriod.id = InstitutionSiteSection.academic_period_id",
						"AcademicPeriod.visible = 1"
					)
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						"EducationGrade.id = InstitutionSiteSection.education_grade_id"
					)
				)
			),
			'conditions' => array(
				"$alias.student_id" => $studentId,
				"$alias.status = 1"
			),
			'order' => array("AcademicPeriod.order")
		));
		
		pr($data);
		
		$this->setVar('data', $data);
	}
}

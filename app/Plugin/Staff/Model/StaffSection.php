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

class StaffSection extends AppModel {
	public $useTable = 'institution_site_sections';
	
	public $actsAs = array(
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'Staff.Staff',
		'InstitutionSite',
		'AcademicPeriod',
		'EducationGrade'
	);
	
	public $hasMany = array(
		'InstitutionSiteSectionStudent',
		'InstitutionSiteSectionGrade'
	);
	
	public function index() {
		$this->Navigation->addCrumb('Sections');
		$alias = $this->alias;
		$staffId = $this->Session->read('Staff.id');
		
		$this->contain(array(
			'InstitutionSite' => array(
				'fields' => array('InstitutionSite.name')
			),
			'AcademicPeriod' => array(
				'fields' => array('AcademicPeriod.name')
			),
			'EducationGrade' => array(
				'fields' => array('EducationGrade.name')
			)
		));

		$data = $this->find('all', array(
			'conditions' => array(
				"$alias.staff_id" => $staffId
			),
			'order' => array("AcademicPeriod.order")
		));
		
		foreach($data as $i => $obj) {
			$id = $obj[$this->alias]['id'];
			$data[$i][$this->alias]['gender'] = $this->InstitutionSiteSectionStudent->getGenderTotalBySection($id);
			if(empty($obj[$this->alias]['education_grade_id'])){
				$data[$i]['EducationGrade']['grades'] = $this->InstitutionSiteSectionGrade->getGradesBySection($id);
			}else{
				$data[$i]['EducationGrade']['grades'] = ClassRegistry::init('InstitutionSiteSection')->getSingleGradeBySection($id);
			}
		}
		
		$this->setVar(compact('data'));
	}
}

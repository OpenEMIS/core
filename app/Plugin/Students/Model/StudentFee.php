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

class StudentFee extends StudentsAppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'InstitutionSiteFee',
		'Students.Student'
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$this->Navigation->addCrumb('Fees');
		
		$ConfigItem = ClassRegistry::init('ConfigItem');
	   	$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));
		$this->setVar('currency', $currency);
	}
	
	public function index() {
		$studentId = $this->Session->read('Student.id');
		$alias = $this->alias;
		$this->contain(array(
			'InstitutionSiteFee' => array(
				'SchoolYear' => array('fields' => array('name')),
				'EducationGrade' => array(
					'fields' => array('name'),
					'EducationProgramme' => array('fields' => array('name'))
				)
			)
		));
		
		$data = $this->find('all', array(
			'fields' => array(
				"$alias.student_id",
				"$alias.institution_site_fee_id",
				'InstitutionSiteFee.total',
				'InstitutionSiteFee.school_year_id',
				'InstitutionSiteFee.education_grade_id',
				'SUM(StudentFee.amount) AS paid'
			),
			'conditions' => array("$alias.student_id" => $studentId),
			'group' => array("$alias.student_id", "$alias.institution_site_fee_id")
		));
		
		$this->setVar(compact('data'));
	}
	
	public function view($studentId, $institutionSiteFeeId) {
		$InstitutionSiteStudentFee = $this->InstitutionSiteFee->InstitutionSiteStudentFee;
		$InstitutionSiteStudentFee->controller = $this->controller;
		$InstitutionSiteStudentFee->viewPayments($studentId, $institutionSiteFeeId);
		$this->render = 'view';
		$this->setVar('model', 'InstitutionSiteStudentFee');
		$this->fields = $InstitutionSiteStudentFee->fields;
	}
}

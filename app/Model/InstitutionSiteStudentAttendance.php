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

class InstitutionSiteStudentAttendance extends AppModel {
    public $actsAs = array('DatePicker' => array('first_date_absent', 'last_date_absent'));
    
	public $belongsTo = array(
		'Student',
		'InstitutionSiteClass',
		'StudentAbsenceReason' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'student_absence_reason_id'
		),
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
        'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
	);
	
	public $validate = array(
		'institution_site_class_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Class'
			)
		),
		'student_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Student'
			)
		),
		'student_absence_reason_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Reason'
			)
		),
		'absence_type' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Type'
			)
		)
	);
	
	public function getAbsenceData($institutionSiteId, $classId, $startDate='', $endDate=''){
		if(!empty($classId)){
			$conditions = array(
				'InstitutionSiteStudentAttendance.institution_site_class_id' => $classId
			);
		}else{
			$conditions = array();
		}
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteStudentAttendance.id', 
				'InstitutionSiteStudentAttendance.absence_type', 
				'InstitutionSiteStudentAttendance.first_date_absent', 
				'InstitutionSiteStudentAttendance.last_date_absent', 
				'InstitutionSiteStudentAttendance.full_day_absent', 
				'InstitutionSiteStudentAttendance.start_time_absent', 
				'InstitutionSiteStudentAttendance.end_time_absent', 
				'Student.id',
				'Student.identification_no',
				'Student.first_name',
				'Student.middle_name',
				'Student.last_name',
				'Student.preferred_name'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('InstitutionSiteStudentAttendance.student_id = Student.id')
				),
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteStudentAttendance.institution_site_class_id = InstitutionSiteClass.id',
						'InstitutionSiteClass.institution_site_id' => $institutionSiteId
					)
				)
			),
			'conditions' => $conditions,
			'order' => array('InstitutionSiteStudentAttendance.first_date_absent', 'InstitutionSiteStudentAttendance.last_date_absent')
		));
		
		return $data;
	}
	
	public function getAbsenceById($absenceId){
		$data = $this->find('first', array(
			'fields' => array(
				'InstitutionSiteClass.name', 
				'InstitutionSiteStudentAttendance.id', 
				'InstitutionSiteStudentAttendance.absence_type', 
				'InstitutionSiteStudentAttendance.first_date_absent', 
				'InstitutionSiteStudentAttendance.last_date_absent', 
				'InstitutionSiteStudentAttendance.full_day_absent', 
				'InstitutionSiteStudentAttendance.start_time_absent', 
				'InstitutionSiteStudentAttendance.end_time_absent', 
				'InstitutionSiteStudentAttendance.comment', 
				'InstitutionSiteStudentAttendance.created', 
				'InstitutionSiteStudentAttendance.modified', 
				'InstitutionSiteStudentAttendance.institution_site_class_id', 
				'InstitutionSiteStudentAttendance.student_id',
				'InstitutionSiteStudentAttendance.student_absence_reason_id', 
				'Student.id',
				'Student.identification_no',
				'Student.first_name',
				'Student.middle_name',
				'Student.last_name',
				'Student.preferred_name',
				'StudentAbsenceReason.name',
				'CreatedUser.*', 
				'ModifiedUser.*'
			),
			'conditions' => array(
				'InstitutionSiteStudentAttendance.id' => $absenceId
			)
		));
		
		return $data;
	}
	
//	public function index($controller, $params) {
//		return null;
//	}
	
}

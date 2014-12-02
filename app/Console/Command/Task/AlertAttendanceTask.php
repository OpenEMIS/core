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
App::uses('AppTask', 'Console/Command/Task');
App::uses('CakeEmail', 'Network/Email');

class AlertAttendanceTask extends AppTask {

	public $uses = array(
		'Alerts.Alert', 
		'Alerts.AlertLog',
		'SecurityRole',
		'InstitutionSiteStudentAbsence'
	);
	public $tasks = array('Common');

	public function execute() {
		$alert = $this->Alert->getAlertByName('Student Absent');
		$threshold = $alert['Alert']['threshold'];
		
		$alertRoles = $this->Alert->getAlertWithRolesByName('Student Absent');
		$roleIds = array();
		foreach($alertRoles AS $row){
			$roleIds[] = $row['AlertRole']['security_role_id'];
		}
		
		$studentIds = $this->InstitutionSiteStudentAbsence->getStudentListForAlert($threshold);
		$data = array();
		if(!empty($roleIds) && !empty($studentIds)){
			$data = $this->SecurityRole->find('all', array(
				'recursive' => -1,
				'fields' => array('SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.email', 'Student.identification_no', 'Student.first_name', 'Student.last_name'),
				'joins' => array(
					array(
						'table' => 'security_groups',
						'alias' => 'SecurityGroup',
						'conditions' => array(
							'SecurityRole.security_group_id = SecurityGroup.id'
						)
					),
					array(
						'table' => 'security_group_users',
						'alias' => 'SecurityGroupUser',
						'conditions' => array(
							'SecurityGroup.id = SecurityGroupUser.security_group_id'
						)
					),
					array(
						'table' => 'security_users',
						'alias' => 'SecurityUser',
						'conditions' => array(
							'SecurityGroupUser.security_user_id = SecurityUser.id'
						)
					),
					array(
						'table' => 'security_group_institution_sites',
						'alias' => 'SecurityGroupInstitutionSite',
						'conditions' => array(
							'SecurityGroup.id = SecurityGroupInstitutionSite.security_group_id'
						)
					),
					array(
						'table' => 'institution_site_students',
						'alias' => 'InstitutionSiteStudent',
						'conditions' => array(
							'InstitutionSiteStudent.institution_site_id = SecurityGroupInstitutionSite.institution_site_id'
						)
					),
					array(
						'table' => 'students',
						'alias' => 'Student',
						'conditions' => array(
							'InstitutionSiteStudent.student_id = Student.id',
							'Student.id' => $studentIds
						)
					)
				),
				'conditions' => array('SecurityRole.id' => $roleIds),
				'group' => array('SecurityUser.id', 'Student.id')
			));
		}
		pr($data);
		return $data;
	}

}

?>
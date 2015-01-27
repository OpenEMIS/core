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

App::uses('AlertTask', 'Console/Command/Task');

class AlertAttendanceTask extends AlertTask {

	public $uses = array(
		'Alerts.Alert', 
		'Alerts.AlertLog',
		'SecurityRole',
		'InstitutionSiteStudentAbsence',
		'SchoolYear',
		'SecurityUser',
		'Students.Student'
	);
	
	public function execute($args) {
		$studentId = $args[0];
		$schoolYearId = $args[1];
		$institutionSiteId = $args[2];
		
		$alert = $this->getObject();
		$threshold = $alert['Alert']['threshold'];
		$roleIds = array();
		foreach($alert['SecurityRole'] AS $row){
			$roleIds[] = $row['id'];
		}
		$this->log('roleIds:', 'alert_processes');
		$this->log($roleIds, 'alert_processes');
		
		$triggered = $this->checkStudentAbsenceAlert($studentId, $schoolYearId, $institutionSiteId, $threshold);
		
		$this->log('triggered:', 'alert_processes');
		$this->log($triggered, 'alert_processes');
		
		$alertLogIds = array();
		if($triggered){
			// fetch list of recipients based on institution_site_id, and alert_roles
			$data = $this->SecurityUser->find('all', array(
				'fields' => array('SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.email', 'InstitutionSite.code', 'InstitutionSite.name'),
				'joins' => array(
					array(
						'table' => 'security_group_users',
						'alias' => 'SecurityGroupUser',
						'conditions' => array(
							'SecurityGroupUser.security_user_id = SecurityUser.id',
							'SecurityGroupUser.security_role_id' => $roleIds
						)
					),
					array(
						'table' => 'security_group_institution_sites',
						'alias' => 'SecurityGroupInstitutionSite',
						'conditions' => array(
							'SecurityGroupInstitutionSite.security_group_id = SecurityGroupUser.security_group_id',
							'SecurityGroupInstitutionSite.institution_site_id' => $institutionSiteId
						)
					),
					array(
						'table' => 'institution_sites',
						'alias' => 'InstitutionSite',
						'conditions' => array(
							'InstitutionSite.id = SecurityGroupInstitutionSite.institution_site_id'
						)
					)
				),
				//'conditions' => array('SecurityGroupUser.security_role_id' => $roleIds),
				'group' => array('SecurityUser.id')
			));
			
			$this->log('data:', 'alert_processes');
			$this->log($data, 'alert_processes');
			
			$studentData = $this->Student->findById($studentId, array('Student.identification_no', 'Student.first_name', 'Student.middle_name', 'Student.third_name', 'Student.last_name'));
			
			$this->log('studentData:', 'alert_processes');
			$this->log($studentData, 'alert_processes');
			
			if($alert && !empty($data) && !empty($studentData)){
				$subject = $alert['Alert']['subject'];
				$message = $alert['Alert']['message'];
				$student = $studentData['Student'];
				
				foreach($data AS $row){
					$securityUser = $row['SecurityUser'];
					$InstitutionSite = $row['InstitutionSite'];
					$userEmail = $securityUser['email'];
					
					$this->AlertLog->create();
					
					$message .= '<p>' . __('Student') . ': ';
					$message .= ModelHelper::getName($student) . ' (' . $student['identification_no'] . ')';
					$message .= '<br/>' . __('Institution') . ': ';
					$message .= $InstitutionSite['name'] . ' (' . $InstitutionSite['code'] . ')';
					$message .= '</p>';

					$newLog = array(
						'id' => NULL,
						'method' => 'Email',
						'destination' => $userEmail,
						'type' => 'Alert',
						'status' => 0,
						'subject' => $subject,
						'message' => $message
					);
					
					if(!empty($userEmail)){
						$this->AlertLog->save($newLog);
						$newId = $this->AlertLog->getLastInsertID();
						$alertLogIds[] = $newId;
					}
				}
			}
		}
		
		return $alertLogIds;
	}
	
	public function checkStudentAbsenceAlert($studentId, $schoolYearId, $institutionSiteId, $threshold){
		$list = $this->InstitutionSiteStudentAbsence->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteStudentAbsence.*'),
			'joins' => array(
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array(
						'InstitutionSiteSection.id = InstitutionSiteStudentAbsence.institution_site_section_id',
						'InstitutionSiteSection.school_year_id' => $schoolYearId, 
						'InstitutionSiteSection.institution_site_id' => $institutionSiteId
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteStudentAbsence.student_id' => $studentId
			)
		));
		
		$totalDaysAbsence = 0;
		foreach($list AS $row){
			$absence = $row['InstitutionSiteStudentAbsence'];
			
			if($absence['full_day_absent'] == 'Yes'){
				$days = 1;
				if(!empty($absence['first_date_absent'])){
					if(!empty($absence['last_date_absent'])){
						$lastDay = strtotime($absence['last_date_absent']);
						$firstDay = strtotime($absence['first_date_absent']);
						
						if($lastDay > $firstDay){
							$days = floor(($lastDay - $firstDay) / (60*60*24));
							$days += 1;
						}else if($lastDay == $firstDay){
							$days = 1;
						}else{
							$days = 0;
						}
					}
				}
				
				$totalDaysAbsence += $days;
			}else{
				$totalDaysAbsence += 0.5;
			}
		}
		$this->log('days of absence: ' . $totalDaysAbsence, 'alert_processes');
		$this->log('threshold: ' . $threshold, 'alert_processes');

		if($totalDaysAbsence >= $threshold){
			return true;
		}else{
			return false;
		}
	}

	// backup, used for the 'main' method in the AlertShell
	/*public function execute() {
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
		return $data;
	}*/

}

?>

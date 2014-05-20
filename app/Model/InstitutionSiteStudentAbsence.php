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

class InstitutionSiteStudentAbsence extends AppModel {
    public $actsAs = array('DatePicker' => array('first_date_absent', 'last_date_absent'), 'ControllerAction');
    
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
				'InstitutionSiteStudentAbsence.institution_site_class_id' => $classId
			);
		}else{
			$conditions = array();
		}
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteStudentAbsence.id', 
				'InstitutionSiteStudentAbsence.absence_type', 
				'InstitutionSiteStudentAbsence.first_date_absent', 
				'InstitutionSiteStudentAbsence.last_date_absent', 
				'InstitutionSiteStudentAbsence.full_day_absent', 
				'InstitutionSiteStudentAbsence.start_time_absent', 
				'InstitutionSiteStudentAbsence.end_time_absent', 
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
					'conditions' => array('InstitutionSiteStudentAbsence.student_id = Student.id')
				),
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteStudentAbsence.institution_site_class_id = InstitutionSiteClass.id',
						'InstitutionSiteClass.institution_site_id' => $institutionSiteId
					)
				)
			),
			'conditions' => $conditions,
			'order' => array('InstitutionSiteStudentAbsence.first_date_absent', 'InstitutionSiteStudentAbsence.last_date_absent')
		));
		
		return $data;
	}
	
	public function getAbsenceById($absenceId){
		$data = $this->find('first', array(
			'fields' => array(
				'InstitutionSiteClass.name', 
				'InstitutionSiteStudentAbsence.id', 
				'InstitutionSiteStudentAbsence.absence_type', 
				'InstitutionSiteStudentAbsence.first_date_absent', 
				'InstitutionSiteStudentAbsence.last_date_absent', 
				'InstitutionSiteStudentAbsence.full_day_absent', 
				'InstitutionSiteStudentAbsence.start_time_absent', 
				'InstitutionSiteStudentAbsence.end_time_absent', 
				'InstitutionSiteStudentAbsence.comment', 
				'InstitutionSiteStudentAbsence.created', 
				'InstitutionSiteStudentAbsence.modified', 
				'InstitutionSiteStudentAbsence.institution_site_class_id', 
				'InstitutionSiteStudentAbsence.student_id',
				'InstitutionSiteStudentAbsence.student_absence_reason_id', 
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
				'InstitutionSiteStudentAbsence.id' => $absenceId
			)
		));
		
		return $data;
	}
	
	public function attendanceStudent($controller, $params){
		$controller->Navigation->addCrumb('Attendance - Students');
	}
	
	public function attendanceStudentAbsence($controller, $params){
		$controller->Navigation->addCrumb('Absence - Students');
		
		$yearList = $controller->SchoolYear->getYearList();
		$classOptions = $controller->InstitutionSiteClass->getClassListByInstitution($controller->institutionSiteId);
		
		if (isset($controller->params['pass'][0])) {
            $classId = $controller->params['pass'][0];
		}else if(!empty($classOptions[0]['id'])){
			$classId = $classOptions[0]['id'];
		}else{
			$classId = 0;
		}
		
		$startDate = '2011-05-16';
		$endDate = '2016-05-16';
		
		$data = $this->getAbsenceData($controller->institutionSiteId, $classId, $startDate, $endDate);
		//pr($data);
		
		$controller->set(compact('yearList', 'classOptions', 'data'));
	}
	
	public function attendanceStudentAbsenceAdd($controller, $params){
		if($controller->request->is('get')){
			$controller->Navigation->addCrumb('Absence - Students', array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
			$controller->Navigation->addCrumb('Add');
		}else{
			//$this->create();
			
			$absenceData = $controller->request->data['InstitutionSiteStudentAbsence'];
			$absenceData['student_id'] = $absenceData['hidden_student_id'];
			unset($controller->request->data['InstitutionSiteStudentAbsence']['hidden_student_id']);
			
			$this->set($absenceData);
			if ($this->validates()) {
				if($controller->InstitutionSiteClassGradeStudent->isStudentInClass($controller->institutionSiteId, $absenceData['institution_site_class_id'], $absenceData['student_id'])){
					if($this->save($absenceData)){
						$newId = $this->getInsertID();
						$postFileData = $controller->request->data[$this->alias]['files'];
						$controller->FileUploader->additionData = array('institution_site_student_attendance_id' => $newId);
						$controller->FileUploader->uploadFile(NULL, $postFileData);
						
						
						if($controller->FileUploader->success){
							$controller->Message->alert('general.add.success');
							return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
						}
						
					}
				}else{
					$controller->Message->alert('institutionSiteAttendance.student.add.failed');
				}
			}
		}
		
		$classOptions = $controller->InstitutionSiteClass->getClassListByInstitution($controller->institutionSiteId);
		$fullDayAbsentOptions = array('Yes' => __('Yes'), 'No' => __('No'));
		$absenceReasonOptions =  $this->StudentAbsenceReason->getList();;
		$absenceTypeOptions = array('Excused' => __('Excused'), 'Unexcused' => __('Unexcused'));
		
		$controller->set(compact('classOptions', 'fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions'));
	}
	
	public function attendanceStudentSearchStudent($controller, $params){
		//$controller->autoRender = false;
		$this->render = false;
        $search = $controller->params->query['term'];
		$classId = intval($controller->params->query['classId']);
		
		if(empty($classId)){
			$result = $controller->InstitutionSiteStudent->getAutoCompleteList($search, $controller->institutionSiteId);
			
		}else{
			$result = $controller->InstitutionSiteClassGradeStudent->getAutoCompleteList($search, $classId);
		}
        
		//$result = array();
        return json_encode($result);
	}
	
	public function attendanceStudentAbsenceEdit($controller, $params){
		if (isset($controller->params['pass'][0])) {
            $absenceId = $controller->params['pass'][0];
            $obj = $this->getAbsenceById($absenceId);

            if (!$obj) {
               return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
            }
        }else {
            return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
        }
		
		if($controller->request->is('get')){
			$controller->Navigation->addCrumb('Absence - Students', array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
			$controller->Navigation->addCrumb('Absence Details');
		}else{
			$obj = $controller->request->data;
			$absenceData = $controller->request->data['InstitutionSiteStudentAbsence'];
			$absenceData['student_id'] = $absenceData['hidden_student_id'];
			unset($controller->request->data['InstitutionSiteStudentAbsence']['hidden_student_id']);
			
			if ($this->save($absenceData, array('validate' => 'only'))) {
				if($controller->InstitutionSiteClassGradeStudent->isStudentInClass($controller->institutionSiteId, $absenceData['institution_site_class_id'], $absenceData['student_id'])){
					if($this->save($absenceData)){
						$controller->Message->alert('general.edit.success');
						return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsenceView', $absenceId));
					}
				}else{
					$controller->Message->alert('institutionSiteAttendance.student.add.failed');
				}
			}
		}
		
		$classOptions = $controller->InstitutionSiteClass->getClassListByInstitution($controller->institutionSiteId);
		$fullDayAbsentOptions = array('Yes' => __('Yes'), 'No' => __('No'));
		$absenceReasonOptions =  $this->StudentAbsenceReason->getList();;
		$absenceTypeOptions = array('Excused' => __('Excused'), 'Unexcused' => __('Unexcused'));
		
		$controller->set(compact('classOptions', 'fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions', 'absenceId', 'obj'));
	}
	
	public function attendanceStudentAbsenceView($controller, $params){
		$controller->Navigation->addCrumb('Absence - Students', array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
		$controller->Navigation->addCrumb('Absence Details');
		
		if (isset($controller->params['pass'][0])) {
            $absenceId = $controller->params['pass'][0];
            $obj = $this->getAbsenceById($absenceId);

            if ($obj) {
                $controller->Session->write('InstitutionStudentAbsenceId', $absenceId);
            } else {
                return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
            }
        }else if ($controller->Session->check('InstitutionStudentAbsenceId')){
            $absenceId = $controller->Session->read('InstitutionStudentAbsenceId');
            $obj = $this->getAbsenceById($absenceId);
        } else {
            return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
        }
		//pr($obj);
		
		$controller->set(compact('obj', 'absenceId'));
	}
	
	public function attendanceStudentAbsenceDelete($controller, $params){
		if ($controller->Session->check('InstitutionStudentAbsenceId')) {
			$absenceId = $controller->Session->read('InstitutionStudentAbsenceId');
			$obj = $this->getAbsenceById($absenceId);
			$studentName = $obj['Student']['first_name'] . ' ' . $obj['Student']['last_name'];

			$this->deleteAll(array('InstitutionSiteStudentAbsence.id' => $absenceId));
			$controller->Utility->alert($studentName . __(' have been deleted successfully.'));
			$controller->redirect(array('action' => 'attendanceStudentAbsence'));
		} else {
			$controller->redirect(array('action' => 'attendanceStudentAbsence'));
		}
	}
	
	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
		$controller->FileUploader->fileVar = 'files';
		$controller->FileUploader->fileModel = 'InstitutionSiteStudentAbsenceAttachment';
		$controller->FileUploader->allowEmptyUpload = true;
		$controller->FileUploader->additionalFileType();
    }
	
	public function attendanceStudentAjaxAddField($controller, $params) {
		$this->render =false;
		
		$fileId = $controller->request->data['size'];
		$multiple = true;
		$controller->set(compact('fileId', 'multiple'));
		$controller->render('/Elements/templates/file_upload_field');
	}
	
}

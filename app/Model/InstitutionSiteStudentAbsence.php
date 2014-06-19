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
    //public $hasMany = array('InstitutionSiteStudentAbsenceAttachment');
	
	public $actsAs = array(
		'DatePicker' => array(
			'first_date_absent', 'last_date_absent'
		), 
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
    
	public $belongsTo = array(
		'Students.Student',
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
		'first_date_absent' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select First Date Absent'
			),
			'ruleNotLater' => array(
				'rule' => array('compareDate', 'last_date_absent'),
				'message' => 'First Date Absent cannot be later than Last Date Absent'
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
	
	public function compareDate($field = array(), $compareField = null) {
		$startDate = new DateTime(current($field));
		$endDate = new DateTime($this->data[$this->name][$compareField]);
		return $endDate >= $startDate;
	}
	
	public function getAbsenceData($institutionSiteId, $schoolYearId, $classId, $startDate='', $endDate=''){
		$conditions = array();
		
		// if $classId is not present, then $institutionSiteId and $schoolYearId are necessary for data filter
		if(!empty($classId)){
			$conditions[] = 'InstitutionSiteStudentAbsence.institution_site_class_id = ' . $classId;
		}
		
		if(!empty($startDate) && !empty($endDate)){
			$conditions['OR'] = array(
					array(
						'InstitutionSiteStudentAbsence.first_date_absent >= "' . $startDate . '"',
						'InstitutionSiteStudentAbsence.first_date_absent <= "' . $endDate . '"'
					),
					array(
						'InstitutionSiteStudentAbsence.last_date_absent >= "' . $startDate . '"',
						'InstitutionSiteStudentAbsence.last_date_absent <= "' . $endDate . '"'
					)
			);
		}
		
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$schoolYear = $SchoolYear->getSchoolYearById($schoolYearId);
		$conditions[] = 'YEAR(InstitutionSiteStudentAbsence.first_date_absent) = "' . $schoolYear . '"';
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT InstitutionSiteStudentAbsence.id', 
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
						'InstitutionSiteClass.institution_site_id' => $institutionSiteId,
						'InstitutionSiteClass.school_year_id' => $schoolYearId
					)
				)
			),
			'conditions' => $conditions,
			'order' => array('InstitutionSiteStudentAbsence.first_date_absent', 'InstitutionSiteStudentAbsence.last_date_absent')
		));
		
		return $data;
	}
	
	public function getStudentAbsenceDataByMonth($studentId, $schoolYearId, $monthId){
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$schoolYear = $SchoolYear->getSchoolYearById($schoolYearId);
		
		$conditions = array(
			'Student.id = ' . $studentId,
		);
		
		$conditions['OR'] = array(
			array(
				'MONTH(InstitutionSiteStudentAbsence.first_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStudentAbsence.first_date_absent) = "' . $schoolYear . '"'
			),
			array(
				'MONTH(InstitutionSiteStudentAbsence.last_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStudentAbsence.last_date_absent) = "' . $schoolYear . '"'
			)
		);
		
		$data = $this->find('all', array(
			'fields' => array(
				'DISTINCT InstitutionSiteStudentAbsence.id', 
				'InstitutionSiteStudentAbsence.absence_type', 
				'InstitutionSiteStudentAbsence.first_date_absent', 
				'InstitutionSiteStudentAbsence.last_date_absent', 
				'InstitutionSiteStudentAbsence.full_day_absent', 
				'InstitutionSiteStudentAbsence.start_time_absent', 
				'InstitutionSiteStudentAbsence.end_time_absent',
				'StudentAbsenceReason.name'
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

		$yearList = ClassRegistry::init('SchoolYear')->getYearList();
		//pr($yearList);
		$currentYearId = ClassRegistry::init('SchoolYear')->getSchoolYearId(date('Y'));
		if (isset($controller->params['pass'][0])) {
			$yearId = $controller->params['pass'][0];
			if (!array_key_exists($yearId, $yearList)) {
                $yearId = $currentYearId;
            }
		}else{
			$yearId = $currentYearId;
		}
		
		$classOptions = $this->InstitutionSiteClass->getClassListByInstitutionSchoolYear($controller->Session->read('InstitutionSite.id'), $yearId);
		//pr($classOptions);
		if (isset($controller->params['pass'][1])) {
			$classId = $controller->params['pass'][1];
			if (!array_key_exists($classId, $classOptions)) {
                $classId = key($classOptions);
            }
		}else{
			$classId = key($classOptions);
		}
		
		$weekList = $controller->getWeekListByYearId($yearId);
		//pr($weekList);
		$currentWeekId = $controller->getCurrentWeekId($yearId);
		if (isset($controller->params['pass'][2])) {
			$weekId = $controller->params['pass'][2];
			if (!array_key_exists($weekId, $weekList)) {
                $weekId = $currentWeekId;
            }
		}else{
			$weekId = $currentWeekId;
		}
		
		$startEndDates = $controller->getStartEndDateByYearWeek($yearId, $weekId);
		$startDate = $startEndDates['start_date'];
		$endDate = $startEndDates['end_date'];
		
		$header = $controller->generateAttendanceHeader($startDate, $endDate);
		$weekDayIndex = $controller->generateAttendanceWeekDayIndex($startDate, $endDate);
		
		$absenceData = $this->getAbsenceData($controller->Session->read('InstitutionSite.id'), $yearId, $classId, $startDate, $endDate);
		$absenceCheckList = array();
		foreach($absenceData AS $absenceUnit){
			$absenceStudent = $absenceUnit['Student'];
			$studentId = $absenceStudent['id'];
			$absenceRecord = $absenceUnit['InstitutionSiteStudentAbsence'];
			$indexAbsenceDate = date('Ymd', strtotime($absenceRecord['first_date_absent']));
			
			$absenceCheckList[$studentId][$indexAbsenceDate] = $absenceUnit;
			
			if(!empty($absenceRecord['last_date_absent']) && $absenceRecord['last_date_absent'] > $absenceRecord['first_date_absent']){
				$tempStartDate = date("Y-m-d", strtotime($absenceRecord['first_date_absent']));
				$formatedLastDate = date("Y-m-d", strtotime($absenceRecord['last_date_absent']));
				while($tempStartDate <= $formatedLastDate){
					$stampTempDate = strtotime($tempStartDate);
					$tempIndex = date('Ymd', $stampTempDate);
					
					$absenceCheckList[$studentId][$tempIndex] = $absenceUnit;
					
					$stampTempDateNew = strtotime('+1 day', $stampTempDate);
					$tempStartDate = date("Y-m-d", $stampTempDateNew);
				}
			}
		}
		
		$InstitutionSiteClassStudentModel = ClassRegistry::init('InstitutionSiteClassStudent');
		
		$studentList = $InstitutionSiteClassStudentModel->getStudentsByClass($classId);
		if(empty($studentList)){
			$controller->Message->alert('institutionSiteAttendance.no_student');
		}
		
		$controller->set(compact('yearList', 'yearId', 'classOptions', 'classId', 'weekList', 'weekId', 'header', 'weekDayIndex', 'studentList', 'absenceCheckList'));
	}
	
	public function attendanceStudentAbsence($controller, $params){
		$controller->Navigation->addCrumb('Absence - Students');
		
		$yearList = ClassRegistry::init('SchoolYear')->getYearList();
		//pr($yearList);
		$currentYearId = ClassRegistry::init('SchoolYear')->getSchoolYearId(date('Y'));
		if (isset($controller->params['pass'][0])) {
			$yearId = $controller->params['pass'][0];
			if (!array_key_exists($yearId, $yearList)) {
                $yearId = $currentYearId;
            }
		}else{
			$yearId = $currentYearId;
		}
		//pr($yearId);
		
		$classOptions = $this->InstitutionSiteClass->getClassListByInstitutionSchoolYear($controller->Session->read('InstitutionSite.id'), $yearId);
		//pr($classOptions);
		if (isset($controller->params['pass'][1])) {
			$classId = $controller->params['pass'][1];
			if (!array_key_exists($classId, $classOptions)) {
                $classId = key($classOptions);
            }
		}else{
			$classId = key($classOptions);
		}
		//pr($classId);
		
		$weekList = $controller->getWeekListByYearId($yearId);
		//pr($weekList);
		$currentWeekId = $controller->getCurrentWeekId($yearId);
		if (isset($controller->params['pass'][2])) {
			$weekId = $controller->params['pass'][2];
			if (!array_key_exists($weekId, $weekList)) {
                $weekId = $currentWeekId;
            }
		}else{
			$weekId = $currentWeekId;
		}
		//pr($weekId);
		
		$startEndDates = $controller->getStartEndDateByYearWeek($yearId, $weekId);
		$startDate = $startEndDates['start_date'];
		$endDate = $startEndDates['end_date'];
		
		$data = $this->getAbsenceData($controller->Session->read('InstitutionSite.id'), $yearId, $classId, $startDate, $endDate);
		if(empty($data)){
			$controller->Message->alert('institutionSiteAttendance.no_data');
		}
		//pr($data);
		
		$controller->set(compact('yearList', 'yearId', 'classOptions', 'classId', 'weekList', 'weekId', 'data'));
	}
	
	public function attendanceStudentAbsenceAdd($controller, $params){
		$InstitutionSiteClassStudentModel = ClassRegistry::init('InstitutionSiteClassStudent');
		
		if($controller->request->is('get')){
			$controller->Navigation->addCrumb('Absence - Students', array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
			$controller->Navigation->addCrumb('Add');
			
			$settingStartTime = $controller->ConfigItem->getValue('start_time');
			$obj = array(
				'InstitutionSiteStudentAbsence' => array(
					'start_time_absent' => $settingStartTime
				)
			);
			$controller->request->data = $obj;
		}else{
			//$this->create();
			
			$absenceData = $controller->request->data['InstitutionSiteStudentAbsence'];
			$absenceData['student_id'] = $absenceData['hidden_student_id'];
			unset($absenceData['hidden_student_id']);
			
			$firstDateAbsent = $absenceData['first_date_absent'];
			$classIdInput = $absenceData['institution_site_class_id'];
			$firstDateAbsentData = new DateTime($firstDateAbsent);
			$firstDateYear = $firstDateAbsentData->format('Y');
			$firstDateYearId = ClassRegistry::init('SchoolYear')->getSchoolYearId($firstDateYear);
			$classExists= $this->InstitutionSiteClass->getClassByIdSchoolYear($classIdInput, $firstDateYearId);
			
			if($absenceData['full_day_absent'] == 'Yes'){
				$absenceData['start_time_absent'] = '';
				$absenceData['end_time_absent'] = '';
			}else{
				$absenceData['last_date_absent'] = null;
			}

			$this->set($absenceData);
			if ($this->validates()) {
				if($InstitutionSiteClassStudentModel->isStudentInClass($controller->Session->read('InstitutionSite.id'), $absenceData['institution_site_class_id'], $absenceData['student_id'])){
					if($classExists){
						if($this->save($absenceData)){
							$newId = $this->getInsertID();
							//pr($newId);
							$postFileData = $controller->request->data[$this->alias]['files'];
							$controller->FileUploader->additionData = array('institution_site_student_absence_id' => $newId);
							$controller->FileUploader->uploadFile(NULL, $postFileData);

							if($controller->FileUploader->success){
								$controller->Message->alert('general.add.success');
								return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsence'));
							}
						}
					}else{
						$controller->Message->alert('institutionSiteAttendance.student.failed.class_first_date_not_match');
					}
					
				}else{
					$controller->Message->alert('institutionSiteAttendance.student.failed.class_student_not_match');
				}
			}
		}
		
		$classOptions = $this->InstitutionSiteClass->getClassListByInstitution($controller->Session->read('InstitutionSite.id'));
		$fullDayAbsentOptions = array('Yes' => __('Yes'), 'No' => __('No'));
		$absenceReasonOptions =  $this->StudentAbsenceReason->getList();;
		$absenceTypeOptions = array('Excused' => __('Excused'), 'Unexcused' => __('Unexcused'));
		
		if (isset($controller->params['pass'][0])) {
			$classId = $controller->params['pass'][0];
			if (!array_key_exists($classId, $classOptions)) {
                $classId = key($classOptions);
            }
		}else{
			$classId = key($classOptions);
		}
		
		$controller->set(compact('classOptions', 'fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions', 'classId'));
	}
	
	public function attendanceStudentSearchStudent($controller, $params){
		//$controller->autoRender = false;
		$this->render = false;
        $search = $controller->params->query['term'];
		$classId = intval($controller->params->query['classId']);
		
		$InstitutionSiteClassStudentModel = ClassRegistry::init('InstitutionSiteClassStudent');
		
		if(empty($classId)){
			$result = $controller->InstitutionSiteStudent->getAutoCompleteList($search, $controller->Session->read('InstitutionSite.id'));
			
		}else{
			$result = $InstitutionSiteClassStudentModel->getAutoCompleteList($search, $classId);
		}
        
		//$result = array();
        return json_encode($result);
	}
	
	public function attendanceStudentAbsenceEdit($controller, $params){
		$InstitutionSiteClassStudentModel = ClassRegistry::init('InstitutionSiteClassStudent');
		
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
			
			$controller->request->data = $obj;
		}else{
			$obj = $controller->request->data;
			$absenceData = $controller->request->data['InstitutionSiteStudentAbsence'];
			$absenceData['student_id'] = $absenceData['hidden_student_id'];
			unset($absenceData['hidden_student_id']);
			
			if($absenceData['full_day_absent'] == 'Yes'){
				$absenceData['start_time_absent'] = '';
				$absenceData['end_time_absent'] = '';
			}else{
				$absenceData['last_date_absent'] = null;
			}
			
			$firstDateAbsent = $absenceData['first_date_absent'];
			$classIdInput = $absenceData['institution_site_class_id'];
			$firstDateAbsentData = new DateTime($firstDateAbsent);
			$firstDateYear = $firstDateAbsentData->format('Y');
			$firstDateYearId = ClassRegistry::init('SchoolYear')->getSchoolYearId($firstDateYear);
			$classExists= $this->InstitutionSiteClass->getClassByIdSchoolYear($classIdInput, $firstDateYearId);
			
			if ($this->save($absenceData, array('validate' => 'only'))) {
				if($InstitutionSiteClassStudentModel->isStudentInClass($controller->Session->read('InstitutionSite.id'), $absenceData['institution_site_class_id'], $absenceData['student_id'])){
					if($classExists){
						if($this->save($absenceData)){
							$postFileData = $controller->request->data[$this->alias]['files'];
							$controller->FileUploader->additionData = array('institution_site_student_absence_id' => $absenceId);
							$controller->FileUploader->uploadFile(NULL, $postFileData);

							if($controller->FileUploader->success){
								$controller->Message->alert('general.edit.success');
								return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStudentAbsenceView', $absenceId));
							}
						}
					}else{
						$controller->Message->alert('institutionSiteAttendance.student.failed.class_first_date_not_match');
					}
					
				}else{
					$controller->Message->alert('institutionSiteAttendance.student.failed.class_student_not_match');
				}
			}
			
			if($absenceData['full_day_absent'] !== 'Yes'){
				$obj['InstitutionSiteStudentAbsence']['last_date_absent'] = '';
			}
			
		}
		
		$classOptions = $this->InstitutionSiteClass->getClassListByInstitution($controller->Session->read('InstitutionSite.id'));
		$fullDayAbsentOptions = array('Yes' => __('Yes'), 'No' => __('No'));
		$absenceReasonOptions =  $this->StudentAbsenceReason->getList();;
		$absenceTypeOptions = array('Excused' => __('Excused'), 'Unexcused' => __('Unexcused'));
		
		$attachments = $controller->FileUploader->getList(array('conditions' => array('InstitutionSiteStudentAbsenceAttachment.institution_site_student_absence_id' => $absenceId)));
		
		$controller->set(compact('classOptions', 'fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions', 'absenceId', 'obj', 'attachments'));
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
		$attachments = $controller->FileUploader->getList(array('conditions' => array('InstitutionSiteStudentAbsenceAttachment.institution_site_student_absence_id' => $absenceId)));
		//pr($attachments);
		$controller->set(compact('obj', 'absenceId', 'attachments'));
	}
	
	public function attendanceStudentAbsenceDelete($controller, $params){
		if ($controller->Session->check('InstitutionStudentAbsenceId')) {
			$absenceId = $controller->Session->read('InstitutionStudentAbsenceId');
			$obj = $this->getAbsenceById($absenceId);
			$studentName = $obj['Student']['first_name'] . ' ' . $obj['Student']['last_name'];

			if($this->delete($absenceId)){
				$InstitutionSiteStudentAbsenceAttachment = ClassRegistry::init('InstitutionSiteStudentAbsenceAttachment');
				$InstitutionSiteStudentAbsenceAttachment->deleteAll(array('InstitutionSiteStudentAbsenceAttachment.institution_site_student_absence_id' => $absenceId)); 
				
				$controller->Message->alert('general.delete.success');
				$controller->redirect(array('action' => 'attendanceStudentAbsence'));
			}
			
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
	
	public function attendanceStudentAttachmentsDownload($controller, $params) {
		$id = $params['pass'][0];
		$this->render = false;
		$controller->FileUploader->downloadFile($id);
    }
	
	public function attendanceStudentAttachmentDelete($controller, $params) {
        $this->render = false;
        if (!$controller->request->is('get')) {
            $result = array('alertOpt' => array());
            $controller->Utility->setAjaxResult('alert', $result);
            $id = $params->data['id'];

			$studentAbsenceAttachment = ClassRegistry::init('InstitutionSiteStudentAbsenceAttachment');
            if ($studentAbsenceAttachment->delete($id)) {
				$msgData  = $controller->Message->get('FileUplaod.success.delete');
                $result['alertOpt']['text'] = $msgData['msg'];// __('File is deleted successfully.');
            } else {
				$msgData  = $controller->Message->get('FileUplaod.error.delete');
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = $msgData;//__('Error occurred while deleting file.');
            }
			
            return json_encode($result);
        }
    }
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		$header = array(
			__('School Year'),
			
			__('Class'),
			
			__('First Date Absent'),
			__('Last Date Absent'),
			__('Full Day Absent'),
			__('Start Time Absent'),
			__('End Time Absent'),
			
			__('Student OpenEMIS ID'),
			__('First Name'),
			__('Middle Name'),
			__('Last Name'),
			__('Preferred Name'),
			
			__('Absent Type'),
			__('Absent Reason'),
			__('Comment')
		);

		return $header;
	}
	
	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$this->unbindModel(array('belongsTo' => array('InstitutionSiteClass', 'ModifiedUser', 'CreatedUser')));
			
			$options = array();
			//$options['recursive'] = -1;
			$options['fields'] = array(
				'SchoolYear.name', 
				
				'InstitutionSiteClass.name', 
				
				'InstitutionSiteStudentAbsence.first_date_absent', 
				'InstitutionSiteStudentAbsence.last_date_absent', 
				'InstitutionSiteStudentAbsence.full_day_absent', 
				'InstitutionSiteStudentAbsence.start_time_absent', 
				'InstitutionSiteStudentAbsence.end_time_absent', 
				
				'Student.identification_no',
				'Student.first_name',
				'Student.middle_name',
				'Student.last_name',
				'Student.preferred_name',
				
				'InstitutionSiteStudentAbsence.absence_type', 
				'StudentAbsenceReason.name',
				'InstitutionSiteStudentAbsence.comment'
			);
			$options['order'] = array('SchoolYear.name', 'InstitutionSiteStudentAbsence.first_date_absent', 'Student.first_name', 'Student.middle_name', 'Student.last_name');
			//$options['conditions'] = array('InstitutionSiteClass.institution_site_id' => $institutionSiteId);
			
			$options['joins'] = array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteStudentAbsence.institution_site_class_id = InstitutionSiteClass.id',
						'InstitutionSiteClass.institution_site_id = ' . $institutionSiteId
					)
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'type' => 'LEFT',
					'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
				)
			);
			
			

			$data = $this->find('all', $options);
			
			$newData = array();
			foreach($data AS $row){
				$tempRow = array();
				
				$schoolYear = $row['SchoolYear'];
				$class = $row['InstitutionSiteClass'];
				$absence = $row['InstitutionSiteStudentAbsence'];
				$student = $row['Student'];
				$reason = $row['StudentAbsenceReason'];
				
				$tempRow[] = $schoolYear['name'];
				$tempRow[] = $class['name'];
				
				$tempRow[] = $this->formatDateByConfig($absence['first_date_absent']);
				$tempRow[] = $this->formatDateByConfig($absence['last_date_absent']);
				$tempRow[] = $absence['full_day_absent'];
				$tempRow[] = $absence['start_time_absent'];
				$tempRow[] = $absence['end_time_absent'];
				
				$tempRow[] = $student['identification_no'];
				$tempRow[] = $student['first_name'];
				$tempRow[] = $student['middle_name'];
				$tempRow[] = $student['last_name'];
				$tempRow[] = $student['preferred_name'];
				
				$tempRow[] = $absence['absence_type'];
				$tempRow[] = $reason['name'];
				$tempRow[] = $absence['comment'];
				
				$newData[] = $tempRow;
			}

			return $newData;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return 'Report_Student_Attendance';
	}
	
}

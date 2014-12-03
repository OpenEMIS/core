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

class InstitutionSiteStaffAbsence extends AppModel {
    public $actsAs = array(
		'DatePicker' => array(
			'first_date_absent', 'last_date_absent'
		), 
		'ControllerAction2',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	//public $hasMany = array('InstitutionSiteStaffAbsenceAttachment');
    
	public $belongsTo = array(
		'Staff.Staff',
		'InstitutionSite',
		'StaffAbsenceReason' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_absence_reason_id'
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
		'staff_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Staff'
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
		'staff_absence_reason_id' => array(
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
	
	public function beforeAction() {
		parent::beforeAction();
		$this->controller->FileUploader->fileVar = 'files';
		$this->controller->FileUploader->fileModel = 'InstitutionSiteStaffAbsenceAttachment';
		$this->controller->FileUploader->allowEmptyUpload = true;
		$this->controller->FileUploader->additionalFileType();
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearOptions = ClassRegistry::init('SchoolYear')->getYearList();

		if ($this->action == 'add') {
			$this->fields['school_year_id'] = array(
				'type' => 'select',
				'options' => $yearOptions,
				'visible' => true,
				'order' => 1,
				'attr' => array('onchange' => "$('#reload').click()")
			);
			$this->setFieldOrder('school_year_id', 0);
		}
		
		$this->fields['full_day_absent']['type'] = 'select';
		$this->fields['full_day_absent']['options'] = array('Yes' => __('Yes'), 'No' => __('No'));
		$this->setFieldOrder('full_day_absent', 2);
		
		$settingStartTime = $this->controller->ConfigItem->getValue('start_time');
		$this->fields['start_time_absent']['type'] = 'time';
		$this->fields['start_time_absent']['attr']['value'] = $settingStartTime;
		
		$this->fields['end_time_absent']['type'] = 'time';
		$this->fields['end_time_absent']['attr']['value'] = $settingStartTime;
		
		$this->fields['absence_type']['type'] = 'select';
		$this->fields['absence_type']['options'] = array('Excused' => __('Excused'), 'Unexcused' => __('Unexcused'));
		
		$this->fields['staff_absence_reason_id']['type'] = 'select';
		$this->fields['staff_absence_reason_id']['options'] = $this->StaffAbsenceReason->getList();
		
		$this->fields['institution_site_id']['type'] = 'hidden';
		$this->fields['institution_site_id']['value'] = $institutionSiteId;
		
	}
	
	public function afterAction() {
		if ($this->action == 'add' || $this->action == 'edit') {
			$startDate = '';
			$endDate = '';
			$todayDate = date('Y-m-d');
					
			if($this->action == 'add'){
				$yearId = $this->controller->viewVars['selectedYear'];
				$yearObj = ClassRegistry::init('SchoolYear')->findById($yearId);
				$startDate = $yearObj['SchoolYear']['start_date'];
				$endDate = $yearObj['SchoolYear']['end_date'];
				
				if($todayDate >= $yearObj['SchoolYear']['start_date'] && $todayDate >= $yearObj['SchoolYear']['end_date']){
					$dataDate = $todayDate;
				}else{
					$dataDate = $startDate;
				}
			}
			
			if ($this->action == 'add') {
				if ($this->request->is('get')) {
					$dataStartDate = $startDate;
					$dataEndDate = $dataStartDate;
				} else {
					$dataStartDate = $this->request->data[$this->alias]['first_date_absent'];
					$dataEndDate = $this->request->data[$this->alias]['last_date_absent'];
				}
			} else {
				if ($this->request->is('get')) {
					$dataStartDate = $this->request->data[$this->alias]['first_date_absent'];
					$dataEndDate = $this->request->data[$this->alias]['last_date_absent'];
				}
			}
			
			$this->fields['first_date_absent']['attr'] = array(
				'startDate' => $startDate,
				'endDate' => $endDate,
				'data-date' => $dataStartDate
			);
			$this->fields['last_date_absent']['attr'] = array(
				'startDate' => $startDate,
				'endDate' => $endDate,
				'data-date' => $dataEndDate
			);
		} 
		
		if ($this->action == 'view') {
			$data = $this->controller->viewVars['data'];
			$data[$this->alias]['staff_id'] = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
			$this->controller->viewVars['data'] = $data;
			$this->setFieldOrder('staff_id', 0);
			$this->setVar('params', array('back' => 'absence'));
			
			$this->setFieldOrder('full_day_absent', 1);
			$this->setFieldOrder('staff_absence_reason_id', 10);
		} else if ($this->action == 'edit') {
			$data = $this->request->data;
			$this->fields['staff_id']['type'] = 'hidden';
			$this->fields['staff']['type'] = 'disabled';
			$this->fields['staff']['value'] = sprintf('%s %s', $data['Staff']['first_name'], $data['Staff']['last_name']);
			$this->fields['staff']['order'] = 0;
			$this->fields['staff']['visible'] = true;
			$this->request->data = $data;
			
			$this->setFieldOrder('full_day_absent', 1);
			$this->setFieldOrder('staff_absence_reason_id', 9);
		}else if ($this->action == 'add') {
			$this->setFieldOrder('full_day_absent', 2);
			$this->setFieldOrder('staff_absence_reason_id', 11);
		}
		
		parent::afterAction();
	}
	
	public function index($yearId=0, $weekId=null){
		$this->Navigation->addCrumb('Attendance - Staff');

		$yearList = ClassRegistry::init('SchoolYear')->find('list', array('conditions' => array('SchoolYear.visible' => 1), 'order' => array('SchoolYear.order')));
		if ($yearId != 0) {
			if (!array_key_exists($yearId, $yearList)) {
				$yearId = key($yearList);
			}
		} else {
			$yearId = key($yearList);
		}
		
		$weekList = $this->controller->getWeekListByYearId($yearId);
		$currentWeekId = $this->controller->getCurrentWeekId($yearId);
		if (!is_null($weekId)) {
			if (!array_key_exists($weekId, $weekList)) {
				$weekId = $currentWeekId;
			}
		} else {
			$weekId = $currentWeekId;
		}
		
		$startEndDates = $this->controller->getStartEndDateByYearWeek($yearId, $weekId);
		$startDate = $startEndDates['start_date'];
		$endDate = $startEndDates['end_date'];
		
		$header = $this->controller->generateAttendanceHeader($startDate, $endDate);
		$weekDayIndex = $this->controller->generateAttendanceWeekDayIndex($startDate, $endDate);
		
		$absenceData = $this->getAbsenceData($this->Session->read('InstitutionSite.id'), $yearId, $startDate, $endDate);
		$absenceCheckList = array();
		foreach($absenceData AS $absenceUnit){
			$absenceStaff = $absenceUnit['Staff'];
			$staffId = $absenceStaff['id'];
			$absenceRecord = $absenceUnit['InstitutionSiteStaffAbsence'];
			$indexAbsenceDate = date('Ymd', strtotime($absenceRecord['first_date_absent']));
			
			$absenceCheckList[$staffId][$indexAbsenceDate] = $absenceUnit;
			
			if(!empty($absenceRecord['last_date_absent']) && $absenceRecord['last_date_absent'] > $absenceRecord['first_date_absent']){
				$tempStartDate = date("Y-m-d", strtotime($absenceRecord['first_date_absent']));
				$formatedLastDate = date("Y-m-d", strtotime($absenceRecord['last_date_absent']));
				while($tempStartDate <= $formatedLastDate){
					$stampTempDate = strtotime($tempStartDate);
					$tempIndex = date('Ymd', $stampTempDate);
					
					$absenceCheckList[$staffId][$tempIndex] = $absenceUnit;
					
					$stampTempDateNew = strtotime('+1 day', $stampTempDate);
					$tempStartDate = date("Y-m-d", $stampTempDateNew);
				}
			}
		}
		
		$yearName = ClassRegistry::init('SchoolYear')->getSchoolYearById($yearId);
		
		$staffList = $this->controller->InstitutionSiteStaff->getStaffByInstitutionSite($this->Session->read('InstitutionSite.id'), $startDate, $endDate);
		if(empty($staffList)){
			$this->Message->alert('institutionSiteAttendance.no_staff');
		}
		
		$this->setVar(compact('yearList', 'yearId', 'weekList', 'weekId', 'header', 'weekDayIndex', 'staffList', 'absenceCheckList'));
	}
	
	public function absence($yearId=0, $classId=0, $weekId=null){
		$this->Navigation->addCrumb('Absence - Staff');
		$yearList = ClassRegistry::init('SchoolYear')->find('list', array('conditions' => array('SchoolYear.visible' => 1), 'order' => array('SchoolYear.order')));
		$currentYearId = ClassRegistry::init('SchoolYear')->getSchoolYearIdByDate(date('Y-m-d'));
		if($currentYearId){
			$defaultYearId = $currentYearId;
		}else{
			$defaultYearId = key($yearList);
		}
		
		if ($yearId != 0) {
			if (!array_key_exists($yearId, $yearList)) {
				$yearId = $defaultYearId;
			}
		} else {
			$yearId = $defaultYearId;
		}
		
		$weekList = $this->controller->getWeekListByYearId($yearId);
		$currentWeekId = $this->controller->getCurrentWeekId($yearId);
		if (!is_null($weekId)) {
			if (!array_key_exists($weekId, $weekList)) {
				$weekId = $currentWeekId;
			}
		} else {
			$weekId = $currentWeekId;
		}
		
		$startEndDates = $this->controller->getStartEndDateByYearWeek($yearId, $weekId);
		$startDate = $startEndDates['start_date'];
		$endDate = $startEndDates['end_date'];
		
		$data = $this->getAbsenceData($this->Session->read('InstitutionSite.id'), $yearId, $startDate, $endDate);
		if(empty($data)){
			$this->Message->alert('institutionSiteAttendance.no_data');
		}
		//pr($data);
		
		$this->setVar(compact('yearList', 'yearId', 'weekList', 'weekId', 'data'));
	}
	
	public function add(){
		$this->render = 'auto';
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
		$yearOptions = $this->fields['school_year_id']['options'];
		$selectedYear = 0;
		if (!empty($yearOptions)) {
			if (empty($selectedYear) || (!empty($selectedYear) && !array_key_exists($selectedYear, $yearOptions))) {
				$selectedYear = key($yearOptions);
			}
		}
		
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$selectedYear = $data[$this->alias]['school_year_id'];
			
			if ($data['submit'] != 'reload') {
				$this->create();
				if ($this->saveAll($data)) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => get_class($this), 'absence', $selectedYear));
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
			}
		} else {
			
		}
		
		$list = $this->controller->InstitutionSiteStaff->getStaffByYear($institutionSiteId, $selectedYear);
		$staffOptions = array();
		foreach ($list as $obj) {
			$staff = $obj['Staff'];
			$staffOptions[$staff['id']] = sprintf('%s - %s %s', $staff['identification_no'], $staff['first_name'], $staff['last_name']);
		}
		$this->fields['staff_id']['type'] = 'select';
		$this->fields['staff_id']['options'] = $staffOptions;
		$this->setFieldOrder('staff_id', 1);
		
		$this->setVar('params', array('back' => 'absence'));
		$this->setVar(compact('fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions', 'yearOptions', 'selectedYear', 'staffOptions'));
	}
	
	public function getAbsenceData($institutionSiteId, $schoolYearId, $startDate='', $endDate=''){
		$conditions = array();
		
		$conditions[] = 'InstitutionSiteStaffAbsence.institution_site_id = ' . $institutionSiteId;
		
		if(!empty($startDate) && !empty($endDate)){
			$conditions['OR'] = array(
					array(
						'InstitutionSiteStaffAbsence.first_date_absent >= "' . $startDate . '"',
						'InstitutionSiteStaffAbsence.first_date_absent <= "' . $endDate . '"'
					),
					array(
						'InstitutionSiteStaffAbsence.last_date_absent >= "' . $startDate . '"',
						'InstitutionSiteStaffAbsence.last_date_absent <= "' . $endDate . '"'
					)
			);
		}
		
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$schoolYear = $SchoolYear->getSchoolYearById($schoolYearId);
		$conditions[] = 'YEAR(InstitutionSiteStaffAbsence.first_date_absent) = "' . $schoolYear . '"';
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT InstitutionSiteStaffAbsence.id', 
				'InstitutionSiteStaffAbsence.absence_type', 
				'InstitutionSiteStaffAbsence.first_date_absent', 
				'InstitutionSiteStaffAbsence.last_date_absent', 
				'InstitutionSiteStaffAbsence.full_day_absent', 
				'InstitutionSiteStaffAbsence.start_time_absent', 
				'InstitutionSiteStaffAbsence.end_time_absent', 
				'Staff.id',
				'Staff.identification_no',
				'Staff.first_name',
				'Staff.middle_name',
				'Staff.last_name',
				'Staff.preferred_name'
			),
			'joins' => array(
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('InstitutionSiteStaffAbsence.staff_id = Staff.id')
				)
			),
			'conditions' => $conditions,
			'order' => array('InstitutionSiteStaffAbsence.first_date_absent', 'InstitutionSiteStaffAbsence.last_date_absent')
		));
		
		return $data;
	}
	
	public function getStaffAbsenceDataByMonth($staffId, $schoolYearId, $monthId){
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$schoolYear = $SchoolYear->getSchoolYearById($schoolYearId);
		
		$conditions = array(
			'Staff.id = ' . $staffId,
		);
		
		$conditions['OR'] = array(
			array(
				'MONTH(InstitutionSiteStaffAbsence.first_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStaffAbsence.first_date_absent) = "' . $schoolYear . '"'
			),
			array(
				'MONTH(InstitutionSiteStaffAbsence.last_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStaffAbsence.last_date_absent) = "' . $schoolYear . '"'
			)
		);
		
		$data = $this->find('all', array(
			'fields' => array(
				'DISTINCT InstitutionSiteStaffAbsence.id', 
				'InstitutionSiteStaffAbsence.absence_type', 
				'InstitutionSiteStaffAbsence.first_date_absent', 
				'InstitutionSiteStaffAbsence.last_date_absent', 
				'InstitutionSiteStaffAbsence.full_day_absent', 
				'InstitutionSiteStaffAbsence.start_time_absent', 
				'InstitutionSiteStaffAbsence.end_time_absent',
				'StaffAbsenceReason.name'
			),
			'conditions' => $conditions,
			'order' => array('InstitutionSiteStaffAbsence.first_date_absent', 'InstitutionSiteStaffAbsence.last_date_absent')
		));
		
		return $data;
	}
	
	public function getAbsenceById($absenceId){
		$data = $this->find('first', array(
			'fields' => array(
				'InstitutionSiteStaffAbsence.id', 
				'InstitutionSiteStaffAbsence.absence_type', 
				'InstitutionSiteStaffAbsence.first_date_absent', 
				'InstitutionSiteStaffAbsence.last_date_absent', 
				'InstitutionSiteStaffAbsence.full_day_absent', 
				'InstitutionSiteStaffAbsence.start_time_absent', 
				'InstitutionSiteStaffAbsence.end_time_absent', 
				'InstitutionSiteStaffAbsence.comment', 
				'InstitutionSiteStaffAbsence.created', 
				'InstitutionSiteStaffAbsence.modified', 
				'InstitutionSiteStaffAbsence.staff_id',
				'InstitutionSiteStaffAbsence.staff_absence_reason_id', 
				'Staff.id',
				'Staff.identification_no',
				'Staff.first_name',
				'Staff.middle_name',
				'Staff.last_name',
				'Staff.preferred_name',
				'StaffAbsenceReason.name',
				'CreatedUser.*', 
				'ModifiedUser.*'
			),
			'conditions' => array(
				'InstitutionSiteStaffAbsence.id' => $absenceId
			)
		));
		
		return $data;
	}
	
//	public function attendanceStaff($controller, $params){
//		$controller->Navigation->addCrumb('Attendance - Staff');
//
//		$yearList = ClassRegistry::init('SchoolYear')->getYearList();
//		//pr($yearList);
//		
//		if (isset($controller->params['pass'][0])) {
//			$yearId = $controller->params['pass'][0];
//			if (!array_key_exists($yearId, $yearList)) {
//                $yearId = key($yearList);
//            }
//		}else{
//			$yearId = key($yearList);
//		}
//		
//		$weekList = $controller->getWeekListByYearId($yearId);
//		//pr($weekList);
//		$currentWeekId = $controller->getCurrentWeekId($yearId);
//		if (isset($controller->params['pass'][1])) {
//			$weekId = $controller->params['pass'][1];
//			if (!array_key_exists($weekId, $weekList)) {
//                $weekId = $currentWeekId;
//            }
//		}else{
//			$weekId = $currentWeekId;
//		}
//		
//		$startEndDates = $controller->getStartEndDateByYearWeek($yearId, $weekId);
//		$startDate = $startEndDates['start_date'];
//		$endDate = $startEndDates['end_date'];
//		
//		$header = $controller->generateAttendanceHeader($startDate, $endDate);
//		$weekDayIndex = $controller->generateAttendanceWeekDayIndex($startDate, $endDate);
//		
//		$absenceData = $this->getAbsenceData($controller->Session->read('InstitutionSite.id'), $yearId, $startDate, $endDate);
//		$absenceCheckList = array();
//		foreach($absenceData AS $absenceUnit){
//			$absenceStaff = $absenceUnit['Staff'];
//			$staffId = $absenceStaff['id'];
//			$absenceRecord = $absenceUnit['InstitutionSiteStaffAbsence'];
//			$indexAbsenceDate = date('Ymd', strtotime($absenceRecord['first_date_absent']));
//			
//			$absenceCheckList[$staffId][$indexAbsenceDate] = $absenceUnit;
//			
//			if(!empty($absenceRecord['last_date_absent']) && $absenceRecord['last_date_absent'] > $absenceRecord['first_date_absent']){
//				$tempStartDate = date("Y-m-d", strtotime($absenceRecord['first_date_absent']));
//				$formatedLastDate = date("Y-m-d", strtotime($absenceRecord['last_date_absent']));
//				while($tempStartDate <= $formatedLastDate){
//					$stampTempDate = strtotime($tempStartDate);
//					$tempIndex = date('Ymd', $stampTempDate);
//					
//					$absenceCheckList[$staffId][$tempIndex] = $absenceUnit;
//					
//					$stampTempDateNew = strtotime('+1 day', $stampTempDate);
//					$tempStartDate = date("Y-m-d", $stampTempDateNew);
//				}
//			}
//		}
//		
//		$yearName = ClassRegistry::init('SchoolYear')->getSchoolYearById($yearId);
//		
//		$staffList = $controller->InstitutionSiteStaff->getStaffByInstitutionSite($controller->Session->read('InstitutionSite.id'), $startDate, $endDate);
//		if(empty($staffList)){
//			$controller->Message->alert('institutionSiteAttendance.no_staff');
//		}
//		
//		$controller->set(compact('yearList', 'yearId', 'weekList', 'weekId', 'header', 'weekDayIndex', 'staffList', 'absenceCheckList'));
//	}
//	
//	public function attendanceStaffAbsence($controller, $params){
//		$controller->Navigation->addCrumb('Absence - Staff');
//		
//		$yearList = ClassRegistry::init('SchoolYear')->getYearList();
//		//pr($yearList);
//		
//		if (isset($controller->params['pass'][0])) {
//			$yearId = $controller->params['pass'][0];
//			if (!array_key_exists($yearId, $yearList)) {
//                $yearId = key($yearList);
//            }
//		}else{
//			$yearId = key($yearList);
//		}
//		//pr($yearId);
//		
//		$weekList = $controller->getWeekListByYearId($yearId);
//		//pr($weekList);
//		$currentWeekId = $controller->getCurrentWeekId($yearId);
//		if (isset($controller->params['pass'][1])) {
//			$weekId = $controller->params['pass'][1];
//			if (!array_key_exists($weekId, $weekList)) {
//                $weekId = $currentWeekId;
//            }
//		}else{
//			$weekId = $currentWeekId;
//		}
//		//pr($weekId);
//		
//		$startEndDates = $controller->getStartEndDateByYearWeek($yearId, $weekId);
//		$startDate = $startEndDates['start_date'];
//		$endDate = $startEndDates['end_date'];
//		
//		$data = $this->getAbsenceData($controller->Session->read('InstitutionSite.id'), $yearId, $startDate, $endDate);
//		if(empty($data)){
//			$controller->Message->alert('institutionSiteAttendance.no_data');
//		}
//		//pr($data);
//		
//		$controller->set(compact('yearList', 'yearId', 'weekList', 'weekId', 'data'));
//	}
//	
//	public function attendanceStaffAbsenceAdd($controller, $params){
//		if($controller->request->is('get')){
//			$controller->Navigation->addCrumb('Absence - Staff', array('controller' => 'InstitutionSites', 'action' => 'attendanceStaffAbsence'));
//			$controller->Navigation->addCrumb('Add');
//			
//			$settingStartTime = $controller->ConfigItem->getValue('start_time');
//			$obj = array(
//				'InstitutionSiteStaffAbsence' => array(
//					'start_time_absent' => $settingStartTime
//				)
//			);
//			$controller->request->data = $obj;
//		}else{
//			//$this->create();
//			
//			$absenceData = $controller->request->data['InstitutionSiteStaffAbsence'];
//			$absenceData['staff_id'] = $absenceData['hidden_staff_id'];
//			unset($absenceData['hidden_staff_id']);
//			
//			$absenceData['institution_site_id'] = $controller->Session->read('InstitutionSite.id');
//			
//			$firstDateAbsent = $absenceData['first_date_absent'];
//			$firstDateAbsentData = new DateTime($firstDateAbsent);
//			$firstDateYear = $firstDateAbsentData->format('Y');
//			$firstDateYearId = ClassRegistry::init('SchoolYear')->getSchoolYearId($firstDateYear);
//			
//			if($absenceData['full_day_absent'] == 'Yes'){
//				$absenceData['start_time_absent'] = '';
//				$absenceData['end_time_absent'] = '';
//			}else{
//				$absenceData['last_date_absent'] = null;
//			}
//
//			$this->set($absenceData);
//			if ($this->validates()) {
//				if($this->save($absenceData)){
//							$newId = $this->getInsertID();
//							//pr($newId);
//							$postFileData = $controller->request->data[$this->alias]['files'];
//							$controller->FileUploader->additionData = array('institution_site_staff_absence_id' => $newId);
//							$controller->FileUploader->uploadFile(NULL, $postFileData);
//
//							if($controller->FileUploader->success){
//								$controller->Message->alert('general.add.success');
//								return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStaffAbsence'));
//							}
//						}
//			}
//		}
//		
//		$fullDayAbsentOptions = array('Yes' => __('Yes'), 'No' => __('No'));
//		$absenceReasonOptions =  $this->StaffAbsenceReason->getList();;
//		$absenceTypeOptions = array('Excused' => __('Excused'), 'Unexcused' => __('Unexcused'));
//		
//		$controller->set(compact('fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions'));
//	}
	
//	public function attendanceStaffSearchStaff($controller, $params){
//		//$controller->autoRender = false;
//		$this->render = false;
//        $search = $controller->params->query['term'];
//		
//		$result = $controller->InstitutionSiteStaff->getAutoCompleteList($search, $controller->Session->read('InstitutionSite.id'));
//        
//		//$result = array();
//        return json_encode($result);
//	}
//	
//	public function attendanceStaffAbsenceEdit($controller, $params){
//		if (isset($controller->params['pass'][0])) {
//            $absenceId = $controller->params['pass'][0];
//            $obj = $this->getAbsenceById($absenceId);
//
//            if (!$obj) {
//               return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStaffAbsence'));
//            }
//        }else {
//            return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStaffAbsence'));
//        }
//		
//		if($controller->request->is('get')){
//			$controller->Navigation->addCrumb('Absence - Staff', array('controller' => 'InstitutionSites', 'action' => 'attendanceStaffAbsence'));
//			$controller->Navigation->addCrumb('Absence Details');
//			
//			$controller->request->data = $obj;
//		}else{
//			$obj = $controller->request->data;
//			$absenceData = $controller->request->data['InstitutionSiteStaffAbsence'];
//			$absenceData['staff_id'] = $absenceData['hidden_staff_id'];
//			unset($absenceData['hidden_staff_id']);
//			
//			if($absenceData['full_day_absent'] == 'Yes'){
//				$absenceData['start_time_absent'] = '';
//				$absenceData['end_time_absent'] = '';
//			}else{
//				$absenceData['last_date_absent'] = null;
//			}
//			
//			$firstDateAbsent = $absenceData['first_date_absent'];
//			$firstDateAbsentData = new DateTime($firstDateAbsent);
//			$firstDateYear = $firstDateAbsentData->format('Y');
//			$firstDateYearId = ClassRegistry::init('SchoolYear')->getSchoolYearId($firstDateYear);
//			
//			if ($this->save($absenceData, array('validate' => 'only'))) {
//				if($this->save($absenceData)){
//							$postFileData = $controller->request->data[$this->alias]['files'];
//							$controller->FileUploader->additionData = array('institution_site_staff_absence_id' => $absenceId);
//							$controller->FileUploader->uploadFile(NULL, $postFileData);
//
//							if($controller->FileUploader->success){
//								$controller->Message->alert('general.edit.success');
//								return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStaffAbsenceView', $absenceId));
//							}
//						}
//			}
//			
//			if($absenceData['full_day_absent'] !== 'Yes'){
//				$obj['InstitutionSiteStudentAbsence']['last_date_absent'] = '';
//			}
//		}
//		
//		$fullDayAbsentOptions = array('Yes' => __('Yes'), 'No' => __('No'));
//		$absenceReasonOptions =  $this->StaffAbsenceReason->getList();;
//		$absenceTypeOptions = array('Excused' => __('Excused'), 'Unexcused' => __('Unexcused'));
//		
//		$attachments = $controller->FileUploader->getList(array('conditions' => array('InstitutionSiteStaffAbsenceAttachment.institution_site_staff_absence_id' => $absenceId)));
//		
//		$controller->set(compact('fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions', 'absenceId', 'obj', 'attachments'));
//	}
//	
//	public function attendanceStaffAbsenceView($controller, $params){
//		$controller->Navigation->addCrumb('Absence - Staff', array('controller' => 'InstitutionSites', 'action' => 'attendanceStaffAbsence'));
//		$controller->Navigation->addCrumb('Absence Details');
//		
//		if (isset($controller->params['pass'][0])) {
//            $absenceId = $controller->params['pass'][0];
//            $obj = $this->getAbsenceById($absenceId);
//
//            if ($obj) {
//                $controller->Session->write('InstitutionStaffAbsenceId', $absenceId);
//            } else {
//                return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStaffAbsence'));
//            }
//        }else if ($controller->Session->check('InstitutionStaffAbsenceId')){
//            $absenceId = $controller->Session->read('InstitutionStaffAbsenceId');
//            $obj = $this->getAbsenceById($absenceId);
//        } else {
//            return $controller->redirect(array('controller' => 'InstitutionSites', 'action' => 'attendanceStaffAbsence'));
//        }
//		//pr($obj);
//		$attachments = $controller->FileUploader->getList(array('conditions' => array('InstitutionSiteStaffAbsenceAttachment.institution_site_staff_absence_id' => $absenceId)));
//		//pr($attachments);
//		$controller->set(compact('obj', 'absenceId', 'attachments'));
//	}
//	
//	public function attendanceStaffAbsenceDelete($controller, $params){
//		if ($controller->Session->check('InstitutionStaffAbsenceId')) {
//			$absenceId = $controller->Session->read('InstitutionStaffAbsenceId');
//			$obj = $this->getAbsenceById($absenceId);
//			$staffName = $obj['Staff']['first_name'] . ' ' . $obj['Staff']['last_name'];
//
//			if($this->delete($absenceId)){
//				$InstitutionSiteStaffAbsenceAttachment = ClassRegistry::init('InstitutionSiteStaffAbsenceAttachment');
//				$InstitutionSiteStaffAbsenceAttachment->deleteAll(array('InstitutionSiteStaffAbsenceAttachment.institution_site_staff_absence_id' => $absenceId)); 
//				
//				$controller->Utility->alert($staffName . __(' have been deleted successfully.'));
//				$controller->redirect(array('action' => 'attendanceStaffAbsence'));
//			}
//		} else {
//			$controller->redirect(array('action' => 'attendanceStaffAbsence'));
//		}
//	}
	
//	public function beforeAction($controller, $action) {
//        $controller->set('model', $this->alias);
//		$controller->FileUploader->fileVar = 'files';
//		$controller->FileUploader->fileModel = 'InstitutionSiteStaffAbsenceAttachment';
//		$controller->FileUploader->allowEmptyUpload = true;
//		$controller->FileUploader->additionalFileType();
//    }
	
	public function attendanceStaffAjaxAddField($controller, $params) {
		$this->render =false;
		
		$fileId = $controller->request->data['size'];
		$multiple = true;
		$controller->set(compact('fileId', 'multiple'));
		$controller->render('/Elements/templates/file_upload_field');
	}
	
	public function attendanceStaffAttachmentsDownload($controller, $params) {
		$id = $params['pass'][0];
		$this->render = false;
		$controller->FileUploader->downloadFile($id);
    }
	
	public function attendanceStaffAttachmentDelete($controller, $params) {
        $this->render = false;
        if (!$controller->request->is('get')) {
            $result = array('alertOpt' => array());
            $controller->Utility->setAjaxResult('alert', $result);
            $id = $params->data['id'];

			$staffAbsenceAttachment = ClassRegistry::init('InstitutionSiteStaffAbsenceAttachment');
            if ($staffAbsenceAttachment->delete($id)) {
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
			__('First Date Absent'),
			__('Last Date Absent'),
			__('Full Day Absent'),
			__('Start Time Absent'),
			__('End Time Absent'),
			__('Staff OpenEMIS ID'),
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
			$options = array();
			//$options['recursive'] = -1;
			$options['fields'] = array(
				'InstitutionSiteStaffAbsence.first_date_absent', 
				'InstitutionSiteStaffAbsence.last_date_absent', 
				'InstitutionSiteStaffAbsence.full_day_absent', 
				'InstitutionSiteStaffAbsence.start_time_absent', 
				'InstitutionSiteStaffAbsence.end_time_absent', 
				
				'Staff.identification_no',
				'Staff.first_name',
				'Staff.middle_name',
				'Staff.last_name',
				'Staff.preferred_name',
				
				'InstitutionSiteStaffAbsence.absence_type', 
				'StaffAbsenceReason.name',
				'InstitutionSiteStaffAbsence.comment'
			);
			$options['order'] = array('InstitutionSiteStaffAbsence.first_date_absent', 'Staff.first_name', 'Staff.middle_name', 'Staff.last_name');
			$options['conditions'] = array('InstitutionSiteStaffAbsence.institution_site_id' => $institutionSiteId);
			
			$this->unbindModel(array('belongsTo' => array('InstitutionSite', 'ModifiedUser', 'CreatedUser')));

			$data = $this->find('all', $options);
			
			$newData = array();
			foreach($data AS $row){
				$tempRow = array();
				
				$absence = $row['InstitutionSiteStaffAbsence'];
				$staff = $row['Staff'];
				$reason = $row['StaffAbsenceReason'];
				
				$tempRow[] = $this->formatDateByConfig($absence['first_date_absent']);
				$tempRow[] = $this->formatDateByConfig($absence['last_date_absent']);
				$tempRow[] = $absence['full_day_absent'];
				$tempRow[] = $absence['start_time_absent'];
				$tempRow[] = $absence['end_time_absent'];
				
				$tempRow[] = $staff['identification_no'];
				$tempRow[] = $staff['first_name'];
				$tempRow[] = $staff['middle_name'];
				$tempRow[] = $staff['last_name'];
				$tempRow[] = $staff['preferred_name'];
				
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
		$index = $args[1];
		return 'Report_Staff_Attendance';
	}
	
}

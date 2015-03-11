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
		'TimePicker' => array('start_time_absent' => array('format' => 'h:i a'), 'end_time_absent' => array('format' => 'h:i a')),
		'ControllerAction2',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	//public $hasMany = array('InstitutionSiteStaffAbsenceAttachment');
    
	public $belongsTo = array(
		'Staff.Staff',
		'InstitutionSite',
		'Staff.StaffAbsenceReason',
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
				'rule' => array('compareDate', 'last_date_absent', true),
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
	
	public function beforeAction() {
		parent::beforeAction();
		$this->controller->FileUploader->fileVar = 'files';
		$this->controller->FileUploader->fileModel = 'InstitutionSiteStaffAbsenceAttachment';
		$this->controller->FileUploader->allowEmptyUpload = true;
		$this->controller->FileUploader->additionalFileType();
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$academicPeriodOptions = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodList();

		if ($this->action == 'add') {
			$this->fields['academic_period_id'] = array(
				'type' => 'select',
				'options' => $academicPeriodOptions,
				'visible' => true,
				'order' => 1,
				'attr' => array('onchange' => "$('#reload').click()")
			);
			$this->setFieldOrder('academic_period_id', 0);
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
			$todayDateFormatted = date('d-m-Y');
					
			if($this->action == 'add'){
				$academicPeriodId = $this->controller->viewVars['selectedAcademicPeriod'];
				$academicPeriodObj = ClassRegistry::init('AcademicPeriod')->findById($academicPeriodId);
				$startDate = $academicPeriodObj['AcademicPeriod']['start_date'];
				$endDate = $academicPeriodObj['AcademicPeriod']['end_date'];
				
				if(strtotime($todayDate) >= strtotime($startDate) && strtotime($todayDate) <= strtotime($endDate)){
					$dataStartDate = $todayDateFormatted;
					$dataEndDate = $todayDateFormatted;
				}else{
					$dataStartDate = $startDate;
					$dataEndDate = $startDate;
				}
			}
			
			if ($this->action == 'add') {
				if (!$this->request->is('get')) {
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
			$data[$this->alias]['staff_id'] = ModelHelper::getName($data['Staff']['SecurityUser']);
			$this->controller->viewVars['data'] = $data;
			$this->setFieldOrder('staff_id', 0);
			$this->setVar('params', array('back' => $this->Session->read('InstitutionSiteStaffAbsence.backLink')));
			
			$this->setFieldOrder('full_day_absent', 1);
			$this->setFieldOrder('staff_absence_reason_id', 10);
		} else if ($this->action == 'edit') {
			$data = $this->request->data;
			$this->fields['staff_id']['type'] = 'hidden';
			$this->fields['staff']['type'] = 'disabled';
			$this->fields['staff']['value'] = ModelHelper::getName($data['Staff']['SecurityUser']);
			$this->fields['staff']['order'] = 0;
			$this->fields['staff']['visible'] = true;
			$this->request->data = $data;
			
			$this->setFieldOrder('full_day_absent', 1);
			$this->setFieldOrder('staff_absence_reason_id', 9);
			$customJs = array('institution_attendance');
			$this->setVar('params', array('js'=> $customJs));
		}else if ($this->action == 'add') {
			$this->setFieldOrder('full_day_absent', 2);
			$this->setFieldOrder('staff_absence_reason_id', 11);
		}
		
		parent::afterAction();
	}

	public function view($id) {
		$this->contain(array('Staff'=>array('SecurityUser'=>array('fields'=>array('first_name','middle_name','third_name','last_name','preferred_name')))));
		parent::view($id);
	}

	public function edit($id) {
		$this->contain(array('Staff'=>array('SecurityUser'=>array('fields'=>array('first_name','middle_name','third_name','last_name','preferred_name')))));
		parent::edit($id);
	}
	
	public function index($academicPeriodId=0, $weekId=null, $dayId=null){
		if ($dayId==null || $dayId!=0) {
			return $this->redirect(array('action' => get_class($this), 'dayview', $academicPeriodId, $weekId, $dayId));
		}

		$this->Navigation->addCrumb('Attendance - Staff');

		$academicPeriodList = ClassRegistry::init('AcademicPeriod')->getAvailableAcademicPeriods();
		$currentAcademicPeriodId = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodIdByDate(date('Y-m-d'));
		if($currentAcademicPeriodId){
			$defaultAcademicPeriodId = $currentAcademicPeriodId;
		}else{
			$defaultAcademicPeriodId = key($academicPeriodList);
		}
		if ($academicPeriodId != 0) {
			if (!array_key_exists($academicPeriodId, $academicPeriodList)) {
				$academicPeriodId = $defaultAcademicPeriodId;
			}
		} else {
			$academicPeriodId = $defaultAcademicPeriodId;
		}
		
		$weekList = $this->controller->getWeekListByAcademicPeriodId($academicPeriodId);
		$currentWeekId = $this->controller->getCurrentWeekId($academicPeriodId);
		if (!is_null($weekId)) {
			if (!array_key_exists($weekId, $weekList)) {
				$weekId = $currentWeekId;
			}
		} else {
			$weekId = $currentWeekId;
		}
		
		$startEndDates = $this->controller->getStartEndDateByAcademicPeriodWeek($academicPeriodId, $weekId);
		$startDate = $startEndDates['start_date'];
		$endDate = $startEndDates['end_date'];
		
		$header = $this->controller->generateAttendanceHeader($startDate, $endDate);
		$headerDates = $this->controller->generateAttendanceHeaderDates($startDate, $endDate);
		$weekDayIndex = $this->controller->generateAttendanceWeekDayIndex($startDate, $endDate);
		
		$absenceData = $this->getAbsenceData($this->Session->read('InstitutionSite.id'), $academicPeriodId, $startDate, $endDate);
		$absenceCheckList = array();
		foreach($absenceData AS $absenceUnit){
			$absenceStaff = $absenceUnit['Staff'];
			$staffId = $absenceStaff['id'];
			$absenceRecord = $absenceUnit['InstitutionSiteStaffAbsence'];
			$indexAbsenceDate = date('Ymd', strtotime($absenceRecord['first_date_absent']));
			
			$absenceCheckList[$staffId][$indexAbsenceDate] = $absenceUnit;
			
			if($absenceRecord['full_day_absent'] == 'Yes' && !empty($absenceRecord['last_date_absent']) && $absenceRecord['last_date_absent'] > $absenceRecord['first_date_absent']){
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
		
		$academicPeriodName = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodById($academicPeriodId);
		
		$staffList = $this->controller->InstitutionSiteStaff->getStaffByInstitutionSite($this->Session->read('InstitutionSite.id'), $startDate, $endDate);
		if(empty($staffList)){
			$this->Message->alert('institutionSiteAttendance.no_staff');
		}

		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}
		
		$this->Session->write('InstitutionSiteStaffAbsence.backLink', array('index',$academicPeriodId,$weekId));
		$this->setVar(compact('academicPeriodList', 'academicPeriodId', 'weekList', 'weekId', 'header', 'weekDayIndex', 'weekDayList', 'staffList', 'absenceCheckList'));
	}
	
	public function absence($academicPeriodId=0, $weekId=null, $dayId=null){
		$this->Navigation->addCrumb('Absence - Staff');
		$academicPeriodList = ClassRegistry::init('AcademicPeriod')->getAvailableAcademicPeriods(true);
		$currentAcademicPeriodId = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodIdByDate(date('Y-m-d'));
		if($currentAcademicPeriodId){
			$defaultAcademicPeriodId = $currentAcademicPeriodId;
		}else{
			$defaultAcademicPeriodId = key($academicPeriodList);
		}
		
		if ($academicPeriodId != 0) {
			if (!array_key_exists($academicPeriodId, $academicPeriodList)) {
				$academicPeriodId = $defaultAcademicPeriodId;
			}
		} else {
			$academicPeriodId = $defaultAcademicPeriodId;
		}
		
		$weekList = $this->controller->getWeekListByAcademicPeriodId($academicPeriodId);
		$currentWeekId = $this->controller->getCurrentWeekId($academicPeriodId);
		if (!is_null($weekId)) {
			if (!array_key_exists($weekId, $weekList)) {
				$weekId = $currentWeekId;
			}
		} else {
			$weekId = $currentWeekId;
		}

		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		$startEndDates = $this->controller->getStartEndDateByAcademicPeriodWeek($academicPeriodId, $weekId);
		$startDate = $startEndDates['start_date'];
		$endDate = $startEndDates['end_date'];
		$weekDayIndex = $this->controller->generateAttendanceWeekDayIndex($startDate, $endDate);
		$headerDates = $this->controller->generateAttendanceHeaderDates($startDate, $endDate);
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}
		
		$todayDate = date("Ymd");
		if ($dayId!=null && $dayId!=0) {
			$selectedDateDigit = $weekDayIndex[$dayId-1];
			$selectedDate = substr($selectedDateDigit,0,4)."-".substr($selectedDateDigit,4,2)."-".substr($selectedDateDigit,6,2);
			$startDate = $selectedDate;
			$endDate = $selectedDate;
		}
		
		$data = $this->getAbsenceData($this->Session->read('InstitutionSite.id'), $academicPeriodId, $startDate, $endDate);
		if(empty($data)){
			$this->Message->alert('institutionSiteAttendance.no_data');
		}
		
		$this->Session->write('InstitutionSiteStaffAbsence.backLink', array('absence',$academicPeriodId,$weekId));
		$this->setVar(compact('academicPeriodList', 'academicPeriodId', 'dayId', 'weekList', 'weekDayList', 'weekId', 'data'));
	}
	
	public function add(){
		$this->render = 'auto';
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$academicPeriodOptions = $this->fields['academic_period_id']['options'];
		$selectedAcademicPeriod = 0;
		if (!empty($academicPeriodOptions)) {
			if (empty($selectedAcademicPeriod) || (!empty($selectedAcademicPeriod) && !array_key_exists($selectedAcademicPeriod, $academicPeriodOptions))) {
				$selectedAcademicPeriod = key($academicPeriodOptions);
			}
		}
		
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$selectedAcademicPeriod = $data[$this->alias]['academic_period_id'];
			
			if ($data['submit'] != 'reload') {
				$this->create();
				if ($this->saveAll($data)) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => get_class($this), 'absence', $selectedAcademicPeriod));
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
			}
		} else {
			
		}
		
		$list = $this->controller->InstitutionSiteStaff->getStaffByAcademicPeriod($institutionSiteId, $selectedAcademicPeriod);
		$staffOptions = array();
		foreach ($list as $obj) {
			$staff = $obj['Staff'];
			$staffOptions[$staff['id']] = ModelHelper::getName($obj['SecurityUser'], array('openEmisId'=>true));
		}
		$this->fields['staff_id']['type'] = 'select';
		$this->fields['staff_id']['options'] = $staffOptions;
		$this->setFieldOrder('staff_id', 1);
		
		$customJs = array('institution_attendance');
		$this->setVar('params', array('back' => 'absence', 'js'=> $customJs));
		$this->setVar(compact('fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions', 'academicPeriodOptions', 'selectedAcademicPeriod', 'staffOptions'));
	}

	public function dayview($academicPeriodId=0, $weekId=null, $dayId=null) {
		if (!is_null($dayId) && $dayId==0) {
			return $this->redirect(array('action' => get_class($this), 'index', $academicPeriodId, $weekId, 0));
		}

		$this->Navigation->addCrumb('Attendance - Staff');

		$academicPeriodList = ClassRegistry::init('AcademicPeriod')->getAvailableAcademicPeriods();
		$currentAcademicPeriodId = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodIdByDate(date('Y-m-d'));
		if($currentAcademicPeriodId){
			$defaultAcademicPeriodId = $currentAcademicPeriodId;
		}else{
			$defaultAcademicPeriodId = key($academicPeriodList);
		}
		if ($academicPeriodId != 0) {
			if (!array_key_exists($academicPeriodId, $academicPeriodList)) {
				$academicPeriodId = $defaultAcademicPeriodId;
			}
		} else {
			$academicPeriodId = $defaultAcademicPeriodId;
		}
		
		$weekList = $this->controller->getWeekListByAcademicPeriodId($academicPeriodId);
		$currentWeekId = $this->controller->getCurrentWeekId($academicPeriodId);
		if (!is_null($weekId)) {
			if (!array_key_exists($weekId, $weekList)) {
				$weekId = $currentWeekId;
			}
		} else {
			$weekId = $currentWeekId;
		}

		$startEndDates = $this->controller->getStartEndDateByAcademicPeriodWeek($academicPeriodId, $weekId);
		$startDate = $startEndDates['start_date'];
		$endDate = $startEndDates['end_date'];

		$weekDayIndex = $this->controller->generateAttendanceWeekDayIndex($startDate, $endDate);

		$header = $this->controller->generateAttendanceDayHeader($startDate, $endDate);
		$headerDates = $this->controller->generateAttendanceHeaderDates($startDate, $endDate);

		$weekDayIndex = $this->controller->generateAttendanceWeekDayIndex($startDate, $endDate);

		$todayDate = date("Ymd");
		if ($dayId==null) {
			if (array_search($todayDate, $weekDayIndex)) {
				$dayId = array_search($todayDate, $weekDayIndex)+1;
			} else {
				$dayId = 1;
			}
		}

		// have to find current date
		$selectedDateDigit = $weekDayIndex[$dayId-1];
		$selectedDate = substr($selectedDateDigit,0,4)."-".substr($selectedDateDigit,4,2)."-".substr($selectedDateDigit,6,2);
		
		$absenceData = $this->getAbsenceData($this->Session->read('InstitutionSite.id'), $academicPeriodId, $startDate, $endDate);
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

		$academicPeriodName = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodById($academicPeriodId);
		$staffList = $this->controller->InstitutionSiteStaff->getStaffByInstitutionSite($this->Session->read('InstitutionSite.id'), $startDate, $endDate);
		if(empty($staffList)){
			$this->Message->alert('institutionSiteAttendance.no_staff');
		}

		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}

		$this->setVar(compact('academicPeriodList', 'academicPeriodId', 'weekList', 'weekId', 'dayId', 'header', 'weekDayIndex', 'selectedDateDigit', 'selectedDate', 'weekDayList', 'staffList', 'absenceCheckList'));
	}

	public function dayedit($academicPeriodId=0, $weekId=null, $dayId=null) {
		if ($dayId==null||$dayId==0) {
			return $this->redirect(array('action' => get_class($this), 'index', $academicPeriodId, $weekId));
		}

		$this->Navigation->addCrumb('Attendance - Staff');

		$academicPeriodList = ClassRegistry::init('AcademicPeriod')->getAvailableAcademicPeriods();
		$currentAcademicPeriodId = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodIdByDate(date('Y-m-d'));
		if($currentAcademicPeriodId){
			$defaultAcademicPeriodId = $currentAcademicPeriodId;
		}else{
			$defaultAcademicPeriodId = key($academicPeriodList);
		}
		if ($academicPeriodId != 0) {
			if (!array_key_exists($academicPeriodId, $academicPeriodList)) {
				$academicPeriodId = $defaultAcademicPeriodId;
			}
		} else {
			$academicPeriodId = $defaultAcademicPeriodId;
		}
		
		$weekList = $this->controller->getWeekListByAcademicPeriodId($academicPeriodId);
		$currentWeekId = $this->controller->getCurrentWeekId($academicPeriodId);
		if (!is_null($weekId)) {
			if (!array_key_exists($weekId, $weekList)) {
				$weekId = $currentWeekId;
			}
		} else {
			$weekId = $currentWeekId;
		}

		$startEndDates = $this->controller->getStartEndDateByAcademicPeriodWeek($academicPeriodId, $weekId);
		$startDate = $startEndDates['start_date'];
		$endDate = $startEndDates['end_date'];

		$weekDayIndex = $this->controller->generateAttendanceWeekDayIndex($startDate, $endDate);

		$header = $this->controller->generateAttendanceDayHeader($startDate, $endDate);
		$headerDates = $this->controller->generateAttendanceHeaderDates($startDate, $endDate);

		$weekDayIndex = $this->controller->generateAttendanceWeekDayIndex($startDate, $endDate);

		$todayDate = date("Ymd");
		if ($dayId==null) {
			if (array_search($todayDate, $weekDayIndex)) {
				$dayId = array_search($todayDate, $weekDayIndex);
			} else {
				$dayId = 0;
			}
		}

		// have to find current date
		$selectedDateDigit = $weekDayIndex[$dayId-1];
		$selectedDate = substr($selectedDateDigit,0,4)."-".substr($selectedDateDigit,4,2)."-".substr($selectedDateDigit,6,2);

		if ($this->request->is(array('post', 'put'))) {
			$absenceOptions = array();
			$absenceOptions['academicPeriodId'] = $academicPeriodId;
			$absenceOptions['weekId'] = $weekId;
			$absenceOptions['selectedDate'] = $selectedDate;
			$absenceOptions['dayId'] = $dayId;
			$this->processAbsenceSaveInDayView($this->request->data, $absenceOptions);
		}
		
		$absenceData = $this->getAbsenceData($this->Session->read('InstitutionSite.id'), $academicPeriodId, $startDate, $endDate);
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

		$academicPeriodName = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodById($academicPeriodId);
		$staffList = $this->controller->InstitutionSiteStaff->getStaffByInstitutionSite($this->Session->read('InstitutionSite.id'), $startDate, $endDate);
		if(empty($staffList)){
			$this->Message->alert('institutionSiteAttendance.no_staff');
		}

		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}

		$absenceTypeList = $this->controller->getDayViewAttendanceOptions();
		$absenceReasonList = $this->StaffAbsenceReason->getList();

		$this->setVar(compact('academicPeriodList', 'academicPeriodId', 'weekList', 'weekId', 'dayId', 'header', 'weekDayIndex', 'selectedDateDigit', 'selectedDate', 'weekDayList', 'staffList', 'absenceCheckList','absenceTypeList', 'absenceReasonList'));
	}

	public function getAbsenceData($institutionSiteId, $academicPeriodId, $startDate='', $endDate=''){
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
		
		/* Commented instead of delete due to the need to re-work the logic using AcademicPeriod date range.*/ 
		/* As of 26 Feb 2015, all statements that call this function are able to provide $startDate and $endDate*/ 
		//$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		//$academicPeriod = $AcademicPeriod->getAcademicPeriodById($academicPeriodId);
		//$conditions[] = 'YEAR(InstitutionSiteStaffAbsence.first_date_absent) >= "' . $academicPeriod['start_date'] . '"';

		$data = $this->find('all', array(
			'contain' => array(
				'Staff' => array(
					'fields' => array('id'),
					'SecurityUser' => array('openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name')),
				'StaffAbsenceReason'
			),
			'conditions' => $conditions,
			'order' => array('InstitutionSiteStaffAbsence.first_date_absent', 'InstitutionSiteStaffAbsence.last_date_absent')
		));

		return $data;
	}

	public function processAbsenceSaveInDayView($data, $options=array()) {
		$processedData = array('InstitutionSiteStaffAbsence'=>array());
		foreach ($data['InstitutionSiteStaffAbsence'] as $key => $value) {
			$staff_attendance_type = $value['staff_attendance_type'];
			unset($value['staff_attendance_type']);
			$conditions = array();
			$conditions['AND'] = array();
			$conditions['AND']['InstitutionSiteStaffAbsence.first_date_absent <='] = $options['selectedDate'];
			$conditions['AND']['InstitutionSiteStaffAbsence.last_date_absent >='] = $options['selectedDate'];
			$conditions['institution_site_id'] = $this->controller->Session->read('InstitutionSite.id');
			$conditions['staff_id'] = $value['staff_id'];

			$found = $this->find(
					'all',
					array(
						'fields' => array('InstitutionSiteStaffAbsence.*'),
						'conditions' => $conditions
					)
				);

			if ($staff_attendance_type!='0') {
				// Marking Absent
				switch ($staff_attendance_type) {
					case '1': $currentAbsenceType = 'Excused'; break;
					case '2': $currentAbsenceType = 'Unexcused'; break;
				}
				if (!empty($found)) {
					foreach ($found as $foundKey => $foundValue) {
						$foundValue['InstitutionSiteStaffAbsence']['absence_type'] = $currentAbsenceType;
						$foundValue['InstitutionSiteStaffAbsence']['staff_absence_reason_id'] = $value['staff_absence_reason_id'];
					}
					$this->save($foundValue);
				} else {
					if (!array_key_exists('selectedDate', $options)) {
						continue;
					}
					$value['first_date_absent'] = $options['selectedDate'];
					$value['last_date_absent'] = $options['selectedDate'];
					$value['full_day_absent'] = 'Yes';
					$value['start_time_absent'] = '12:00 AM';
					$value['end_time_absent'] = '11:59 PM';
					$value['absence_type'] = $currentAbsenceType;
					$value['institution_site_id'] = $this->controller->Session->read('InstitutionSite.id');
					$this->create();
					$this->save($value);
				}
			} else {
				// Marking present
				if (!empty($found)) {
					foreach ($found as $key => $value) {
						$currAbsence = $found[$key]['InstitutionSiteStaffAbsence'];
						if (date('Ymd', strtotime($currAbsence['first_date_absent'])) == date('Ymd', strtotime($options['selectedDate']))) {
							$this->delete($currAbsence['id']);
						} else if (strtotime($currAbsence['first_date_absent']) < strtotime($options['selectedDate'])) {
							$currAbsence['last_date_absent'] = date('d-m-Y', strtotime('-1 day', strtotime($options['selectedDate'])));
							$currAbsence['end_time_absent'] = '11:59 PM';
							$this->save(array('InstitutionSiteStaffAbsence'=>$currAbsence));
						}
					}
				} 
			}
			
		}

		$this->Message->alert('general.add.success');
		return $this->redirect(array('action' => get_class($this), 'dayview', $options['academicPeriodId'], $options['weekId'], $options['dayId']));
	}
	
	public function getStaffAbsenceDataByMonth($staffId, $academicPeriodId, $monthId){
		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		$academicPeriod = $AcademicPeriod->getAcademicPeriodById($academicPeriodId);
		
		$conditions = array(
			'Staff.id = ' . $staffId,
		);
		
		$conditions['OR'] = array(
			array(
				'MONTH(InstitutionSiteStaffAbsence.first_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStaffAbsence.first_date_absent) = "' . $academicPeriod . '"'
			),
			array(
				'MONTH(InstitutionSiteStaffAbsence.last_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStaffAbsence.last_date_absent) = "' . $academicPeriod . '"'
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
				'SecurityUser.openemis_no',
				'SecurityUser.first_name',
				'SecurityUser.middle_name',
				'SecurityUser.third_name',
				'SecurityUser.last_name',
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
				
				'SecurityUser.openemis_no',
				'SecurityUser.first_name',
				'SecurityUser.middle_name',
				'SecurityUser.third_name',
				'SecurityUser.last_name',
				'Staff.preferred_name',
				
				'InstitutionSiteStaffAbsence.absence_type', 
				'StaffAbsenceReason.name',
				'InstitutionSiteStaffAbsence.comment'
			);
			$options['order'] = array('InstitutionSiteStaffAbsence.first_date_absent', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name');
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
				
				$tempRow[] = $staff['openemis_no'];
				$tempRow[] = $staff['first_name'];
				$tempRow[] = $staff['middle_name'];
				$tempRow[] = $staff['third_name'];
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

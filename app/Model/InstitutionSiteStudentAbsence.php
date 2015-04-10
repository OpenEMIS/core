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
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.last_name'))),
		'DatePicker' => array(
			'first_date_absent', 'last_date_absent'
		),
		'TimePicker' => array('start_time_absent' => array('format' => 'h:i a'), 'end_time_absent' => array('format' => 'h:i a')),
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'Students.Student',
		'InstitutionSiteSection',
		'Students.StudentAbsenceReason',
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
		'institution_site_section_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Section'
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
				'rule' => array('compareDate', 'last_date_absent', true),
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

	/* Excel Behaviour */
	public function excelGetConditions() {
		$id = CakeSession::read('InstitutionSite.id');
		$conditions = array('InstitutionSiteSection.institution_site_id' => $id);
		return $conditions;
	}
	/* End Excel Behaviour */
	
	public function beforeAction() {
		parent::beforeAction();
		$this->controller->FileUploader->fileVar = 'files';
		$this->controller->FileUploader->fileModel = 'InstitutionSiteStudentAbsenceAttachment';
		$this->controller->FileUploader->allowEmptyUpload = true;
		$this->controller->FileUploader->additionalFileType();
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$academicPeriodOptions = $this->InstitutionSiteSection->getAcademicPeriodOptions(array('InstitutionSiteSection.institution_site_id' => $institutionSiteId));
		
		if ($this->action == 'add') {
			$this->fields['academic_period_id'] = array(
				'type' => 'select',
				'options' => $academicPeriodOptions,
				'visible' => true,
				'order' => 1,
				'attr' => array('onchange' => "$('#reload').click()")
			);
			$this->setFieldOrder('academic_period_id', 1);
		}
		
		$this->fields['institution_site_section_id']['attr'] = array('onchange' => "$('#reload').click()");
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
		$this->fields['student_absence_reason_id']['type'] = 'select';
		$this->fields['student_absence_reason_id']['options'] = $this->StudentAbsenceReason->getList();
		$this->setFieldOrder('student_absence_reason_id', 9);
		
	}
	
	public function afterAction() {
		if ($this->action == 'add' || $this->action == 'edit') {
			$todayDate = date('Y-m-d');
			$todayDateFormatted = date('d-m-Y');
			
			$academicPeriodId = 0;

			if ($this->action == 'add') {
				$academicPeriodId = $this->controller->viewVars['selectedAcademicPeriod'];
			} else {
				$sectionId = $this->request->data['InstitutionSiteStudentAbsence']['institution_site_section_id'];
				$academicPeriodId = $this->InstitutionSiteSection->field('InstitutionSiteSection.academic_period_id', array('InstitutionSiteSection.id' => $sectionId));
			}

			//pr($this->request->data);die;
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
			$sectionId = $data[$this->alias]['institution_site_section_id'];
			$data[$this->alias]['student_id'] = ModelHelper::getName($data['Student']['SecurityUser']);
			$data[$this->alias]['institution_site_section_id'] = $this->InstitutionSiteSection->field('InstitutionSiteSection.name', array('InstitutionSiteSection.id' => $sectionId));
			$this->controller->viewVars['data'] = $data;
			$this->setFieldOrder('institution_site_section_id', 0);
			$this->setFieldOrder('student_id', 1);
			$this->setVar('params', array('back' => $this->Session->read('InstitutionSiteStudentAbsence.backLink')));
		} else if ($this->action == 'edit') {
			//pr($this->request->data);die;

			$data = $this->request->data;
			$sectionId = $data[$this->alias]['institution_site_section_id'];
			$this->fields['section']['type'] = 'disabled';
			$this->fields['section']['value'] = $this->InstitutionSiteSection->field('InstitutionSiteSection.name', array('InstitutionSiteSection.id' => $sectionId));
			$this->fields['section']['order'] = 0;
			$this->fields['section']['visible'] = true;
			$this->setFieldOrder('section', 0);
			$this->fields['student_id']['type'] = 'hidden';
			$this->fields['student']['type'] = 'disabled';
			$this->fields['student']['order'] = 1;
			$this->fields['student']['visible'] = true;

			if ($this->request->is('get')) {
				$this->fields['student']['value'] = ModelHelper::getName($data['Student']['SecurityUser']);
			} else {
				$this->fields['student']['value'] = $this->request->data[$this->alias]['studentName'];
			}
			$this->fields['studentName'] = array(
				'type' => 'hidden',
				'value' => $this->fields['student']['value'],
				'visible' => true
			);
			$this->fields['institution_site_section_id']['type'] = 'hidden';
			$this->request->data = $data;
			$customJs = array('institution_attendance');
			$this->setVar('params', array('js'=> $customJs));
		}
		$this->setFieldOrder('full_day_absent', 2);
		parent::afterAction();
	}

	public function view($id) {
		$this->contain(array('Student'=>array('SecurityUser'=>array('fields'=>array('first_name','middle_name','third_name','last_name','preferred_name')))));
		parent::view($id);
	}

	public function edit($id) {
		$this->contain(array('Student'=>array('SecurityUser'=>array('fields'=>array('first_name','middle_name','third_name','last_name','preferred_name')))));
		parent::edit($id);
	}
	
	public function index($academicPeriodId=0, $sectionId=null, $weekId=null, $dayId=null) {
		if ($dayId==null || $dayId!=0) {
			return $this->redirect(array('action' => get_class($this), 'dayview', $academicPeriodId, $sectionId, $weekId, $dayId));	
		}

		$this->Navigation->addCrumb('Attendance - Students');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
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
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $academicPeriodId);
		if(!empty($sectionOptions)){
			if(isset($sectionId)){
				if ($sectionId != 0) {
					if (!array_key_exists($sectionId, $sectionOptions)) {
						$sectionId = key($sectionOptions);
					}
				}
			}else{
				$sectionId = key($sectionOptions);
			}
		}
		$sectionOptions = $this->controller->Option->prependLabel($sectionOptions, 'InstitutionSiteStudentAbsence.select_section');
		
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
		
		$absenceData = $this->getAbsenceData($institutionSiteId, $academicPeriodId, $sectionId, $startDate, $endDate);
		$absenceCheckList = array();
		foreach($absenceData AS $absenceUnit){
			$absenceStudent = $absenceUnit['Student'];
			$studentId = $absenceStudent['id'];
			$absenceRecord = $absenceUnit['InstitutionSiteStudentAbsence'];
			$indexAbsenceDate = date('Ymd', strtotime($absenceRecord['first_date_absent']));
			
			$absenceCheckList[$studentId][$indexAbsenceDate] = $absenceUnit;
			
			if($absenceRecord['full_day_absent'] == 'Yes' && !empty($absenceRecord['last_date_absent']) && $absenceRecord['last_date_absent'] > $absenceRecord['first_date_absent']){
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

		$InstitutionSiteSectionStudentModel = ClassRegistry::init('InstitutionSiteSectionStudent');
		$studentList = $InstitutionSiteSectionStudentModel->getSectionStudents($sectionId, $startDate, $endDate);
		if(empty($studentList)){
			$this->Message->alert('general.noData');
		}
		
		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}

		$this->Session->write('InstitutionSiteStudentAbsence.backLink', array('index',$academicPeriodId,$sectionId,$weekId));
		$this->setVar(compact('academicPeriodList', 'academicPeriodId', 'sectionOptions', 'sectionId', 'weekList', 'weekId', 'header', 'weekDayIndex', 'weekDayList', 'studentList', 'absenceCheckList'));
	}
	
	public function absence($academicPeriodId=0, $sectionId=null, $weekId=null, $dayId=null) {
		$this->Navigation->addCrumb('Absence - Students');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
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
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $academicPeriodId);
		if(!empty($sectionOptions)){
			if(isset($sectionId)){
				if ($sectionId != 0) {
					if (!array_key_exists($sectionId, $sectionOptions)) {
						$sectionId = key($sectionOptions);
					}
				}
			}else{
				$sectionId = key($sectionOptions);
			}
		}
		$sectionOptions = $this->controller->Option->prependLabel($sectionOptions, 'InstitutionSiteStudentAbsence.select_section');
		
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

		$data = $this->getAbsenceData($institutionSiteId, $academicPeriodId, $sectionId, $startDate, $endDate);
		if(empty($data)){
			$this->Message->alert('general.noData');
		}
		$this->Session->write('InstitutionSiteStudentAbsence.backLink', array('absence',$academicPeriodId,$sectionId,$weekId));
		$this->setVar(compact('academicPeriodList', 'academicPeriodId', 'dayId', 'sectionOptions', 'sectionId', 'weekDayList', 'weekList', 'weekId', 'data'));
	}
	
	public function add() {
		$this->render = 'auto';
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$InstitutionSiteSectionStudentModel = ClassRegistry::init('InstitutionSiteSectionStudent');

		$academicPeriodOptions = $this->fields['academic_period_id']['options'];
		$selectedAcademicPeriod = 0;
		$selectedSection = 0;
		if (!empty($academicPeriodOptions)) {
			if (empty($selectedAcademicPeriod) || (!empty($selectedAcademicPeriod) && !array_key_exists($selectedAcademicPeriod, $academicPeriodOptions))) {
				$selectedAcademicPeriod = key($academicPeriodOptions);
			}
		}

		
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$selectedAcademicPeriod = $data[$this->alias]['academic_period_id'];
			$selectedSection = $data[$this->alias]['institution_site_section_id'];
			
			if ($data['submit'] != 'reload') {
				$this->create();
				if ($this->saveAll($data)) {
					// trigger alert
					$studentId = $data['InstitutionSiteStudentAbsence']['student_id'];
					$currentAcademicPeriodId = ClassRegistry::init('AcademicPeriod')->getCurrent();
					$this->controller->Alert->trigger(array('Attendance', $studentId, $currentAcademicPeriodId, $institutionSiteId));
					
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => get_class($this), 'absence', $selectedAcademicPeriod));
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
			}
		}
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $selectedAcademicPeriod);
		$this->fields['institution_site_section_id']['type'] = 'select';
		$this->fields['institution_site_section_id']['options'] = $sectionOptions;
		$this->setFieldOrder('institution_site_section_id', 2);
		if (!empty($sectionOptions)) {
			if (empty($selectedSection) || (!empty($selectedSection) && !array_key_exists($selectedSection, $sectionOptions))) {
				$selectedSection = key($sectionOptions);
			}
		}
		
		$list = $this->InstitutionSiteSection->InstitutionSiteSectionStudent->getStudentsBySection($selectedSection);
		$studentOptions = array();
		foreach ($list as $obj) {
			$student = $obj['Student'];
			$studentOptions[$student['id']] = ModelHelper::getName($obj['SecurityUser'], array('openEmisId'=>true));
		}
		$this->fields['student_id']['type'] = 'select';
		$this->fields['student_id']['options'] = $studentOptions;
		$this->setFieldOrder('student_id', 3);
		
		$customJs = array('institution_attendance');
		$this->setVar('params', array('back' => 'absence', 'js'=> $customJs));
		$this->setVar(compact('academicPeriodOptions', 'selectedAcademicPeriod', 'sectionOptions', 'studentOptions', 'fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions', 'sectionId'));
	}

	public function dayview($academicPeriodId=0, $sectionId=0, $weekId=null, $dayId=null) {
		if (!is_null($dayId) && $dayId==0) {
			return $this->redirect(array('action' => get_class($this), 'index', $academicPeriodId, $sectionId, $weekId, 0));
		} 

		$this->Navigation->addCrumb('Attendance - Students');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
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
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $academicPeriodId);
		if(!empty($sectionOptions)){
			if(isset($sectionId)){
				if ($sectionId != 0) {
					if (!array_key_exists($sectionId, $sectionOptions)) {
						$sectionId = key($sectionOptions);
					}
				}
			}else{
				$sectionId = key($sectionOptions);
			}
		}
		$sectionOptions = $this->controller->Option->prependLabel($sectionOptions, 'InstitutionSiteStudentAbsence.select_section');
		
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
		
		$header = $this->controller->generateAttendanceDayHeader();
		$headerDates = $this->controller->generateAttendanceHeaderDates($startDate, $endDate);
		
		$absenceData = $this->getAbsenceData($institutionSiteId, $academicPeriodId, $sectionId, $selectedDate, $selectedDate);
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
		
		$InstitutionSiteSectionStudentModel = ClassRegistry::init('InstitutionSiteSectionStudent');
		$studentList = $InstitutionSiteSectionStudentModel->getSectionStudents($sectionId, $startDate, $endDate);
		if(empty($studentList)){
			$this->Message->alert('general.noData');
		}

		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}
		
		$this->setVar(compact('academicPeriodList', 'academicPeriodId', 'sectionOptions', 'sectionId', 'weekList', 'weekId', 'dayId', 'header', 'weekDayIndex', 'selectedDateDigit', 'selectedDate', 'weekDayList', 'studentList', 'absenceCheckList'));

	}

	public function dayedit($academicPeriodId=0, $sectionId=null, $weekId=null, $dayId=null) {
		if ($dayId==null||$dayId==0) {
			return $this->redirect(array('action' => get_class($this), 'index', $academicPeriodId, $sectionId, $weekId));
		}

		$this->Navigation->addCrumb('Attendance - Students');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
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
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $academicPeriodId);
		if(!empty($sectionOptions)){
			if(isset($sectionId)){
				if ($sectionId != 0) {
					if (!array_key_exists($sectionId, $sectionOptions)) {
						$sectionId = key($sectionOptions);
					}
				}
			}else{
				$sectionId = key($sectionOptions);
			}
		}
		$sectionOptions = $this->controller->Option->prependLabel($sectionOptions, 'InstitutionSiteStudentAbsence.select_section');
		
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
			$absenceOptions['sectionId'] = $sectionId;
			$absenceOptions['weekId'] = $weekId;
			$absenceOptions['selectedDate'] = $selectedDate;
			$absenceOptions['dayId'] = $dayId;
			$this->processAbsenceSaveInDayView($this->request->data, $absenceOptions);
		}
		
		$header = $this->controller->generateAttendanceDayHeader();
		$headerDates = $this->controller->generateAttendanceHeaderDates($startDate, $endDate);
		
		$absenceData = $this->getAbsenceData($institutionSiteId, $academicPeriodId, $sectionId, $selectedDate, $selectedDate);
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
		
		$InstitutionSiteSectionStudentModel = ClassRegistry::init('InstitutionSiteSectionStudent');
		
		$studentList = $InstitutionSiteSectionStudentModel->getSectionStudents($sectionId, $startDate, $endDate);
		if(empty($studentList)){
			$this->Message->alert('general.noData');
		}

		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}

		$absenceTypeList = $this->controller->getDayViewAttendanceOptions();
		$absenceReasonList = $this->StudentAbsenceReason->getList();

		$this->setVar(compact('academicPeriodList', 'academicPeriodId', 'sectionOptions', 'sectionId', 'weekList', 'weekId', 'dayId', 'header', 'weekDayIndex', 'selectedDateDigit', 'selectedDate', 'weekDayList', 'studentList', 'absenceCheckList','absenceTypeList', 'absenceReasonList'));

	}

	public function getAbsenceData($institutionSiteId, $academicPeriodId, $sectionId=0, $startDate='', $endDate='') {
		$conditions = array();
		
		// if $sectionId is not present, then $institutionSiteId and $academicPeriodId are necessary for data filter
		// update 10dec2014 - M discussed with U, if there are no classes (meaning combobox not selected for class - it returns no data - as it should)
		if (!empty($sectionId)) {
			$conditions[] = 'InstitutionSiteStudentAbsence.institution_site_section_id = ' . $sectionId;
		} else {
			return array();
		}
		
		$conditions['InstitutionSiteStudentAbsence.first_date_absent <='] = $endDate;
		$conditions['InstitutionSiteStudentAbsence.last_date_absent >='] = $startDate;
		
		/* Commented instead of delete due to the need to re-work the logic using AcademicPeriod date range.*/ 
		/* As of 26 Feb 2015, all statements that call this function are able to provide $startDate and $endDate*/ 
		// $AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		// $academicPeriod = $AcademicPeriod->getAcademicPeriodById($academicPeriodId);
		// $conditions[] = 'YEAR(InstitutionSiteStudentAbsence.first_date_absent) = "' . $academicPeriod . '"';

		$data = $this->find('all',
			array(
				'contain' => array(
					'Student' => array(
						'fields' => array('id'),
						'SecurityUser' => array('openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name')),
					'StudentAbsenceReason'
				),
				'conditions' => $conditions,
				'order' => array('InstitutionSiteStudentAbsence.first_date_absent', 'InstitutionSiteStudentAbsence.last_date_absent'),
			)
		);
		return $data;
	}

	public function processAbsenceSaveInDayView($data, $options=array()) {
		$processedData = array('InstitutionSiteStudentAbsence'=>array());
		foreach ($data['InstitutionSiteStudentAbsence'] as $key => $value) {
			$student_attendance_type = $value['student_attendance_type'];
			unset($value['student_attendance_type']);
			$conditions = array();
			$conditions['AND'] = array();
			$conditions['AND']['InstitutionSiteStudentAbsence.first_date_absent <='] = $options['selectedDate'];
			$conditions['AND']['InstitutionSiteStudentAbsence.last_date_absent >='] = $options['selectedDate'];
			$conditions['institution_site_section_id'] = $options['sectionId'];
			$conditions['student_id'] = $value['student_id'];

			$found = $this->find(
				'all',
				array(
					'fields' => array('InstitutionSiteStudentAbsence.*'),
					'conditions' => $conditions
				)
			);

			if ($student_attendance_type!='0') {
				// Marking Absent
				switch ($student_attendance_type) {
					case '1': $currentAbsenceType = 'Excused'; break;
					case '2': $currentAbsenceType = 'Unexcused'; break;
				}
				if (!empty($found)) {
					foreach ($found as $foundKey => $foundValue) {
						$foundValue['InstitutionSiteStudentAbsence']['absence_type'] = $currentAbsenceType;
						$foundValue['InstitutionSiteStudentAbsence']['student_absence_reason_id'] = $value['student_absence_reason_id'];
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
					$value['institution_site_section_id'] = $options['sectionId'];
					$this->create();
					$this->save($value);
					$institutionSiteId = $this->Session->read('InstitutionSite.id');
					$studentId = $value['student_id'];
					$currentAcademicPeriodId = ClassRegistry::init('AcademicPeriod')->getCurrent();
					$this->controller->Alert->trigger(array('Attendance', $studentId, $currentAcademicPeriodId, $institutionSiteId));
				}
			} else {
				// Marking present
				if (!empty($found)) {
					foreach ($found as $key => $value) {
						$currAbsence = $found[$key]['InstitutionSiteStudentAbsence'];
						if (date('Ymd', strtotime($currAbsence['first_date_absent'])) == date('Ymd', strtotime($options['selectedDate']))) {
							$this->delete($currAbsence['id']);
						} else if (strtotime($currAbsence['first_date_absent']) < strtotime($options['selectedDate'])) {
							$currAbsence['last_date_absent'] = date('d-m-Y', strtotime('-1 day', strtotime($options['selectedDate'])));
							$currAbsence['end_time_absent'] = '11:59 PM';
							$this->save(array('InstitutionSiteStudentAbsence'=>$currAbsence));
						}
					}
				} 
			}
			
		}

		$this->Message->alert('general.add.success');
		return $this->redirect(array('action' => get_class($this), 'dayview', $options['academicPeriodId'], $options['sectionId'], $options['weekId'], $options['dayId']));
	}
	
	public function getStudentAbsenceDataByMonth($studentId, $academicPeriodId, $monthId){
		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		$academicPeriod = $AcademicPeriod->getAcademicPeriodById($academicPeriodId);
		
		$conditions = array(
			'Student.id = ' . $studentId,
		);
		
		$conditions['OR'] = array(
			array(
				'MONTH(InstitutionSiteStudentAbsence.first_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStudentAbsence.first_date_absent) = "' . $academicPeriod . '"'
			),
			array(
				'MONTH(InstitutionSiteStudentAbsence.last_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStudentAbsence.last_date_absent) = "' . $academicPeriod . '"'
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
			__('Academic Period'),
			
			__('Section'),
			
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
			$this->unbindModel(array('belongsTo' => array('InstitutionSiteSection', 'ModifiedUser', 'CreatedUser')));
			
			$options = array();
			//$options['recursive'] = -1;
			$options['fields'] = array(
				'AcademicPeriod.name', 
				
				'InstitutionSiteSection.name', 
				
				'InstitutionSiteStudentAbsence.first_date_absent', 
				'InstitutionSiteStudentAbsence.last_date_absent', 
				'InstitutionSiteStudentAbsence.full_day_absent', 
				'InstitutionSiteStudentAbsence.start_time_absent', 
				'InstitutionSiteStudentAbsence.end_time_absent', 
				
				'SecurityUser.openemis_no',
				'SecurityUser.first_name',
				'SecurityUser.middle_name',
				'SecurityUser.third_name',
				'SecurityUser.last_name',
				'Student.preferred_name',
				
				'InstitutionSiteStudentAbsence.absence_type', 
				'StudentAbsenceReason.name',
				'InstitutionSiteStudentAbsence.comment'
			);

			$options['order'] = array('AcademicPeriod.name', 'InstitutionSiteStudentAbsence.first_date_absent', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name');
			//$options['conditions'] = array('InstitutionSiteSection.institution_site_id' => $institutionSiteId);
			
			$options['joins'] = array(
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array(
						'InstitutionSiteStudentAbsence.institution_site_section_id = InstitutionSiteSection.id',
						'InstitutionSiteSection.institution_site_id = ' . $institutionSiteId
					)
				),
				array(
					'table' => 'academic_periods',
					'alias' => 'AcademicPeriod',
					'type' => 'LEFT',
					'conditions' => array('InstitutionSiteSection.academic_period_id = AcademicPeriod.id')
				)
			);
			
			

			$data = $this->find('all', $options);
			
			$newData = array();
			foreach($data AS $row){
				$tempRow = array();
				
				$academicPeriod = $row['AcademicPeriod'];
				$section = $row['InstitutionSiteSection'];
				$absence = $row['InstitutionSiteStudentAbsence'];
				$student = $row['Student'];
				$reason = $row['StudentAbsenceReason'];
				
				$tempRow[] = $academicPeriod['name'];
				$tempRow[] = $section['name'];
				
				$tempRow[] = $this->formatDateByConfig($absence['first_date_absent']);
				$tempRow[] = $this->formatDateByConfig($absence['last_date_absent']);
				$tempRow[] = $absence['full_day_absent'];
				$tempRow[] = $absence['start_time_absent'];
				$tempRow[] = $absence['end_time_absent'];
				
				$tempRow[] = $student['openemis_no'];
				$tempRow[] = $student['first_name'];
				$tempRow[] = $student['middle_name'];
				$tempRow[] = $student['third_name'];
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

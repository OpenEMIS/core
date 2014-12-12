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
		'TimePicker' => array('start_time_absent' => array('format' => 'h:i a'), 'end_time_absent' => array('format' => 'h:i a')),
		'ControllerAction2',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $belongsTo = array(
		'Students.Student',
		//'InstitutionSiteClass',
		'InstitutionSiteSection',
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
	
	public function beforeAction() {
		parent::beforeAction();
		$this->controller->FileUploader->fileVar = 'files';
		$this->controller->FileUploader->fileModel = 'InstitutionSiteStudentAbsenceAttachment';
		$this->controller->FileUploader->allowEmptyUpload = true;
		$this->controller->FileUploader->additionalFileType();
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearOptions = $this->InstitutionSiteSection->getYearOptions(array('InstitutionSiteSection.institution_site_id' => $institutionSiteId));
		
		if ($this->action == 'add') {
			$this->fields['school_year_id'] = array(
				'type' => 'select',
				'options' => $yearOptions,
				'visible' => true,
				'order' => 1,
				'attr' => array('onchange' => "$('#reload').click()")
			);
			$this->setFieldOrder('school_year_id', 1);
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
		
		/*
		$this->fields['files'] = array(
			'type' => 'element',
			'element' => '../InstitutionSites/InstitutionSiteStudentAbsence/files',
			'visible' => true
		);
		*/
	}
	
	public function afterAction() {
		if ($this->action == 'add' || $this->action == 'edit') {
			$todayDate = date('Y-m-d');
			$todayDateFormatted = date('d-m-Y');
			
			$yearId = $this->action == 'add' ? $this->controller->viewVars['selectedYear'] : $this->request->data['InstitutionSiteSection']['school_year_id'];
			
			$yearObj = ClassRegistry::init('SchoolYear')->findById($yearId);
			$startDate = $yearObj['SchoolYear']['start_date'];
			$endDate = $yearObj['SchoolYear']['end_date'];
			
			if($todayDate >= $startDate && $todayDate <= $endDate){
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
			$data[$this->alias]['student_id'] = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
			$data[$this->alias]['institution_site_section_id'] = $this->InstitutionSiteSection->field('InstitutionSiteSection.name', array('InstitutionSiteSection.id' => $sectionId));
			$this->controller->viewVars['data'] = $data;
			$this->setFieldOrder('institution_site_section_id', 0);
			$this->setFieldOrder('student_id', 1);
			$this->setVar('params', array('back' => $this->Session->read('InstitutionSiteStudentAbsence.backLink')));
		} else if ($this->action == 'edit') {
			$data = $this->request->data;
			$sectionId = $data[$this->alias]['institution_site_section_id'];
			$this->fields['section']['type'] = 'disabled';
			$this->fields['section']['value'] = $this->InstitutionSiteSection->field('InstitutionSiteSection.name', array('InstitutionSiteSection.id' => $sectionId));
			$this->fields['section']['order'] = 0;
			$this->fields['section']['visible'] = true;
			$this->setFieldOrder('section', 0);
			$this->fields['student_id']['type'] = 'hidden';
			$this->fields['student']['type'] = 'disabled';
			$this->fields['student']['value'] = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
			$this->fields['student']['order'] = 1;
			$this->fields['student']['visible'] = true;
			$this->fields['institution_site_section_id']['type'] = 'hidden';
			$this->request->data = $data;
		}
		$this->setFieldOrder('full_day_absent', 2);
		parent::afterAction();
	}
	
	public function index($yearId=0, $sectionId=0, $weekId=null, $dayId=null) {
		if ($dayId!=null || $dayId!=0) {
			return $this->redirect(array('action' => get_class($this), 'dayview', $yearId, $sectionId, $weekId, $dayId));
		}

		$this->Navigation->addCrumb('Attendance - Students');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
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
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $yearId);
		if(!empty($sectionOptions)){
			if ($sectionId != 0) {
				if (!array_key_exists($sectionId, $sectionOptions)) {
					$sectionId = key($sectionOptions);
				}
			} else {
				$sectionId = key($sectionOptions);
			}
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
		$headerDates = $this->controller->generateAttendanceHeaderDates($startDate, $endDate);
		$weekDayIndex = $this->controller->generateAttendanceWeekDayIndex($startDate, $endDate);
		
		$absenceData = $this->getAbsenceData($institutionSiteId, $yearId, $sectionId, $startDate, $endDate);
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

		$studentList = $InstitutionSiteSectionStudentModel->getSectionSutdents($sectionId, $startDate, $endDate);
		if(empty($studentList)){
			$this->Message->alert('general.noData');
		}
		
		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}

		$this->Session->write('InstitutionSiteStudentAbsence.backLink', array('index',$yearId,$sectionId,$weekId));
		$this->setVar(compact('yearList', 'yearId', 'sectionOptions', 'sectionId', 'weekList', 'weekId', 'header', 'weekDayIndex', 'weekDayList', 'studentList', 'absenceCheckList'));
	}
	
	public function absence($yearId=0, $sectionId=0, $weekId=null, $dayId=null) {
		$this->Navigation->addCrumb('Absence - Students');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
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
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $yearId);
		if(!empty($sectionOptions)){
			if ($sectionId != 0) {
				if (!array_key_exists($sectionId, $sectionOptions)) {
					$sectionId = key($sectionOptions);
				}
			} else {
				$sectionId = key($sectionOptions);
			}
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

		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		$startEndDates = $this->controller->getStartEndDateByYearWeek($yearId, $weekId);
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

		$data = $this->getAbsenceData($institutionSiteId, $yearId, $sectionId, $startDate, $endDate);
		if(empty($data)){
			$this->Message->alert('general.noData');
		}
		$this->Session->write('InstitutionSiteStudentAbsence.backLink', array('absence',$yearId,$sectionId,$weekId));
		$this->setVar(compact('yearList', 'yearId', 'dayId', 'sectionOptions', 'sectionId', 'weekDayList', 'weekList', 'weekId', 'data'));
	}
	
	public function add() {
		$this->render = 'auto';
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$InstitutionSiteSectionStudentModel = ClassRegistry::init('InstitutionSiteSectionStudent');
		
		$yearOptions = $this->fields['school_year_id']['options'];
		$selectedYear = 0;
		$selectedSection = 0;
		if (!empty($yearOptions)) {
			if (empty($selectedYear) || (!empty($selectedYear) && !array_key_exists($selectedYear, $yearOptions))) {
				$selectedYear = key($yearOptions);
			}
		}
		
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$selectedYear = $data[$this->alias]['school_year_id'];
			$selectedSection = $data[$this->alias]['institution_site_section_id'];
			
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
		}
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $selectedYear);
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
			$studentOptions[$student['id']] = sprintf('%s - %s %s', $student['identification_no'], $student['first_name'], $student['last_name']);
		}
		$this->fields['student_id']['type'] = 'select';
		$this->fields['student_id']['options'] = $studentOptions;
		$this->setFieldOrder('student_id', 3);
		
		//$this->setVar(compact());
		/*
		if ($this->request->is('get')) {
			$this->Navigation->addCrumb('Absence - Students');
			
			$settingStartTime = $this->controller->ConfigItem->getValue('start_time');
			$obj = array(
				'InstitutionSiteStudentAbsence' => array(
					'start_time_absent' => $settingStartTime
				)
			);
			$this->request->data = $obj;
		} else {
			//$this->create();
			pr($this->request->data);die;
			$absenceData = $this->request->data['InstitutionSiteStudentAbsence'];
			$absenceData['student_id'] = $absenceData['hidden_student_id'];
			unset($absenceData['hidden_student_id']);
			
			$firstDateAbsent = $absenceData['first_date_absent'];
			$classIdInput = $absenceData['institution_site_class_id'];
			$firstDateAbsentData = new DateTime($firstDateAbsent);
			$firstDateYear = $firstDateAbsentData->format('Y');
			$firstDateYearId = ClassRegistry::init('SchoolYear')->getSchoolYearId($firstDateYear); // error here
			$classExists = $this->InstitutionSiteClass->getClassByIdSchoolYear($classIdInput, $firstDateYearId);
			
			if($absenceData['full_day_absent'] == 'Yes'){
				$absenceData['start_time_absent'] = '';
				$absenceData['end_time_absent'] = '';
			}else{
				$absenceData['last_date_absent'] = null;
			}

			$this->set($absenceData);
			if ($this->validates()) {
				if($InstitutionSiteClassStudentModel->isStudentInClass($institutionSiteId, $absenceData['institution_site_class_id'], $absenceData['student_id'])){
					if($classExists){
						if($this->save($absenceData)){
							$newId = $this->getInsertID();
							//pr($newId);
							$postFileData = $this->request->data[$this->alias]['files'];
							$this->controller->FileUploader->additionData = array('institution_site_student_absence_id' => $newId);
							$this->controller->FileUploader->uploadFile(NULL, $postFileData);

							if($this->controller->FileUploader->success){
								$this->Message->alert('general.add.success');
								return $this->redirect(array('action' => get_class($this), 'absence'));
							}
						}
					}else{
						$this->Message->alert('institutionSiteAttendance.student.failed.class_first_date_not_match');
					}
					
				}else{
					$this->Message->alert('institutionSiteAttendance.student.failed.class_student_not_match');
				}
			}
		}
		$fullDayAbsentOptions = array('Yes' => __('Yes'), 'No' => __('No'));
		$absenceReasonOptions =  $this->StudentAbsenceReason->getList();
		$absenceTypeOptions = array('Excused' => __('Excused'), 'Unexcused' => __('Unexcused'));
		*/
		
		$this->setVar('params', array('back' => 'absence'));
		$this->setVar(compact('yearOptions', 'selectedYear', 'sectionOptions', 'studentOptions', 'fullDayAbsentOptions', 'absenceReasonOptions', 'absenceTypeOptions', 'sectionId'));
	}

	public function dayview($yearId=0, $sectionId=0, $weekId=null, $dayId=null) {
		if ($dayId==null||$dayId==0) {
			return $this->redirect(array('action' => get_class($this), 'index', $yearId, $sectionId, $weekId));
		} 

		$this->Navigation->addCrumb('Attendance - Students');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
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
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $yearId);
		if(!empty($sectionOptions)){
			if ($sectionId != 0) {
				if (!array_key_exists($sectionId, $sectionOptions)) {
					$sectionId = key($sectionOptions);
				}
			} else {
				$sectionId = key($sectionOptions);
			}
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
		
		$header = $this->controller->generateAttendanceDayHeader();
		$headerDates = $this->controller->generateAttendanceHeaderDates($startDate, $endDate);
		
		$absenceData = $this->getAbsenceData($institutionSiteId, $yearId, $sectionId, $selectedDate, $selectedDate);
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
		
		$studentList = $InstitutionSiteSectionStudentModel->getSectionSutdents($sectionId, $startDate, $endDate);
		if(empty($studentList)){
			$this->Message->alert('general.noData');
		}

		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}
		
		$this->setVar(compact('yearList', 'yearId', 'sectionOptions', 'sectionId', 'weekList', 'weekId', 'dayId', 'header', 'weekDayIndex', 'selectedDateDigit', 'selectedDate', 'weekDayList', 'studentList', 'absenceCheckList'));

	}

	public function dayedit($yearId=0, $sectionId=0, $weekId=null, $dayId=null) {
		if ($dayId==null||$dayId==0) {
			return $this->redirect(array('action' => get_class($this), 'index', $yearId, $sectionId, $weekId));
		}

		$this->Navigation->addCrumb('Attendance - Students');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
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
		
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $yearId);
		if(!empty($sectionOptions)){
			if ($sectionId != 0) {
				if (!array_key_exists($sectionId, $sectionOptions)) {
					$sectionId = key($sectionOptions);
				}
			} else {
				$sectionId = key($sectionOptions);
			}
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
			$absenceOptions['yearId'] = $yearId;
			$absenceOptions['sectionId'] = $sectionId;
			$absenceOptions['weekId'] = $weekId;
			$absenceOptions['selectedDate'] = $selectedDate;
			$absenceOptions['dayId'] = $dayId;
			$this->processAbsenceSaveInDayView($this->request->data, $absenceOptions);
		}
		
		$header = $this->controller->generateAttendanceDayHeader();
		$headerDates = $this->controller->generateAttendanceHeaderDates($startDate, $endDate);
		
		$absenceData = $this->getAbsenceData($institutionSiteId, $yearId, $sectionId, $selectedDate, $selectedDate);
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
		
		$studentList = $InstitutionSiteSectionStudentModel->getSectionSutdents($sectionId, $startDate, $endDate);
		if(empty($studentList)){
			$this->Message->alert('general.noData');
		}

		$weekDayList = array();
		array_push($weekDayList, __('All Days'));
		foreach ($weekDayIndex as $key => $value) {
			array_push($weekDayList, "(".$headerDates[$key].") ".substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2));
		}

		$absenceTypeList = $this->controller->getDayViewAttendanceOptions();
		$absenceReasonList = $this->StudentAbsenceReason->getOptionList(array('FieldOption.code' => 'StudentAbsenceReason'));

		$this->setVar(compact('yearList', 'yearId', 'sectionOptions', 'sectionId', 'weekList', 'weekId', 'dayId', 'header', 'weekDayIndex', 'selectedDateDigit', 'selectedDate', 'weekDayList', 'studentList', 'absenceCheckList','absenceTypeList', 'absenceReasonList'));

	}

	public function getAbsenceData($institutionSiteId, $schoolYearId, $sectionId=0, $startDate='', $endDate='') {
		$conditions = array();
		
		// if $sectionId is not present, then $institutionSiteId and $schoolYearId are necessary for data filter
		// update 10dec2014 - M discussed with U, if there are no classes (meaning combobox not selected for class - it returns no data - as it should)
		if (!empty($sectionId)) {
			$conditions[] = 'InstitutionSiteStudentAbsence.institution_site_section_id = ' . $sectionId;
		} else {
			return array();
		}
		
		$conditions['InstitutionSiteStudentAbsence.first_date_absent <='] = $endDate;
		$conditions['InstitutionSiteStudentAbsence.last_date_absent >='] = $startDate;
		/*
		if(!empty($startDate) && !empty($endDate)){
			$conditions['OR'] = array(
					array(
						'InstitutionSiteStudentAbsence.first_date_absent >= ' => $startDate,
						'InstitutionSiteStudentAbsence.first_date_absent <= ' => $endDate
					),
					array(
						//'InstitutionSiteStudentAbsence.last_date_absent >= ' => $startDate,
						//'InstitutionSiteStudentAbsence.last_date_absent <= ' => $endDate
					)
			);
		}
		*/
		
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$schoolYear = $SchoolYear->getSchoolYearById($schoolYearId);
		//$conditions[] = 'YEAR(InstitutionSiteStudentAbsence.first_date_absent) = "' . $schoolYear . '"';

		$data = $this->find('all',
			array(
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
					'StudentAbsenceReason.id',
					'StudentAbsenceReason.name'
				),
				'conditions' => $conditions,
				'order' => array('InstitutionSiteStudentAbsence.first_date_absent', 'InstitutionSiteStudentAbsence.last_date_absent'),
				// 'group' => array('InstitutionSiteStudentAbsence.student_id')
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
		return $this->redirect(array('action' => get_class($this), 'dayview', $options['yearId'], $options['sectionId'], $options['weekId'], $options['dayId']));
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
	
	/*
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
		
		if (isset($controller->params['pass'][0])) {
			$yearId = $controller->params['pass'][0];
			if (!array_key_exists($yearId, $yearList)) {
				$yearId = key($yearList);
			}
		}else{
			$yearId = key($yearList);
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
		
		if (isset($controller->params['pass'][0])) {
			$yearId = $controller->params['pass'][0];
			if (!array_key_exists($yearId, $yearList)) {
				$yearId = key($yearList);
			}
		}else{
			$yearId = key($yearList);
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
	
	public function attendanceStudentAbsenceAdd($controller, $params) {
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
	*/
	
	/*
	public function beforeAction($controller, $action) {
		$controller->set('model', $this->alias);
		$controller->FileUploader->fileVar = 'files';
		$controller->FileUploader->fileModel = 'InstitutionSiteStudentAbsenceAttachment';
		$controller->FileUploader->allowEmptyUpload = true;
		$controller->FileUploader->additionalFileType();
	}
	*/
	
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
				'SchoolYear.name', 
				
				'InstitutionSiteSection.name', 
				
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
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'type' => 'LEFT',
					'conditions' => array('InstitutionSiteSection.school_year_id = SchoolYear.id')
				)
			);
			
			

			$data = $this->find('all', $options);
			
			$newData = array();
			foreach($data AS $row){
				$tempRow = array();
				
				$schoolYear = $row['SchoolYear'];
				$section = $row['InstitutionSiteSection'];
				$absence = $row['InstitutionSiteStudentAbsence'];
				$student = $row['Student'];
				$reason = $row['StudentAbsenceReason'];
				
				$tempRow[] = $schoolYear['name'];
				$tempRow[] = $section['name'];
				
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
	
	public function getStudentListForAlert($threshold) {
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$currentSchoolYear = $SchoolYear->getCurrentSchoolYear();
		$currentYearId = $currentSchoolYear['id'];

		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('Student.id', 'COUNT(Student.id) AS total'),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array(
						'InstitutionSiteStudentAbsence.student_id = Student.id'
					)
				),
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteStudentAbsence.institution_site_class_id = InstitutionSiteClass.id'
					)
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'type' => 'LEFT',
					'conditions' => array(
						'InstitutionSiteClass.school_year_id = SchoolYear.id',
						'SchoolYear.id = ' . $currentYearId
					)
				)
			),
			'group' => array('Student.id HAVING total >= ' . $threshold)
		));
		
		$data = array();
		foreach($list AS $row){
			$studentId = $row['Student']['id'];
			$data[] = $studentId;
		}

		return $data;
	}
	
}

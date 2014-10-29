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

App::uses('Sanitize', 'Utility');

class StudentsController extends StudentsAppController {
	public $name = 'Students';
	public $studentId;
	public $uses = array(
		'Area',
		'InstitutionSite',
		'InstitutionSiteClass',
		'InstitutionSiteType',
		'InstitutionSiteStudentAbsence',
		'InstitutionSiteClassStudent',
		'Students.Student',
		'Students.StudentHistory',
		'Students.StudentCustomField',
		'Students.StudentCustomFieldOption',
		'Students.StudentCustomValue',
		'Students.StudentAssessment',
		'Students.StudentAttendanceType',
		'SchoolYear',
		'ConfigItem'
	);
	
	public $helpers = array('Js' => array('Jquery'), 'Paginator');
	public $components = array(
		'Paginator',
		'FileAttachment' => array(
			'model' => 'Students.StudentAttachment',
			'foreignKey' => 'student_id'
		),
		'FileUploader',
		'AccessControl',
		'Wizard'
	);
	
	public $modules = array(
		// old ControllerAction
		'healthHistory' => 'Students.StudentHealthHistory',
		'healthFamily' => 'Students.StudentHealthFamily',
		'healthImmunization' => 'Students.StudentHealthImmunization',
		'healthMedication' => 'Students.StudentHealthMedication',
		'healthAllergy' => 'Students.StudentHealthAllergy',
		'healthTest' => 'Students.StudentHealthTest',
		'healthConsultation' => 'Students.StudentHealthConsultation',
		'health' => 'Students.StudentHealth',
		'specialNeed' => 'Students.StudentSpecialNeed',
		'award' => 'Students.StudentAward',
		'bankAccounts' => 'Students.StudentBankAccount',
		'comments' => 'Students.StudentComment',
		'contacts' => 'Students.StudentContact',
		'extracurricular' => 'Students.StudentExtracurricular',
		'identities' => 'Students.StudentIdentity',
		'languages' => 'Students.StudentLanguage',
		'nationalities' => 'Students.StudentNationality',
		'attachments' =>'Students.StudentAttachment',
		'guardians' => 'Students.StudentGuardian',
		'behaviour' => 'Students.StudentBehaviour',
		'additional' => 'Students.StudentCustomField',
		// new ControllerAction
		'InstitutionSiteStudent',
		'Programme' => array('plugin' => 'Students'),
		'StudentFee' => array('plugin' => 'Students')
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Students', array('controller' => $this->name, 'action' => 'index'));
		$this->Wizard->setModule('Student');
		
		$actions = array('index', 'advanced');
		if (in_array($this->action, $actions)) {
			$this->bodyTitle = __('Students');
			//$this->Session->delete('Student');
		} else if ($this->Wizard->isActive()) {
			$this->bodyTitle = __('New Student');
		} else if ($this->Session->check('Student.data.name')) {
			$name = $this->Session->read('Student.data.name');
			$this->studentId = $this->Session->read('Student.id'); // for backward compatibility
			if ($this->action != 'view' && $this->action != 'InstitutionSiteStudent') {
				$this->Navigation->addCrumb($name, array('controller' => $this->name, 'action' => 'view'));
			}
			$this->bodyTitle = $name;
		}
	}

	public function index() {
		// redirect to InstitutionSiteStudent index page if institution is selected
		if ($this->Session->check('InstitutionSite.id')) {
			return $this->redirect(array('action' => 'InstitutionSiteStudent'));
		}
		// end redirect
		
		$this->Navigation->addCrumb('List of Students');
		//$this->Session->delete('Student');
		
		if ($this->request->is('post')) {
			if (isset($this->request->data['Student']['SearchField'])) {
				$this->request->data['Student']['SearchField'] = Sanitize::escape($this->request->data['Student']['SearchField']);
				if ($this->request->data['Student']['SearchField'] != $this->Session->read('Search.SearchFieldStudent')) {
					$this->Session->delete('Search.SearchFieldStudent');
					$this->Session->write('Search.SearchFieldStudent', $this->request->data['Student']['SearchField']);
				}
			}

			if (isset($this->request->data['sortdir']) && isset($this->request->data['order'])) {
				if ($this->request->data['sortdir'] != $this->Session->read('Search.sortdirStudent')) {
					$this->Session->delete('Search.sortdirStudent');
					$this->Session->write('Search.sortdirStudent', $this->request->data['sortdir']);
				}
				if ($this->request->data['order'] != $this->Session->read('Search.orderStudent')) {
					$this->Session->delete('Search.orderStudent');
					$this->Session->write('Search.orderStudent', $this->request->data['order']);
				}
			}
		}

		$fieldordername = ($this->Session->read('Search.orderStudent')) ? $this->Session->read('Search.orderStudent') : 'Student.first_name';
		$fieldorderdir = ($this->Session->read('Search.sortdirStudent')) ? $this->Session->read('Search.sortdirStudent') : 'asc';

		$searchKey = stripslashes($this->Session->read('Search.SearchFieldStudent'));
		$conditions = array(
			'SearchKey' => $searchKey,
			'AdvancedSearch' => $this->Session->check('Student.AdvancedSearch') ? $this->Session->read('Student.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id')
		);
		$order = array('order' => array($fieldordername => $fieldorderdir));
		$limit = ($this->Session->read('Search.perpageStudent')) ? $this->Session->read('Search.perpageStudent') : 30;
		$this->Paginator->settings = array_merge(array('limit' => $limit, 'maxLimit' => 100), $order);

		$data = $this->paginate('Student', $conditions);
		if (empty($searchKey) && !$this->Session->check('Student.AdvancedSearch')) {
			if (count($data) == 1 && !$this->AccessControl->newCheck($this->params['controller'], 'add')) {
				$this->redirect(array('action' => 'viewStudent', $data[0]['Student']['id']));
			}
		}
		if (empty($data) && !$this->request->is('ajax')) {
			$this->Message->alert('general.noData');
		}
		$this->set('students', $data);
		$this->set('sortedcol', $fieldordername);
		$this->set('sorteddir', ($fieldorderdir == 'asc') ? 'up' : 'down');
		$this->set('searchField', $searchKey);
		if ($this->request->is('post')) {
			$this->render('index_records', 'ajax');
		}
	}

	public function advanced() {
		$key = 'Student.AdvancedSearch';
		$this->set('header', __('Advanced Search'));

		$IdentityType = ClassRegistry::init('IdentityType');
		$identityTypeOptions = $IdentityType->findList();

		if ($this->request->is('get')) {
			if ($this->request->is('ajax')) {
				$this->autoRender = false;
				$search = $this->params->query['term'];
				$result = $this->Area->autocomplete($search);
				return json_encode($result);
			} else {
				$this->Navigation->addCrumb('Advanced Search');

				if (isset($this->params->pass[0])) {
					if (intval($this->params->pass[0]) === 0) {
						$this->Session->delete($key);
						$this->redirect(array('action' => 'index'));
					}
				}
			}
		} else {
			$search = $this->request->data;

			if (!empty($search)) {
				$this->Session->write($key, $search);
			}

			$this->redirect(array('action' => 'index'));
		}
		$this->set(compact('identityTypeOptions'));
	}
		
	public function getCustomFieldsSearch($sitetype = 0,$customfields = 'Student') {
		$this->layout = false;
		$arrSettings = array(
			'CustomField'=>$customfields.'CustomField',
			'CustomFieldOption'=>$customfields.'CustomFieldOption',
			'CustomValue'=>$customfields.'CustomValue',
			'Year' => ''
		);
		if ($this->{$customfields}->hasField('institution_site_type_id')) {
			$arrSettings = array_merge(array('institutionSiteTypeId'=>$sitetype),$arrSettings);
		}
		$arrCustFields = array($customfields => $arrSettings);
		
		$instituionSiteCustField = $this->Components->load('CustomField',$arrCustFields[$customfields]);
		$dataFields[$customfields] = $instituionSiteCustField->getCustomFields();
		$types = $this->InstitutionSiteType->findList(1);
		$this->set("customfields",array($customfields));
		$this->set('types',  $types);	
		$this->set('typeSelected',  $sitetype);
		$this->set('dataFields',  $dataFields);
		$this->render('/Elements/customfields/search');
	}

	public function view($id=0) {
		if ($id == 0 && $this->Wizard->isActive()) {
			return $this->redirect(array('action' => 'add'));
		}
		
		if ($id > 0) {
			if ($this->Student->exists($id)) {
				$this->DateTime->getConfigDateFormat();
				$this->Session->write('Student.id', $id);
				$this->Wizard->unsetModule('Student');
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => 'index'));
			}
		} else {
			if ($this->Session->check('Student.id')) {
				$id = $this->Session->read('Student.id');
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => 'index'));
			}
		}
		$data = $this->Student->findById($id);
		$obj = $data['Student'];
		$name = trim($obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']);
		$obj['name'] = $name;
		$this->bodyTitle = $name;
		$this->Session->write('Student.data', $obj);
		$this->Navigation->addCrumb($name, array('controller' => $this->name, 'action' => 'view'));
		$this->Navigation->addCrumb('Overview');
		$this->set('data', $data);
	}
	
	public function add() {
		$this->Wizard->start();
		return $this->redirect(array('action' => 'edit'));
	}
	
	public function edit() {
		$model = 'Student';
		$id = null;
		$addressAreaId = false;
		$birthplaceAreaId = false;
		if ($this->Session->check($model . '.id')) {
			$id = $this->Session->read($model . '.id');
			$this->Student->id = $id;
			$this->Navigation->addCrumb('Edit');
		} else {
			$this->Navigation->addCrumb('Add');
			$this->bodyTitle = __('New Student');
		}
		$data = array();
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$this->Student->set($data);
			$addressAreaId = $this->request->data[$model]['address_area_id'];
			$birthplaceAreaId = $this->request->data[$model]['birthplace_area_id'];
			
			if ($this->Student->validates() && $this->Student->save()) {
				if ($this->Wizard->isActive()) {
					if (is_null($id)) {
						$this->Message->alert($model . '.add.success');
						$id = $this->Student->getLastInsertId();
						$this->Session->write($model . '.id', $id);
					}
					$this->Session->write($model . '.data', $this->Student->findById($id));
					// unset wizard so it will not auto redirect from WizardComponent
					unset($this->request->data['wizard']['next']);
					$this->Wizard->next();
				} else {
					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => 'view'));
				}
			}
		} else {
			if (!empty($id)) {
				$data = $this->Student->findById($id);
				$this->request->data = $data;
				$addressAreaId = $this->request->data[$model]['address_area_id'];
				$birthplaceAreaId = $this->request->data[$model]['birthplace_area_id'];
			}
		}
		$genderOptions = $this->Option->get('gender');
		$this->set('autoid', $this->getUniqueID());
		$this->set('genderOptions', $genderOptions);
		$this->set('data', $data);
		$this->set('model', $model);
		$this->set('addressAreaId', $addressAreaId);
		$this->set('birthplaceAreaId', $birthplaceAreaId);
	}

	public function classes() {
		$this->Navigation->addCrumb(ucfirst($this->action));
		$header = __(ucfirst($this->action));
		$studentId = $this->Session->read('Student.id');
		$data = array();
		$classes = $this->InstitutionSiteClassStudent->getListOfClassByStudent($studentId);

		foreach ($classes as $row) {
			$key = $row['InstitutionSite']['name'];
			$data[$key][] = $row;
		}
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->set(compact('data', 'header'));
	}

	public function delete() {
		$id = $this->Session->read('Student.id');
		$this->Student->delete($id);
		$this->Message->alert('general.delete.success');
		$this->redirect(array('action' => 'index'));
	}

	public function history() {
		$this->Navigation->addCrumb('History');

		$arrTables = array('StudentHistory');
		$studentId = $this->Session->read('Student.id');
		$historyData = $this->StudentHistory->find('all', array(
			'conditions' => array('StudentHistory.student_id' => $studentId),
			'order' => array('StudentHistory.created' => 'desc')
		));

		// pr($historyData);
		$data = $this->Student->findById($studentId);
		$data2 = array();
		foreach ($historyData as $key => $arrVal) {
			foreach ($arrTables as $table) {
				//pr($arrVal);die;
				foreach ($arrVal[$table] as $k => $v) {
					$keyVal = ($k == 'name') ? $table . '_name' : $k;
					//echo $k.'<br>';
					$data2[$keyVal][$v] = $arrVal['StudentHistory']['created'];
				}
			}
		}
		if (empty($data2)) {
			$this->Utility->alert($this->Utility->getMessage('NO_HISTORY'), array('type' => 'info', 'dismissOnClick' => false));
		}

		$this->set('data', $data);
		$this->set('data2', $data2);
	}

	public function assessments() {
		$this->Navigation->addCrumb('Results');
		$header = __('Results');
		if (!$this->Session->check('Student.id')) {
			return $this->redirect(array('action' => 'index'));
		}
		$studentId = $this->Session->read('Student.id');
		$years = $this->StudentAssessment->getYears($studentId);
		$programmeGrades = $this->StudentAssessment->getProgrammeGrades($studentId);

		reset($years);
		reset($programmeGrades);

		if ($this->request->isPost()) {
			$selectedYearId = $this->request->data['year'];
			if (!$this->Session->check('Student.assessment.year')) {
				$this->Session->write('Student.assessment.year', $selectedYearId);
			}
			$isYearChanged = $this->Session->read('Student.assessment.year') !== $this->request->data['year'];
			
			$programmeGrades = $this->StudentAssessment->getProgrammeGrades($studentId, $selectedYearId);
			$selectedProgrammeGrade = $isYearChanged ? key($programmeGrades) : $this->request->data['programmeGrade'];
		} else {
			$selectedYearId = key($years);
			$selectedProgrammeGrade = key($programmeGrades);
		}
		$data = $this->StudentAssessment->getData($studentId, $selectedYearId, $selectedProgrammeGrade);

		if (empty($data) && empty($years) && empty($programmeGrades)) {
			$this->Message->alert('general.noData');
		}

		$this->set('years', $years);
		$this->set('selectedYear', $selectedYearId);
		$this->set('programmeGrades', $programmeGrades);
		$this->set('selectedProgrammeGrade', $selectedProgrammeGrade);
		$this->set('data', $data);
		$this->set('header',$header);
	}

	private function custFieldYrInits() {
		$this->Navigation->addCrumb('Annual Info');
		$action = $this->action;
		$siteid = @$this->request->params['pass'][2];
		$id = $this->Session->read('Student.id');
		$schoolYear = ClassRegistry::init('SchoolYear');
		$years = $schoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('student_id' => $id, 'institution_site_id' => $siteid, 'school_year_id' => $selectedYear);

		$arrMap = array('CustomField' => 'StudentDetailsCustomField',
			'CustomFieldOption' => 'StudentDetailsCustomFieldOption',
			'CustomValue' => 'StudentDetailsCustomValue',
			'Year' => 'SchoolYear');
		return compact('action', 'siteid', 'id', 'years', 'selectedYear', 'condParam', 'arrMap');
	}

	private function custFieldSY($school_yr_ids) {
		return $this->InstitutionSite->find('list', array('conditions' => array('InstitutionSite.id' => $school_yr_ids)));
	}

	private function custFieldSites($institution_sites) {
		$institution_sites = $this->InstitutionSite->find('all', array('fields' => array('InstitutionSite.id', 'InstitutionSite.name'/*, 'Institution.name'*/), 'conditions' => array('InstitutionSite.id' => $institution_sites)));
		$tmp = array('0' => '--');
		foreach ($institution_sites as $arrVal) {
			$tmp[$arrVal['InstitutionSite']['id']] = /*$arrVal['Institution']['name'] . ' - ' . */$arrVal['InstitutionSite']['name'];
		}
		return $tmp;
	}

	public function custFieldYrView() {
		$this->Navigation->addCrumb("More", array('controller' => 'Students', 'action' => 'additional'));
		extract($this->custFieldYrInits());
		$customfield = $this->Components->load('CustomField', $arrMap);

		$data = array();
		if ($id && $selectedYear && $siteid)
			$data = $customfield->getCustomFieldView($condParam);

		$institution_sites = $customfield->getCustomValuebyCond('list', array('fields' => array('institution_site_id', 'school_year_id'), 'conditions' => array('school_year_id' => $selectedYear, 'student_id' => $id)));
		$institution_sites = $this->custFieldSites(array_keys($institution_sites));
		if (count($institution_sites) < 2)
			$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
		$displayEdit = false;
		$this->set(compact('arrMap', 'selectedYear', 'siteid', 'years', 'action', 'id', 'institution_sites', 'displayEdit'));
		$this->set($data);
		$this->set('myview', 'additional');
		$this->render('/Elements/customfields/view');
	}

	// STUDENT ATTENDANCE PART
	public function absence() {
		if (!$this->Session->check('Student.id')) {
			return $this->redirect(array('controller' => 'Students', 'action' => 'index'));
		}
		$studentId = $this->Session->read('Student.id');
		//$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
		$this->Navigation->addCrumb('Absence');
		$header = __('Absence');
		
		$yearList = $this->SchoolYear->getYearList();
		
		if (isset($this->params['pass'][0])) {
			$yearId = $this->params['pass'][0];
			if (!array_key_exists($yearId, $yearList)) {
				$yearId = key($yearList);
			}
		} else {
			$yearId = key($yearList);
		}
		
		$monthOptions = $this->generateMonthOptions();
		$currentMonthId = $this->getCurrentMonthId();
		if (isset($this->params['pass'][1])) {
			$monthId = $this->params['pass'][1];
			if (!array_key_exists($monthId, $monthOptions)) {
				$monthId = $currentMonthId;
			}
		} else {
			$monthId = $currentMonthId;
		}
		
		$absenceData = $this->InstitutionSiteStudentAbsence->getStudentAbsenceDataByMonth($studentId, $yearId, $monthId);
		$data = $absenceData;
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		
		$settingWeekdays = $this->getWeekdaysBySetting();

		$this->set(compact('header', 'data','yearList','yearId', 'monthOptions', 'monthId', 'settingWeekdays'));
	}

	private function getAvailableYearId($yearList) {
		$yearId = 0;
		if (isset($this->params['pass'][0])) {
			$yearId = $this->params['pass'][0];
			if (!array_key_exists($yearId, $yearList)) {
				$yearId = key($yearList);
			}
		} else {
			$yearId = key($yearList);
		}
		return $yearId;
	}

	public function getUniqueID() {
		$generate_no = '';
		$str = $this->Student->find('first', array('order' => array('Student.id DESC'), 'limit' => 1, 'fields' => 'Student.id'));
		$prefix = $this->ConfigItem->find('first', array('limit' => 1,
			'fields' => 'ConfigItem.value',
			'conditions' => array(
				'ConfigItem.name' => 'student_prefix'
			)
		));
		$prefix = explode(",", $prefix['ConfigItem']['value']);

		if ($prefix[1] > 0) {
			$id = $str['Student']['id'] + 1;
			if (strlen($id) < 6) {
				$str = str_pad($id, 6, "0", STR_PAD_LEFT);
			} else {
				$str = $id;
			}
			// Get two random number
			$rnd1 = rand(0, 9);
			$rnd2 = rand(0, 9);
			$generate_no = $prefix[0] . $str . $rnd1 . $rnd2;
		}

		return $generate_no;
	}
	
	public function generateMonthOptions() {
		$options = array();
		for ($i = 1; $i <= 12; $i++) {
			$options[$i] = date("F", mktime(0, 0, 0, $i+1, 0, 0, 0));
		}
		
		return $options;
	}
	
	public function getCurrentMonthId() {
		$options = $this->generateMonthOptions();
		$currentMonth = date("F");
		$monthId = 1;
		foreach($options AS $id => $month) {
			if ($currentMonth === $month) {
				$monthId = $id;
				break;
			}
		}
		
		return $monthId;
	}
	
	public function getWeekdaysBySetting() {
		$weekdaysArr = array(
			1 => 'monday',
			2 => 'tuesday',
			3 => 'wednesday',
			4 => 'thursday',
			5 => 'friday',
			6 => 'saturday',
			7 => 'sunday'
		);
		
		$settingFirstWeekDay = $this->ConfigItem->getValue('first_day_of_week');
		if (empty($settingFirstWeekDay) || !in_array($settingFirstWeekDay, $weekdaysArr)) {
			$settingFirstWeekDay = 'monday';
		}
		
		$settingDaysPerWek = intval($this->ConfigItem->getValue('days_per_week'));
		if (empty($settingDaysPerWek)) {
			$settingDaysPerWek = 5;
		}
		
		foreach($weekdaysArr AS $index => $weekday) {
			if ($weekday == $settingFirstWeekDay) {
				$firstWeekdayIndex = $index;
				break;
			}
		}
		
		$newIndex = $firstWeekdayIndex + $settingDaysPerWek;
		
		$weekdays = array();
		for($i=$firstWeekdayIndex; $i<$newIndex; $i++) {
			if ($i<=7) {
				$weekdays[] = $weekdaysArr[$i];
			} else {
				$weekdays[] = $weekdaysArr[$i%7];
			}
		}
		
		return $weekdays;
	}

}

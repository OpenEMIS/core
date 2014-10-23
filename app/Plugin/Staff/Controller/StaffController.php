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

class StaffController extends StaffAppController {
	public $name = 'Staff';
	public $staffId;
	public $staffObj;
	public $uses = array(
		'Area',
		'Institution',
		'InstitutionSite',
		'InstitutionSiteType',
		'InstitutionSiteStaff',
		'Staff.InstitutionSiteStaff',
		'Staff.Staff',
		'Staff.StaffHistory',
		'Staff.StaffCustomField',
		'Staff.StaffCustomFieldOption',
		'Staff.StaffCustomValue',
		'Staff.StaffAttendance',
		'SchoolYear',
		'ConfigItem',
		'SalaryAdditionType',
		'SalaryDeductionType',
		'TrainingCourse',
		'Staff.StaffAttendanceType',
		'InstitutionSiteStaffAbsence',
		'Staff.StaffTrainingSelfStudy',
	);
	public $helpers = array('Js' => array('Jquery'), 'Paginator');
	public $components = array(
		'Paginator',
		'FileUploader',
		'Wizard'
	);
	public $modules = array(
		'healthHistory' => 'Staff.StaffHealthHistory',
		'healthFamily' => 'Staff.StaffHealthFamily',
		'healthImmunization' => 'Staff.StaffHealthImmunization',
		'healthMedication' => 'Staff.StaffHealthMedication',
		'healthAllergy' => 'Staff.StaffHealthAllergy',
		'healthTest' => 'Staff.StaffHealthTest',
		'healthConsultation' => 'Staff.StaffHealthConsultation',
		'health' => 'Staff.StaffHealth',
		'specialNeed' => 'Staff.StaffSpecialNeed',
		'award' => 'Staff.StaffAward',
		'membership' => 'Staff.StaffMembership',
		'license' => 'Staff.StaffLicense',
		'trainingNeed' => 'Staff.StaffTrainingNeed',
		'trainingResult' => 'Staff.StaffTrainingResult',
		'trainingSelfStudy' => 'Staff.StaffTrainingSelfStudy',
		'contacts' => 'Staff.StaffContact',
		'identities' => 'Staff.StaffIdentity',
		'nationalities' => 'Staff.StaffNationality',
		'languages' => 'Staff.StaffLanguage',
		'bankAccounts' => 'Staff.StaffBankAccount',
		'comments' => 'Staff.StaffComment',
		'attachments' => 'Staff.StaffAttachment',
		'qualifications' => 'Staff.StaffQualification',
		'leaves' => 'Staff.StaffLeave',
		'extracurricular' => 'Staff.StaffExtracurricular',
		'employments' => 'Staff.StaffEmployment',
		'salaries' => 'Staff.StaffSalary',
		'behaviour' =>'Staff.StaffBehaviour',
		'training' => 'Staff.StaffTraining',
		'report' => 'Staff.StaffReport',
		'additional' => 'Staff.StaffCustomField',
		// new ControllerAction
		'InstitutionSiteStaff',
		'Position' => array('plugin' => 'Staff')
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Staff', array('controller' => $this->name, 'action' => 'index'));
		$this->Wizard->setModule('Staff');
		
		$actions = array('index', 'advanced');
		if (in_array($this->action, $actions)) {
			$this->bodyTitle = __('Staff');
			//$this->Session->delete('Staff');
		} else if ($this->Wizard->isActive()) {
			$this->bodyTitle = __('New Staff');
		} else if ($this->Session->check('Staff.data.name')) {
			$name = $this->Session->read('Staff.data.name');
			$this->staffId = $this->Session->read('Staff.id'); // for backward compatibility
			if ($this->action != 'view' && $this->action != 'InstitutionSiteStaff') {
				$this->Navigation->addCrumb($name, array('controller' => $this->name, 'action' => 'view'));
			}
			$this->bodyTitle = $name;
		}
	}

	public function index() {
		// redirect to InstitutionSiteStaff index page if institution is selected
		if ($this->Session->check('InstitutionSite.id')) {
			return $this->redirect(array('action' => 'InstitutionSiteStaff'));
		}
		// end redirect
		
		$this->Navigation->addCrumb('List of Staff');
		if ($this->request->is('post')) {
			if (isset($this->request->data['Staff']['SearchField'])) {
				$this->request->data['Staff']['SearchField'] = Sanitize::escape($this->request->data['Staff']['SearchField']);
				if ($this->request->data['Staff']['SearchField'] != $this->Session->read('Search.SearchFieldStaff')) {
					$this->Session->delete('Search.SearchFieldStaff');
					$this->Session->write('Search.SearchFieldStaff', $this->request->data['Staff']['SearchField']);
				}
			}

			if (isset($this->request->data['sortdir']) && isset($this->request->data['order'])) {
				if ($this->request->data['sortdir'] != $this->Session->read('Search.sortdirStaff')) {
					$this->Session->delete('Search.sortdirStaff');
					$this->Session->write('Search.sortdirStaff', $this->request->data['sortdir']);
				}
				if ($this->request->data['order'] != $this->Session->read('Search.orderStaff')) {
					$this->Session->delete('Search.orderStaff');
					$this->Session->write('Search.orderStaff', $this->request->data['order']);
				}
			}
		}

		$fieldordername = ($this->Session->read('Search.orderStaff')) ? $this->Session->read('Search.orderStaff') : 'Staff.first_name';
		$fieldorderdir = ($this->Session->read('Search.sortdirStaff')) ? $this->Session->read('Search.sortdirStaff') : 'asc';

		$searchKey = stripslashes($this->Session->read('Search.SearchFieldStaff'));
		$conditions = array(
			'SearchKey' => $searchKey,
			'AdvancedSearch' => $this->Session->check('Staff.AdvancedSearch') ? $this->Session->read('Staff.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id')
		);
		$order = array('order' => array($fieldordername => $fieldorderdir));
		$limit = ($this->Session->read('Search.perpageStaff')) ? $this->Session->read('Search.perpageStaff') : 30;
		$this->Paginator->settings = array_merge(array('limit' => $limit, 'maxLimit' => 100), $order);

		$data = $this->paginate('Staff', $conditions);
		if (empty($searchKey) && !$this->Session->check('Staff.AdvancedSearch')) {
			if (count($data) == 1 && !$this->AccessControl->newCheck($this->params['controller'], 'add')) {
				$this->redirect(array('action' => 'viewStaff', $data[0]['Staff']['id']));
			}
		}
		if (empty($data) && !$this->request->is('ajax')) {
			$this->Utility->alert($this->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
		}
		$this->set('staff', $data);
		$this->set('sortedcol', $fieldordername);
		$this->set('sorteddir', ($fieldorderdir == 'asc') ? 'up' : 'down');
		$this->set('searchField', $searchKey);
		if ($this->request->is('post')) {
			$this->render('index_records', 'ajax');
		}
	}

	public function advanced() {
		$key = 'Staff.AdvancedSearch';
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
			$search = $this->data;
			if (!empty($search)) {
				$this->Session->write($key, $search);
			}
			$this->redirect(array('action' => 'index'));
		}
		$this->set(compact('identityTypeOptions'));
	}

	public function getCustomFieldsSearch($sitetype = 0, $customfields = 'Staff') {
		$this->layout = false;
		$arrSettings = array(
			'CustomField' => $customfields . 'CustomField',
			'CustomFieldOption' => $customfields . 'CustomFieldOption',
			'CustomValue' => $customfields . 'CustomValue',
			'Year' => ''
		);
		if ($this->{$customfields}->hasField('institution_site_type_id')) {
			$arrSettings = array_merge(array('institutionSiteTypeId' => $sitetype), $arrSettings);
		}
		$arrCustFields = array($customfields => $arrSettings);

		$instituionSiteCustField = $this->Components->load('CustomField', $arrCustFields[$customfields]);
		$dataFields[$customfields] = $instituionSiteCustField->getCustomFields();
		$types = $this->InstitutionSiteType->findList(1);
		$this->set("customfields", array($customfields));
		$this->set('types', $types);
		$this->set('typeSelected', $sitetype);
		$this->set('dataFields', $dataFields);
		$this->render('/Elements/customfields/search');
	}

	public function view($id=0) {
		if ($id == 0 && $this->Wizard->isActive()) {
			return $this->redirect(array('action' => 'add'));
		}
		
		if ($id > 0) {
			if ($this->Staff->exists($id)) {
				$this->DateTime->getConfigDateFormat();
				$this->Session->write('Staff.id', $id);
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => 'index'));
			}
		} else {
			if ($this->Session->check('Staff.id')) {
				$id = $this->Session->read('Staff.id');
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => 'index'));
			}
		}
		$this->Staff->recursive = 0;
		$data = $this->Staff->findById($id);
		$obj = $data['Staff'];
		$name = trim($obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']);
		$obj['name'] = $name;
		$this->bodyTitle = $name;
		$this->Session->write('Staff.data', $obj);
		$this->Navigation->addCrumb($name, array('controller' => $this->name, 'action' => 'view'));
		$this->Navigation->addCrumb('Overview');
		$this->set('data', $data);
	}
	
	public function add() {
		$this->Wizard->start();
		return $this->redirect(array('action' => 'edit'));
	}

	public function edit() {
		$model = 'Staff';
		$id = null;
		$addressAreaId = false;
		$birthplaceAreaId = false;
		if ($this->Session->check($model . '.id')) {
			$id = $this->Session->read($model . '.id');
			$this->Staff->id = $id;
			$this->Navigation->addCrumb('Edit');
		} else {
			$this->Navigation->addCrumb('Add');
			$this->bodyTitle = __('New Staff');
		}
		$data = array();
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$this->Staff->set($data);
			$addressAreaId = $this->request->data[$model]['address_area_id'];
			$birthplaceAreaId = $this->request->data[$model]['birthplace_area_id'];
			
			if ($this->Staff->validates() && $this->Staff->save()) {
				if ($this->Wizard->isActive()) {
					if (is_null($id)) {
						$this->Message->alert('general.add.success');
						$id = $this->Staff->getLastInsertId();
						$this->Session->write($model . '.id', $id);
					}
					$this->Session->write($model . '.data', $this->Staff->findById($id));
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
				$data = $this->Staff->findById($id);
				$this->request->data = $data;
				$addressAreaId = $this->request->data['Staff']['address_area_id'];
				$birthplaceAreaId = $this->request->data['Staff']['birthplace_area_id'];
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

	public function delete() {
		$id = $this->Session->read('Staff.id');
		$name = $this->Staff->field('first_name', array('Staff.id' => $id));
		if ($name !== false) {
			$this->Staff->delete($id);
			$this->Message->alert('general.delete.success');
		} else {
			$this->Utility->alert(__($this->Utility->getMessage('DELETED_ALREADY')));
		}
		$this->redirect(array('action' => 'index'));
	}

	public function history() {
		$this->Navigation->addCrumb('History');
		$staffId = $this->Session->read('Staff.id');
		
		$arrTables = array('StaffHistory');
		$historyData = $this->StaffHistory->find('all', array(
			'conditions' => array('StaffHistory.staff_id' => $staffId),
			'order' => array('StaffHistory.created' => 'desc')
		));
		$data = $this->Staff->findById($staffId);
		$data2 = array();
		foreach ($historyData as $key => $arrVal) {
			foreach ($arrTables as $table) {
				foreach ($arrVal[$table] as $k => $v) {
					$keyVal = ($k == 'name') ? $table . '_name' : $k;
					$data2[$keyVal][$v] = $arrVal['StaffHistory']['created'];
				}
			}
		}
		if (empty($data2)) {
			$this->Utility->alert($this->Utility->getMessage('NO_HISTORY'), array('type' => 'info', 'dismissOnClick' => false));
		}

		$this->set('data', $data);
		$this->set('data2', $data2);
	}

	private function custFieldYrInits() {
		$this->Navigation->addCrumb('Annual Info');
		$action = $this->action;
		$siteid = @$this->request->params['pass'][2];
		$id = $this->Session->read('Staff.id');
		$schoolYear = ClassRegistry::init('SchoolYear');
		$years = $schoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('staff_id' => $id, 'institution_site_id' => $siteid, 'school_year_id' => $selectedYear);

		$arrMap = array('CustomField' => 'StaffDetailsCustomField',
			'CustomFieldOption' => 'StaffDetailsCustomFieldOption',
			'CustomValue' => 'StaffDetailsCustomValue',
			'Year' => 'SchoolYear');
		return compact('action', 'siteid', 'id', 'years', 'selectedYear', 'condParam', 'arrMap');
	}

	private function custFieldSY($school_yr_ids) {
		return $this->InstitutionSite->find('list', array('conditions' => array('InstitutionSite.id' => $school_yr_ids)));
	}

	private function custFieldSites($institution_sites) {
		$institution_sites = $this->InstitutionSite->find('all', array('fields' => array('InstitutionSite.id', 'InstitutionSite.name'/*, 'Institution.name'*/), 'conditions' => array('InstitutionSite.id' => $institution_sites)));
		//$institution_sites = $this->InstitutionSite->find('all', array('limit'=>1));
	
		$tmp = array('0' => '--');
		foreach ($institution_sites as $arrVal) {
			$tmp[$arrVal['InstitutionSite']['id']] = /*$arrVal['Institution']['name'] . ' - ' .*/ $arrVal['InstitutionSite']['name'];
		}
		return $tmp;
	}

	public function custFieldYrView() {
		$this->Navigation->addCrumb("More", array('controller' => 'Staff', 'action' => 'additional'));
		extract($this->custFieldYrInits());
		$customfield = $this->Components->load('CustomField', $arrMap);
		$data = array();
		if ($id && $selectedYear && $siteid)
			$data = $customfield->getCustomFieldView($condParam);
		$institution_sites = $customfield->getCustomValuebyCond('list', array('fields' => array('institution_site_id', 'school_year_id'), 'conditions' => array('school_year_id' => $selectedYear, 'staff_id' => $id)));
		$institution_sites = $this->custFieldSites(array_keys($institution_sites));
		if (count($institution_sites) < 2)
			$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
		$displayEdit = false;
		$this->set(compact('arrMap', 'selectedYear', 'siteid', 'years', 'action', 'id', 'institution_sites', 'displayEdit'));
		$this->set($data);
		$this->set('myview', 'additional');
		$this->render('/Elements/customfields/view');
	}

	// Staff ATTENDANCE PART
	public function absence() {
		$staffId = $this->Session->read('Staff.id');
		if(empty($staffId)){
			return $this->redirect(array('controller' => 'Staff', 'action' => 'index'));
		}

		$this->Navigation->addCrumb('Absence');
		$header = __('Absence');
		
		$yearList = $this->SchoolYear->getYearList();
		
		if (isset($this->params['pass'][0])) {
			$yearId = $this->params['pass'][0];
			if (!array_key_exists($yearId, $yearList)) {
				$yearId = key($yearList);
			}
		}else{
			$yearId = key($yearList);
		}
		
		$monthOptions = $this->generateMonthOptions();
		$currentMonthId = $this->getCurrentMonthId();
		if (isset($this->params['pass'][1])) {
			$monthId = $this->params['pass'][1];
			if (!array_key_exists($monthId, $monthOptions)) {
				$monthId = $currentMonthId;
			}
		}else{
			$monthId = $currentMonthId;
		}
		
		$absenceData = $this->InstitutionSiteStaffAbsence->getStaffAbsenceDataByMonth($staffId, $yearId, $monthId);
		//pr($absenceData);
		$data = $absenceData;
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
			//$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
		}
		
		$settingWeekdays = $this->getWeekdaysBySetting();

		$this->set(compact('header', 'data','yearList','yearId', 'monthOptions', 'monthId', 'settingWeekdays'));
	}

	public function generateAttendanceLegend(){
		$data = $this->StaffAttendanceType->getAttendanceTypes();
		
		$indicator = 0;
		$str = '';
		foreach($data AS $row){
			$code = $row['StaffAttendanceType']['national_code'];
			$name = $row['StaffAttendanceType']['name'];
			
			if($indicator > 0){
				$str .= '; ' . $code . ' = ' . $name;
			}else{
				$str .= $code . ' = ' . $name;
			}
			
			$indicator++;
		}
		
		return $str;
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
		$str = $this->Staff->find('first', array('order' => array('Staff.id DESC'), 'limit' => 1, 'fields' => 'Staff.id'));
		$prefix = $this->ConfigItem->find('first', array('limit' => 1,
			'fields' => 'ConfigItem.value',
			'conditions' => array(
				'ConfigItem.name' => 'staff_prefix'
			)
		));
		$prefix = explode(",", $prefix['ConfigItem']['value']);

		if ($prefix[1] > 0) {
			$id = $str['Staff']['id'] + 1;
			if (strlen($id) < 6) {
				$str = str_pad($id, 6, "0", STR_PAD_LEFT);
			}else{
				$str = $id;
			}
			// Get two random number
			$rnd1 = rand(0, 9);
			$rnd2 = rand(0, 9);
			$generate_no = $prefix[0] . $str . $rnd1 . $rnd2;
		}

		return $generate_no;
	}

	
	public function getTrainingCoursesById() {
		$this->autoRender = false;

		if (isset($this->params['pass'][0]) && !empty($this->params['pass'][0])) {
			$id = $this->params['pass'][0];
			$type = $this->params['pass'][1];
			if ($type == 1) {
				$courseData = $this->TrainingCourse->find('all', array(
					'fields' => array('TrainingCourse.*', 'TrainingRequirement.*'),
					'joins' => array(
						array(
							'type' => 'LEFT',
							'table' => 'training_requirements',
							'alias' => 'TrainingRequirement',
							'conditions' => array('TrainingRequirement.id = TrainingCourse.training_requirement_id')
						)
					),
					'conditions' => array('TrainingCourse.id' => $id),
					'recursive' => -1)
				);
			} else {
				$courseData = $this->TrainingCourse->find('all', array(
					'fields' => array('TrainingCourse.*', 'TrainingSession.*'),
					'joins' => array(
						array(
							'type' => 'INNER',
							'table' => 'training_sessions',
							'alias' => 'TrainingSession',
							'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id')
						)
					),
					'conditions' => array('TrainingSession.id' => $id),
					'recursive' => -1)
				);
			}
			echo json_encode($courseData);
		}
	}
	
	public function generateMonthOptions(){
		$options = array();
		for ($i = 1; $i <= 12; $i++)
		{
				$options[$i] = date("F", mktime(0, 0, 0, $i+1, 0, 0, 0));
		}
		
		return $options;
	}
	
	public function getCurrentMonthId(){
		$options = $this->generateMonthOptions();
		$currentMonth = date("F");
		$monthId = 1;
		foreach($options AS $id => $month){
			if($currentMonth === $month){
				$monthId = $id;
				break;
			}
		}
		
		return $monthId;
	}
	
	public function getWeekdaysBySetting(){
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
		if(empty($settingFirstWeekDay) || !in_array($settingFirstWeekDay, $weekdaysArr)){
			$settingFirstWeekDay = 'monday';
		}
		
		$settingDaysPerWek = intval($this->ConfigItem->getValue('days_per_week'));
		if(empty($settingDaysPerWek)){
			$settingDaysPerWek = 5;
		}
		
		foreach($weekdaysArr AS $index => $weekday){
			if($weekday == $settingFirstWeekDay){
				$firstWeekdayIndex = $index;
				break;
			}
		}
		
		$newIndex = $firstWeekdayIndex + $settingDaysPerWek;
		
		$weekdays = array();
		for($i=$firstWeekdayIndex; $i<$newIndex; $i++){
			if($i<=7){
				$weekdays[] = $weekdaysArr[$i];
			}else{
				$weekdays[] = $weekdaysArr[$i%7];
			}
		}
		
		return $weekdays;
	}


	public function ajax_find_training_provider() {
		$this->autoRender = false;
		if($this->request->is('ajax')) {
			$this->autoRender = false;
			$search = $this->params->query['term'];
			$data = $this->StaffTrainingSelfStudy->autocompleteTrainingProvider($search);
 
			return json_encode($data);
		 }
	}

}

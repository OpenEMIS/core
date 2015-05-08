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
		'SecurityUser',
		'AreaAdministrative',
		'InstitutionSite',
		'InstitutionSiteType',
		'Staff.Staff',
		'Staff.StaffActivity',
		'Staff.StaffCustomField',
		'Staff.StaffCustomFieldOption',
		'Staff.StaffCustomValue',
		'Staff.StaffAttendance',
		'Staff.StaffLeave',
		'Staff.StaffBehaviour',
		'AcademicPeriod',
		'ConfigItem',
		'SalaryAdditionType',
		'SalaryDeductionType',
		'TrainingCourse',
		'Staff.StaffAttendanceType',
		'InstitutionSiteStaffAbsence',
		'Staff.StaffTrainingSelfStudy'
	);
	public $helpers = array('Js' => array('Jquery'), 'Paginator');
	public $components = array(
		'ControllerAction',
		'Paginator',
		'FileUploader',
		'Activity' => array('model' => 'StaffActivity'),
		'Workflow2',
		'PhpExcel'
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
		'StaffSpecialNeed' => array('plugin' => 'Staff'),
		'StaffAward' => array('plugin' => 'Staff'),
		'membership' => 'Staff.StaffMembership',
		'license' => 'Staff.StaffLicense',
		'trainingNeed' => 'Staff.StaffTrainingNeed',
		'trainingResult' => 'Staff.StaffTrainingResult',
		'trainingSelfStudy' => 'Staff.StaffTrainingSelfStudy',
		'StaffContact' => array('plugin' => 'Staff'),
		'StaffIdentity' => array('plugin' => 'Staff'),
		'StaffNationality' => array('plugin' => 'Staff'),
		'StaffLanguage' => array('plugin' => 'Staff'),
		'bankAccounts' => 'Staff.StaffBankAccount',
		'StaffComment' => array('plugin' => 'Staff'),
		'attachments' => 'Staff.StaffAttachment',
		'qualifications' => 'Staff.StaffQualification',
		'extracurricular' => 'Staff.StaffExtracurricular',
		'employments' => 'Staff.StaffEmployment',
		'StaffSalary' => array('plugin' => 'Staff'),
		'training' => 'Staff.StaffTraining',
		'report' => 'Staff.StaffReport',
		'additional' => 'Staff.StaffCustomField',
		// new ControllerAction
		'InstitutionSiteStaff',
		'Position' => array('plugin' => 'Staff'),
		'StaffClass' => array('plugin' => 'Staff'),
		'StaffSection' => array('plugin' => 'Staff')
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Staff', array('controller' => $this->name, 'action' => 'index'));
		
		$actions = array('index', 'advanced', 'import', 'importTemplate', 'downloadFailed');
		if (in_array($this->action, $actions)) {
			$this->bodyTitle = __('Staff');
			//$this->Session->delete('Staff');
		} else if ($this->action == 'add') {
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

		$searchKey = $this->Session->check('Staff.search.key') ? $this->Session->read('Staff.search.key') : '';
		if ($this->request->is(array('post', 'put'))) {
			$searchKey = Sanitize::escape($this->request->data['Staff']['search']);
		}

		$IdentityType = ClassRegistry::init('IdentityType');
		$defaultIdentity = $IdentityType->find('first', array(
			'contain' => array('FieldOption'),
			'conditions' => array('FieldOption.code' => $IdentityType->alias),
			'order' => array('IdentityType.default DESC')
		));

		$conditions = array(
			'SearchKey' => $searchKey,
			'AdvancedSearch' => $this->Session->check('Staff.AdvancedSearch') ? $this->Session->read('Staff.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id'),
			'defaultIdentity' => $defaultIdentity['IdentityType']['id']
		);

		$order = empty($this->params->named['sort']) ? array('SecurityUser.first_name' => 'asc') : array();
		$data = $this->Search->search($this->Staff, $conditions, $order);
		$data = $this->Staff->attachLatestInstitutionInfo($data);
		
		if (empty($searchKey) && !$this->Session->check('Staff.AdvancedSearch')) {
			if (count($data) == 1 && !$this->AccessControl->newCheck($this->params['controller'], 'add')) {
				$this->redirect(array('action' => 'view', $data[0]['Staff']['id']));
			}
		}
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->set('data', $data);
		$this->set('defaultIdentity', $defaultIdentity['IdentityType']);
	}

	public function advanced() {
		$key = 'Staff.AdvancedSearch';
		$this->set('header', __('Advanced Search'));

		$IdentityType = ClassRegistry::init('IdentityType');
		$identityTypeOptions = $IdentityType->getList();

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
		$types = $this->InstitutionSiteType->getList();
		$this->set("customfields", array($customfields));
		$this->set('types', $types);
		$this->set('typeSelected', $sitetype);
		$this->set('dataFields', $dataFields);
		$this->render('/Elements/customfields/search');
	}

	public function view($id=0) {
		if ($id == 0) {
			$staffIdSession = $this->Session->read('Staff.id');
			if(empty($staffIdSession)){
				return $this->redirect(array('action' => 'add'));
			}
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
		$this->Staff->contain(array('SecurityUser' => array('Gender')));
		$data = $this->Staff->findById($id);
		$obj = $data['Staff'];
		$name = ModelHelper::getName($data['SecurityUser']);
		$obj['name'] = $name;
		$this->bodyTitle = $name;
		$this->Session->write('Staff.data', $obj);
		$this->Session->write('Staff.user.data', $data['SecurityUser']);
		$this->Session->write('Staff.security_user_id', $obj['security_user_id']);
		$this->Navigation->addCrumb($name, array('controller' => $this->name, 'action' => 'view'));
		$this->Navigation->addCrumb('Overview');
		$this->set('data', $data);
	}

	public function add() {
		$this->SecurityUser->controller = $this;
		$this->SecurityUser->setMandatoryModel('Staff');

		$this->SecurityUser->StaffContact->validator()->remove('preferred');
		$this->SecurityUser->validator()->remove('username', 'ruleRequired');

		$ConfigItem = ClassRegistry::init('ConfigItem');

		$configContacts = $ConfigItem->getOptionValue('StaffContact');
		// mandatory behavior
		// if request data has key... then modify the validation rules after finding the config item values

		

		$model = 'SecurityUser';
		$id = null;

		$this->Navigation->addCrumb('Add');
		$this->bodyTitle = __('New Staff');

		$data = array();
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			if (array_key_exists('Staff', $data)) {
				if (array_key_exists('birthplace_area_id', $data['Staff'])) {
					if (!array_key_exists($model, $data)) {
						$data[$model] = array();
					}
					$data[$model]['birthplace_area_id'] = $data['Staff']['birthplace_area_id'];
					$birthplace_area_id = $data['Staff']['birthplace_area_id'];
					unset($data['Staff']['birthplace_area_id']);
				}
				if (array_key_exists('address_area_id', $data['Staff'])) {
					if (!array_key_exists($model, $data)) {
						$data[$model] = array();
					}
					$data[$model]['address_area_id'] = $data['Staff']['address_area_id'];
					$address_area_id = $data['Staff']['address_area_id'];
					unset($data['Staff']['address_area_id']);
				}
				$data['Staff'][0] = array();
				if (isset($birthplace_area_id)) {
					$data['Staff'][0]['birthplace_area_id'] = $birthplace_area_id;
				}
				if (isset($address_area_id)) {
					$data['Staff'][0]['address_area_id'] = $address_area_id;
				}
			} else {
				$data['Staff'][0] = array();
				// so there is something in the array for staff to be saved too. overriden later by app model
				$data['Staff'][0]['created_user_id'] = 0;
			}

			if (array_key_exists('submit', $data) && $data['submit'] == 'changeNationality') {
				unset($this->request->data['StaffIdentity']);
				$data = $this->request->data;
			} else {
				if ($this->SecurityUser->saveAll($data)) {
					$InstitutionSiteStaffModel = ClassRegistry::init('InstitutionSiteStaff');
					$InstitutionSiteStaffModel->validator()->remove('search');
					$dataToSite = $this->Session->read('InstitutionSiteStaff.addNew');

					$securityUserId = $this->SecurityUser->getLastInsertId();
					
					$this->Message->alert('Staff.add.success');
					$id = $this->Staff->getLastInsertId();
					$this->Session->write('Staff.id', $id);

					if (!empty($dataToSite)) {
						$staffStatusId = $InstitutionSiteStaffModel->StaffStatus->getDefaultValue();
						$dataToSite['staff_status_id'] = $staffStatusId;
						$dataToSite['staff_id'] = $id;

						$InstitutionSiteStaffModel->save($dataToSite);
					}

					$this->Session->write('Staff.data', $this->Staff->findById($id));
					$this->Session->write('Staff.security_user_id', $securityUserId);

					return $this->redirect(array('action' => 'view'));

				} else {
				}
			}
		}

		if (array_key_exists($model, $data)) {
			if (array_key_exists('address_area_id', $data[$model])) {
				$addressAreaId = $data[$model]['address_area_id'];
			}
			if (array_key_exists('birthplace_area_id', $data[$model])) {
				$birthplaceAreaId = $data[$model]['birthplace_area_id'];	
			}	
		}

		$genderOptions = $this->SecurityUser->Gender->getList();
		$dataMask = $this->ConfigItem->getValue('staff_identification');
		$arrIdNo = !empty($dataMask) ? array('data-mask' => $dataMask) : array();

		$Country = ClassRegistry::init('Country');
		$nationalityOptions = $Country->getOptions();

		$identityTypeOption = array();
		if (array_key_exists('StaffNationality', $this->request->data)) {
			$identityTypeOption = $this->request->data['StaffNationality'][0];
		} else {
			$first_key = key($nationalityOptions);
			$identityTypeOption = array('country_id' => $first_key);
			
		}

		$IdentityType = ClassRegistry::init('IdentityType');
		$identityTypeOptions = $IdentityType->getList($identityTypeOption);

		$SpecialNeedType = ClassRegistry::init('SpecialNeedType');
		$specialNeedOptions = $SpecialNeedType->getList($identityTypeOption);

		$ContactType = ClassRegistry::init('ContactType');
		$contactOptionData = $ContactType->find(
			'all',
			array(
				'contain' => array(
					'ContactOption' => array(
						'name'
					)
				)
			)
		);
		$contactOptions = array();
		foreach ($contactOptionData as $key => $value) {
			$contactOptions[$value['ContactType']['id']] = $value['ContactType']['name'].' - '.$value['ContactOption']['name'];
		}

		$this->set(compact('nationalityOptions', 'identityTypeOptions', 'contactOptions', 'specialNeedOptions'));

		$this->set('autoid', $this->Utility->getUniqueOpenemisId(array('model'=>'Staff')));
		$this->set('arrIdNo', $arrIdNo);
		$this->set('genderOptions', $genderOptions);
		$this->set('data', $data);
		$this->set('model', $model);
		$this->set('addressAreaId', (isset($addressAreaId))? $addressAreaId: null);
		$this->set('birthplaceAreaId', (isset($birthplaceAreaId))? $birthplaceAreaId: null);
	}

	public function edit() {
		$model = 'SecurityUser';
		$id = null;

		$staffIdSession = $this->Session->read('Staff.id');	

		if (!empty($staffIdSession)) {
			$this->Navigation->addCrumb('Edit');
		} else {
			$this->Navigation->addCrumb('Add');
			$this->bodyTitle = __('New Staff');
		}
		
		$data = array();
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			if (array_key_exists('Staff', $data)) {
				if (array_key_exists('birthplace_area_id', $data['Staff'])) {
					if (!array_key_exists($model, $data)) {
						$data[$model] = array();
					}
					$data[$model]['birthplace_area_id'] = $data['Staff']['birthplace_area_id'];
					unset($data['Staff']['birthplace_area_id']);
				}
				if (array_key_exists('address_area_id', $data['Staff'])) {
					if (!array_key_exists($model, $data)) {
						$data[$model] = array();
					}
					$data[$model]['address_area_id'] = $data['Staff']['address_area_id'];
					unset($data['Staff']['address_area_id']);
				}
			}

			if (array_key_exists('submit', $data) && $data['submit'] == 'changeNationality') {
				unset($this->request->data['StaffIdentity']);
				$data = $this->request->data;
			} else {				
				if ($this->SecurityUser->saveAll($data)) {
					$InstitutionSiteStaffModel = ClassRegistry::init('InstitutionSiteStaff');
					$InstitutionSiteStaffModel->validator()->remove('search');
					$dataToSite = $this->Session->read('InstitutionSiteStaff.addNew');

					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => 'view'));
				}
			}
		} else {
			if (!empty($staffIdSession)) {
				$data = $this->Staff->findById($staffIdSession);
				$this->request->data = $data;
			}
		}

		if (array_key_exists($model, $data)) {
			if (array_key_exists('address_area_id', $data[$model])) {
				$addressAreaId = $data[$model]['address_area_id'];
			}
			if (array_key_exists('birthplace_area_id', $data[$model])) {
				$birthplaceAreaId = $data[$model]['birthplace_area_id'];	
			}	
		}

		$genderOptions = $this->SecurityUser->Gender->getList();
		$dataMask = $this->ConfigItem->getValue('staff_identification');
		$arrIdNo = !empty($dataMask) ? array('data-mask' => $dataMask) : array();

		$this->set('autoid', $this->Utility->getUniqueOpenemisId(array('model'=>'Staff')));
		$this->set('arrIdNo', $arrIdNo);
		$this->set('genderOptions', $genderOptions);
		$this->set('data', $data);
		$this->set('model', $model);
		$this->set('addressAreaId', (isset($addressAreaId))? $addressAreaId: null);
		$this->set('birthplaceAreaId', (isset($birthplaceAreaId))? $birthplaceAreaId: null);
	}

	public function delete() {
		$id = $this->Session->read('Staff.id');
		$secId = $this->Staff->field('security_user_id', array('Staff.id' => $id));
		
		if ($this->Staff->delete($id)) {
			$this->SecurityUser->delete($secId);
		}
		
		$this->Message->alert('general.delete.success');
		return $this->redirect(array('action' => 'index'));
	}
	
	public function excel() {
		$this->Staff->excel();
	}
	
	// Staff ATTENDANCE PART
	public function absence() {
		$staffId = $this->Session->read('Staff.id');
		if(empty($staffId)){
			return $this->redirect(array('controller' => 'Staff', 'action' => 'index'));
		}

		$this->Navigation->addCrumb('Absence');
		$header = __('Absence');
		
		$academicPeriodList = $this->AcademicPeriod->getAcademicPeriodList();
		
		if (isset($this->params['pass'][0])) {
			$academicPeriodId = $this->params['pass'][0];
			if (!array_key_exists($academicPeriodId, $academicPeriodList)) {
				$academicPeriodId = key($academicPeriodList);
			}
		}else{
			$academicPeriodId = key($academicPeriodList);
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
		
		$absenceData = $this->InstitutionSiteStaffAbsence->getStaffAbsenceDataByMonth($staffId, $academicPeriodId, $monthId);
		$data = $absenceData;
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
			//$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
		}
		
		$settingWeekdays = $this->getWeekdaysBySetting();

		$this->set(compact('header', 'data','academicPeriodList','academicPeriodId', 'monthOptions', 'monthId', 'settingWeekdays'));
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
	
	private function getAvailableAcademicPeriodId($academicPeriodList) {
		$academicPeriodId = 0;
		if (isset($this->params['pass'][0])) {
			$academicPeriodId = $this->params['pass'][0];
			if (!array_key_exists($academicPeriodId, $academicPeriodList)) {
				$academicPeriodId = key($academicPeriodList);
			}
		} else {
			$academicPeriodId = key($academicPeriodList);
		}
		return $academicPeriodId;
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
							'table' => 'field_option_values',
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
	
	public function import() {
		$this->Navigation->addCrumb('Import');
		$model = 'Staff';

		if ($this->request->is(array('post', 'put'))) {
			if (!empty($this->request->data[$model]['excel'])) {
				$fielObj = $this->request->data[$model]['excel'];
				if ($fielObj['error'] == 0) {
					$supportedFormats = $this->{$model}->getSupportedFormats();
					$uploadedName = $fielObj['name'];
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$fileFormat = finfo_file($finfo, $fielObj['tmp_name']);
					finfo_close($finfo);
					if(!in_array($fileFormat, $supportedFormats)){
						$this->Message->alert('Import.formatNotSupported');
						return $this->redirect(array('controller' => 'Staff', 'action' => 'import'));
					}
					$header = $this->{$model}->getHeader();
					$columns = $this->{$model}->getColumns();
					$mapping = $this->{$model}->getMapping();
					$totalColumns = count($columns);

					$lookup = $this->{$model}->getCodesByMapping($mapping);

					$uploaded = $fielObj['tmp_name'];

					$objPHPExcel = $this->PhpExcel->loadWorksheet($uploaded);
					$worksheets = $objPHPExcel->getWorksheetIterator();
					$firstSheetOnly = false;

					$totalImported = 0;
					$totalUpdated = 0;
					$dataFailed = array();
					foreach ($worksheets as $sheet) {
						if ($firstSheetOnly) {break;}

						$highestRow = $sheet->getHighestRow();
						$totalRows = $highestRow;
						//$highestColumn = $sheet->getHighestColumn();
						//$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
						
						$openemisNo = $this->Utility->getUniqueOpenemisId(array('model' => 'Student'));
						for ($row = 1; $row <= $highestRow; ++$row) {
							$tempRow = array();
							$originalRow = array();
							$rowPass = true;
							$rowInvalidCodeCols = array();
							for ($col = 0; $col < $totalColumns; ++$col) {
								$cell = $sheet->getCellByColumnAndRow($col, $row);
								$originalValue = $cell->getValue();
								$cellValue = $originalValue;
								if(gettype($cellValue) == 'double' || gettype($cellValue) == 'boolean'){
									$cellValue = (string) $cellValue;
								}
								$excelMappingObj = $mapping[$col]['ImportMapping'];
								$foreignKey = $excelMappingObj['foreign_key'];
								$columnName = $columns[$col];
								$originalRow[$col] = $originalValue;
								$val = $cellValue;
								
								if($row > 1){
									if(!empty($val)){
										if($columnName == 'date_of_birth'){
											$val = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($val));
											$originalRow[$col] = $val;
										}
									}
									
									$translatedCol = $this->{$model}->getExcelLabel($model.'.'.$columnName);
									if(empty($translatedCol)){
										$translatedCol = __($columnName);
									}

									if ($foreignKey == 1) {
										if(!empty($cellValue)){
											if (array_key_exists($cellValue, $lookup[$col])) {
												$val = $lookup[$col][$cellValue];
											} else {
												if($row !== 1 && $cellValue != ''){
													$rowPass = false;
													$rowInvalidCodeCols[] = $translatedCol;
												}
											}
										}
									} else if ($foreignKey == 2) {
										$excelLookupModel = ClassRegistry::init($excelMappingObj['lookup_model']);
										$recordId = $excelLookupModel->field('id', array($excelMappingObj['lookup_column'] => $cellValue));
										if(!empty($recordId)){
											$val = $recordId;
										}else{
											if($row !== 1 && $cellValue != ''){
												$rowPass = false;
												$rowInvalidCodeCols[] = $translatedCol;
											}
										}
									}
								}
								
								$tempRow[$columnName] = $val;
							}

							if(!$rowPass){
								$rowCodeError = $this->{$model}->getExcelLabel('Import.invalid_code');
								$colCount = 1;
								foreach($rowInvalidCodeCols as $codeCol){
									if($colCount == 1){
										$rowCodeError .= ': ' . $codeCol;
									}else{
										$rowCodeError .= ', ' . $codeCol;
									}
									$colCount ++;
								}
								
								$dataFailed[] = array(
									'row_number' => $row,
									'error' => $rowCodeError,
									'data' => $originalRow
								);
								continue;
							}
							
							if ($row === 1) {
								$header = $tempRow;
								$dataFailed = array();
								continue;
							}
							
							if(empty($tempRow['openemis_no'])){
								$tempRow['openemis_no'] = ++$openemisNo;
								$tempRow['openemis_no_generated'] = true;
							}
							
							$this->SecurityUser->set($tempRow);
							if ($this->SecurityUser->validates()) {
								$this->SecurityUser->create();
								if ($this->SecurityUser->save($tempRow)) {
									$totalImported++;
									
									$securityUserId = $this->SecurityUser->getLastInsertId();
									$this->{$model}->create();
									$this->{$model}->save(array('security_user_id' => $securityUserId));
								} else {
									$dataFailed[] = array(
										'row_number' => $row,
										'error' => $this->{$model}->getExcelLabel('Import.saving_failed'),
										'data' => $originalRow
									);
								}
							} else {
								$validationErrors = $this->SecurityUser->validationErrors;
								if(array_key_exists('openemis_no', $validationErrors) && count($validationErrors) == 1){
									$updateRow = $tempRow;
									if(empty($updateRow['openemis_no_type'])){
										$idExisting = $this->SecurityUser->field('id', array('openemis_no' => $updateRow['openemis_no']));
										$updateRow['id'] = $idExisting;

										if($this->SecurityUser->save($updateRow)) {
											$totalUpdated++;
										}else{
											$dataFailed[] = array(
												'row_number' => $row,
												'error' => $this->{$model}->getExcelLabel('Import.saving_failed'),
												'data' => $originalRow
											);
										}
									}else{
										$updateRow['openemis_no'] = ++$openemisNo;
										$this->SecurityUser->create();
										if ($this->SecurityUser->save($updateRow)) {
											$totalImported++;

											$securityUserId = $this->SecurityUser->getLastInsertId();
											$this->{$model}->create();
											$this->{$model}->save(array('security_user_id' => $securityUserId));
										} else {
											$dataFailed[] = array(
												'row_number' => $row,
												'error' => $this->{$model}->getExcelLabel('Import.saving_failed'),
												'data' => $originalRow
											);
										}
									}
								}else{
									$errorStr = $this->{$model}->getExcelLabel('Import.validation_failed');
									$count = 1;
									foreach($validationErrors as $field => $arr){
										$fieldName = $this->{$model}->getExcelLabel($model.'.'.$field);
										if(empty($fieldName)){
											$fieldName = __($field);
										}

										if($count === 1){
											$errorStr .= ': ' . $fieldName;
										}else{
											$errorStr .= ', ' . $fieldName;
										}
										$count ++;
									}
									
									$dataFailed[] = array(
										'row_number' => $row,
										'error' => $errorStr,
										'data' => $originalRow
									);
									$this->log($this->{$model}->validationErrors, 'debug');
								}
							}
						}

						$firstSheetOnly = true;
					}
					
					if(!empty($dataFailed)){
						$downloadFolder = $this->{$model}->prepareDownload();
						$excelFile = sprintf('%s_%s_%s_%s.xlsx', 
								$this->{$model}->getExcelLabel('general.import'), 
								$this->{$model}->getExcelLabel('general.'.  strtolower($this->{$model}->alias)), 
								$this->{$model}->getExcelLabel('general.failed'),
								time()
						);
						$excelPath = $downloadFolder . DS . $excelFile;

						$writer = new XLSXWriter();
						$newHeader = $header;
						$newHeader[] = $this->{$model}->getExcelLabel('general.errors');
						$writer->writeSheetRow('sheet1', array_values($newHeader));
						foreach($dataFailed as $record){
							$record['data'][] = $record['error'];
							$writer->writeSheetRow('sheet1', array_values($record['data']));
						}
						$writer->writeToFile($excelPath);
					}else{
						$excelFile = null;
					}

					$this->set(compact('uploadedName', 'totalRows', 'dataFailed', 'totalImported', 'totalUpdated', 'header', 'excelPath', 'excelFile'));
				}
			}
		}
		
		$this->set(compact('model'));
	}

	public function importTemplate(){
		$this->Staff->downloadTemplate();
	}
	
	public function downloadFailed($excelFile){
		$this->Staff->performDownload($excelFile);
	}

}

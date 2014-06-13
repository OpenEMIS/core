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
	public $studentId;
	public $studentObj;
	public $uses = array(
		'Area',
		'Institution',
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
		'behaviour'=>'Students.StudentBehaviour'
	);

	public $className = 'Student';

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Students', array('controller' => 'Students', 'action' => 'index'));
		$this->Wizard->setModule('Student');
		$this->bodyTitle = 'Students';
		/*
		if($this->Session->check('Student.wizard.mode')) {
			
		}
		*/
		/*
		$actions = array('index', 'advanced', 'add', 'view');
		$this->set('Student.wizard.mode', false);
		
		if (in_array($this->action, $actions)) {
			$this->bodyTitle = 'Students';
			if($this->action=='add'){
				$this->Session->delete('StudentId');
				$this->Session->write('WizardMode', true);
				$wizardLink = $this->Navigation->getWizardLinks('Student');
				$this->Session->write('WizardLink', $wizardLink);
				$this->redirect(array('action'=>'edit'));
			}
		} else {
			if($this->Session->check('WizardMode') && $this->Session->read('WizardMode')==true){
				$this->set('WizardMode', true);
				$this->Navigation->getWizard($this->action);
			}
			if ($this->Session->check('StudentId') && $this->action !== 'Home') {
				$this->studentId = $this->Session->read('StudentId');
				$this->studentObj = $this->Session->read('StudentObj');
				$obj = $this->Session->read('Student.data');
				$firstName = $obj['first_name'];
				$middleName = $obj['middle_name'];
				$lastName = $obj['last_name'];
				$name = $firstName . " " . $middleName . " " . $lastName;
				$this->bodyTitle = $name;
				$this->Navigation->addCrumb($name, array('controller' => 'Students', 'action' => 'view'));
			}else if (!$this->Session->check('StudentId') && $this->action !== 'Home') {
				$name = __('New Student');
				$this->bodyTitle = $name;
			}
		} 
		*/
	}

	public function index() {
		$this->Navigation->addCrumb('List of Students');
		$this->Session->delete('Student');
		
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
		if ($this->request->is('get')) {
			if ($this->request->is('ajax')) {
				$this->autoRender = false;
				$search = $this->params->query['term'];
				$result = $this->Area->autocomplete($search);
				return json_encode($result);
			} else {
				$this->Navigation->addCrumb('List of Students', array('controller' => 'Students', 'action' => 'index'));
				$this->Navigation->addCrumb('Advanced Search');

				if (isset($this->params->pass[0])) {
					if (intval($this->params->pass[0]) === 0) {
						$this->Session->delete($key);
						$this->redirect(array('action' => 'index'));
					}
				}
			}
		} else {
			//$search = $this->request->data['Search'];
			$search = $this->request->data;
			if (!empty($search)) {
				$this->Session->write($key, $search);
			}
			$this->redirect(array('action' => 'index'));
		}
	}
		
	public function getCustomFieldsSearch($sitetype = 0,$customfields = 'Student'){
		$this->layout = false;
		$arrSettings = array(
			'CustomField'=>$customfields.'CustomField',
			'CustomFieldOption'=>$customfields.'CustomFieldOption',
			'CustomValue'=>$customfields.'CustomValue',
			'Year'=>''
		);
		if($this->{$customfields}->hasField('institution_site_type_id')){
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
		$this->Navigation->addCrumb('Overview');
		
		if($id == 0 && $this->Wizard->isActive()) {
			return $this->redirect(array('action' => 'add'));
		}
		
		if($id > 0) {
			if($this->Student->exists($id)) {
				$this->DateTime->getConfigDateFormat();
				$this->Session->write('Student.id', $id);
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => 'index'));
			}
		} else {
			if($this->Session->check('Student.id')) {
				$id = $this->Session->read('Student.id');
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => 'index'));
			}
		}
		$data = $this->Student->findById($id);
		$obj = $data['Student'];
		$name = trim($obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']);
		$this->bodyTitle = $name;
		$this->Session->write('Student.data', $obj);
		$this->set('data', $data);
	}
	
	public function add() {
		$this->Wizard->start();
		return $this->redirect(array('action' => 'edit'));
	}

	public function edit() {
		$studentId = null;
		$addressAreaId = false;
		$birthplaceAreaId = false;
		if ($this->Session->check('Student.id')) {
			$studentId = $this->Session->read('Student.id');
			$this->Student->id = $studentId;
			$this->Navigation->addCrumb('Edit');
		} else {
			$this->Navigation->addCrumb('Add');
			$this->bodyTitle = __('New Student');
		}
		$data = array();
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$this->Student->set($data);
			$addressAreaId = $this->request->data['Student']['address_area_id'];
			$birthplaceAreaId = $this->request->data['Student']['birthplace_area_id'];
			
			if ($this->Student->validates() && $this->Student->save()) {
				if($this->Wizard->isActive()) {
					if(is_null($studentId)) {
						$this->Message->alert('Student.add.success');
						$studentId = $this->Student->getLastInsertId();
						$this->Session->write('Student.id', $studentId);
					}
					$this->Session->write('Student.data', $this->Student->findById($studentId));
					// unset wizard so it will not auto redirect from WizardComponent
					unset($this->request->data['wizard']['next']);
					$this->Wizard->next();
				} else {
					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => 'view'));
				}
			}
		} else {
			if (!empty($studentId)) {
				$data = $this->Student->findById($studentId);
				$this->request->data = $data;
				$addressAreaId = $this->request->data['Student']['address_area_id'];
				$birthplaceAreaId = $this->request->data['Student']['birthplace_area_id'];
			}
		}
		$genderOptions = $this->Option->get('gender');
		$this->set('autoid', $this->getUniqueID());
		$this->set('genderOptions', $genderOptions);
		$this->set('data', $data);
		$this->set('model', 'Student');
		$this->set('addressAreaId', $addressAreaId);
		$this->set('birthplaceAreaId', $birthplaceAreaId);
	}

	public function classes() {
		$this->Navigation->addCrumb(ucfirst($this->action));
		$header = __(ucfirst($this->action));
		$studentId = $this->Session->read('StudentId');
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
		$id = $this->Session->read('StudentId');
		$this->Student->delete($id);
		$this->Message->alert('general.delete.success');
		$this->redirect(array('action' => 'index'));
	}

	public function additional() {
		$this->Navigation->addCrumb('More');
		$header = __('More');
		// get all student custom field in order
		$data = $this->StudentCustomField->find('all', array('conditions' => array('StudentCustomField.visible' => 1), 'order' => 'StudentCustomField.order'));

		$this->StudentCustomValue->unbindModel(array('belongsTo' => array('Student')));
		$cusValuesData = $this->StudentCustomValue->find('all', array(
			'conditions' => array('StudentCustomValue.student_id' => $this->studentId))
		);
		$dataValues = array();
		foreach ($cusValuesData as $arrV) {
			$dataValues[$arrV['StudentCustomField']['id']][] = $arrV['StudentCustomValue'];
			// pr($arrV);
		}
		// pr($tmp);die;
		//$this->UserSession->readStatusSession($this->request->action);
		$this->set(compact('header','data', 'dataValues'));
	}

	public function additionalEdit() {
		$this->Navigation->addCrumb('Edit More');

		if ($this->request->is('post')) {
			if(isset($this->request->data['submit']) && $this->request->data['submit']==__('Previous')){
				$this->Navigation->previousWizardLink($this->action);
			}
			$mandatory = $this->Navigation->getMandatoryWizard($this->action);
			$error = false;
			//pr($this->request->data);
			//die();
			$arrFields = array('textbox', 'dropdown', 'checkbox', 'textarea');
			/**
			* Note to Preserve the Primary Key to avoid exhausting the max PK limit
			*/
			foreach ($arrFields as $fieldVal) {
				// pr($fieldVal);
				// pr($this->request->data['StudentCustomValue']);
				if (!isset($this->request->data['StudentCustomValue'][$fieldVal]))
					continue;
				foreach ($this->request->data['StudentCustomValue'][$fieldVal] as $key => $val) {

					if ($fieldVal == "checkbox") {
						if($mandatory && count($val['value'])==0){
							$this->Utility->alert(__('Record is not added due to errors encountered.'), array('type' => 'error'));
							$error = true;
							break;
						}

						$arrCustomValues = $this->StudentCustomValue->find('list', array('fields' => array('value'), 'conditions' => array('StudentCustomValue.student_id' => $this->studentId, 'StudentCustomValue.student_custom_field_id' => $key)));

						$tmp = array();
						if (count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
							foreach ($arrCustomValues as $pk => $intVal) {
								//pr($val['value']); echo "$intVal";
								if (!in_array($intVal, $val['value'])) {
									//echo "not in db so remove \n";
									$this->StudentCustomValue->delete($pk);
								}
							}
						$ctr = 0;
						if (count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
							foreach ($val['value'] as $intVal) {
								//pr($val['value']); echo "$intVal";
								if (!in_array($intVal, $arrCustomValues)) {
									$this->StudentCustomValue->create();
									$arrV['student_custom_field_id'] = $key;
									$arrV['value'] = $val['value'][$ctr];
									$arrV['student_id'] = $this->studentId;
									$this->StudentCustomValue->save($arrV);
									unset($arrCustomValues[$ctr]);
								}
								$ctr++;
							}
					} else { // if editing reuse the Primary KEY; so just update the record
						if($mandatory && empty($val['value'])){
							$this->Utility->alert(__('Record is not added due to errors encountered.'), array('type' => 'error'));
							$error = true;
							break;
						}
						$datafields = $this->StudentCustomValue->find('first', array('fields' => array('id', 'value'), 'conditions' => array('StudentCustomValue.student_id' => $this->studentId, 'StudentCustomValue.student_custom_field_id' => $key)));
						$this->StudentCustomValue->create();
						if ($datafields)
							$this->StudentCustomValue->id = $datafields['StudentCustomValue']['id'];
						$arrV['student_custom_field_id'] = $key;
						$arrV['value'] = $val['value'];
						$arrV['student_id'] = $this->studentId;
						$this->StudentCustomValue->save($arrV);
					}
				}
			}
			if(!$error){
				$this->Navigation->updateWizard($this->action, null);
				//$this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'additional');
				$this->redirect(array('action' => 'additional'));
			}
		}
		$this->StudentCustomField->unbindModel(array('hasMany' => array('StudentCustomFieldOption')));

		$this->StudentCustomField->bindModel(array(
			'hasMany' => array(
				'StudentCustomFieldOption' => array(
					'conditions' => array(
						'StudentCustomFieldOption.visible' => 1),
					'order' => array('StudentCustomFieldOption.order' => "ASC")
				)
			)
		));
		$data = $this->StudentCustomField->find('all', array('conditions' => array('StudentCustomField.visible' => 1), 'order' => 'StudentCustomField.order'));
		$this->StudentCustomValue->unbindModel(array('belongsTo' => array('Student')));
		$dataValues = $this->StudentCustomValue->find('all', array('conditions' => array('StudentCustomValue.student_id' => $this->studentId)));
		$tmp = array();
		foreach ($dataValues as $arrV) {
			$tmp[$arrV['StudentCustomField']['id']][] = $arrV['StudentCustomValue'];
		}
		$dataValues = $tmp;
		$this->set('data', $data);
		$this->set('dataValues', $tmp);
	}

	public function history() {
		$this->Navigation->addCrumb('History');

		$arrTables = array('StudentHistory');
		$historyData = $this->StudentHistory->find('all', array(
			'conditions' => array('StudentHistory.student_id' => $this->studentId),
			'order' => array('StudentHistory.created' => 'desc')
		));

		// pr($historyData);
		$data = $this->Student->findById($this->studentId);
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

	/**
	* Assessments that the student has achieved till date
	* @return [type] [description]
	*/
	public function assessments() {
		$this->Navigation->addCrumb('Results');
		$header = __('Results');
		if (is_null($this->studentId)) {
			//var_dump($this->name);
			$this->redirect(array('controller' => $this->name));
		}

		$years = $this->StudentAssessment->getYears($this->studentId);
		$programmeGrades = $this->StudentAssessment->getProgrammeGrades($this->studentId);

		reset($years);
		reset($programmeGrades);

		if ($this->request->isPost()) {
			$selectedYearId = $this->request->data['year'];
			if (!$this->Session->check('Student.assessment.year')) {
				$this->Session->write('Student.assessment.year', $selectedYearId);
			}
			$isYearChanged = $this->Session->read('Student.assessment.year') !== $this->request->data['year'];

			$programmeGrades = $this->StudentAssessment->getProgrammeGrades($this->studentId, $selectedYearId);
			$selectedProgrammeGrade = $isYearChanged ? key($programmeGrades) : $this->request->data['programmeGrade'];
		} else {
			$selectedYearId = key($years);
			$selectedProgrammeGrade = key($programmeGrades);
		}

		$data = $this->StudentAssessment->getData($this->studentId, $selectedYearId, $selectedProgrammeGrade);

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
		$id = $this->studentId;
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

		//if(empty($data['dataFields'])) $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'));
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
		$studentId = !empty($this->studentId) ? $this->studentId : $this->Session->read('StudentId');
		if(empty($studentId)){
			return $this->redirect(array('controller' => 'Students', 'action' => 'index'));
		}
		//$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
		$this->Navigation->addCrumb('Absence');
		$header = __('Absence');
		
		$yearList = $this->SchoolYear->getYearList();
		//pr($yearList);
		$currentYearId = $this->SchoolYear->getSchoolYearId(date('Y'));
		if (isset($this->params['pass'][0])) {
			$yearId = $this->params['pass'][0];
			if (!array_key_exists($yearId, $yearList)) {
				$yearId = $currentYearId;
			}
		}else{
			$yearId = $currentYearId;
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
		
		$absenceData = $this->InstitutionSiteStudentAbsence->getStudentAbsenceDataByMonth($studentId, $yearId, $monthId);
		//pr($absenceData);
		$data = $absenceData;
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
			//$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
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
	
	public function generateMonthOptions() {
		$options = array();
		for ($i = 1; $i <= 12; $i++) {
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

}

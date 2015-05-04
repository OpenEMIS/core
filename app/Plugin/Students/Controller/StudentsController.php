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
		'SecurityUser',
		'AreaAdministrative',
		'InstitutionSite',
		'InstitutionSiteClass',
		'InstitutionSiteType',
		'InstitutionSiteStudentAbsence',
		'InstitutionSiteSectionStudent',
		'InstitutionSiteClassStudent',
		'Students.Student',
		'Students.StudentActivity',
		'Students.StudentCustomField',
		'Students.StudentCustomFieldOption',
		'Students.StudentCustomValue',
		'Students.StudentAssessment',
		'Students.StudentAttendanceType',
		'Students.Programme',
		'AcademicPeriod',
		'ConfigItem'
	);
	
	public $helpers = array('Js' => array('Jquery'), 'Paginator');
	public $components = array(
		'ControllerAction',
		'Paginator',
		'FileAttachment' => array(
			'model' => 'Students.StudentAttachment',
			'foreignKey' => 'student_id'
		),
		'FileUploader',
		'AccessControl',
		'Wizard',
		'Activity' => array('model' => 'StudentActivity'),
		'PhpExcel'
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
		'StudentSpecialNeed' => array('plugin' => 'Students'),
		'StudentAward' => array('plugin' => 'Students'),
		'bankAccounts' => 'Students.StudentBankAccount',
		'StudentComment' => array('plugin' => 'Students'),
		'StudentContact' => array('plugin' => 'Students'),
		'extracurricular' => 'Students.StudentExtracurricular',
		'StudentIdentity' => array('plugin' => 'Students'),
		'StudentLanguage' => array('plugin' => 'Students'),
		'StudentNationality' => array('plugin' => 'Students'),
		'attachments' =>'Students.StudentAttachment',
		'guardians' => 'Students.StudentGuardian',
		'additional' => 'Students.StudentCustomField',
		// new ControllerAction
		'InstitutionSiteStudent',
		'StudentFee' => array('plugin' => 'Students'),
		'StudentBehaviour' => array('plugin' => 'Students'),
		'Absence' => array('plugin' => 'Students'),
		'StudentSection' => array('plugin' => 'Students')
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Students', array('controller' => $this->name, 'action' => 'index'));
		$this->Wizard->setModule('Student');
		
		$actions = array('index', 'advanced', 'import', 'importTemplate', 'downloadFailed');
		if (in_array($this->action, $actions)) {
			$this->bodyTitle = __('Students');
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

		$searchKey = $this->Session->check('Student.search.key') ? $this->Session->read('Student.search.key') : '';
		if ($this->request->is(array('post', 'put'))) {
			$searchKey = Sanitize::escape($this->request->data['Student']['search']);
		}

		$IdentityType = ClassRegistry::init('IdentityType');
		$defaultIdentity = $IdentityType->find('first', array(
			'contain' => array('FieldOption'),
			'conditions' => array('FieldOption.code' => $IdentityType->alias),
			'order' => array('IdentityType.default DESC')
		));

		$conditions = array(
			'SearchKey' => $searchKey,
			'AdvancedSearch' => $this->Session->check('Student.AdvancedSearch') ? $this->Session->read('Student.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id'),
			'defaultIdentity' => $defaultIdentity['IdentityType']['id']
		);

		$order = empty($this->params->named['sort']) ? array('SecurityUser.first_name' => 'asc') : array();
		$data = $this->Search->search($this->Student, $conditions, $order);
		$data = $this->Student->attachLatestInstitutionInfo($data);
		
		if (empty($searchKey) && !$this->Session->check('Student.AdvancedSearch')) {
			if (count($data) == 1 && !$this->AccessControl->newCheck($this->params['controller'], 'add')) {
				$this->redirect(array('action' => 'view', $data[0]['Student']['id']));
			}
		}
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->set('data', $data);
		$this->set('defaultIdentity', $defaultIdentity['IdentityType']);
	}

	public function advanced() {
		$key = 'Student.AdvancedSearch';
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
		$types = $this->InstitutionSiteType->getList();
		$this->set("customfields",array($customfields));
		$this->set('types',  $types);	
		$this->set('typeSelected',  $sitetype);
		$this->set('dataFields',  $dataFields);
		$this->render('/Elements/customfields/search');
	}

	public function view($id=0) {
		if ($id == 0 && $this->Wizard->isActive()) {
			$studentIdSession = $this->Session->read('Student.id');
			if(empty($studentIdSession)){
				return $this->redirect(array('action' => 'add'));
			}
		}
		
		if ($id > 0) {
			if ($this->Student->exists($id)) {
				$this->DateTime->getConfigDateFormat();
				$this->Session->write('Student.id', $id);
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
		$this->Student->contain(array('SecurityUser' => array('Gender')));
		$data = $this->Student->findById($id);
		
		$obj = $data['Student'];
		$name = ModelHelper::getName($data['SecurityUser']);
		$obj['name'] = $name;
		$this->bodyTitle = $name;
		$this->Session->write('Student.data', $obj);
		$this->Session->write('Student.user.data', $data['SecurityUser']);
		$this->Session->write('Student.security_user_id', $obj['security_user_id']);
		$this->Navigation->addCrumb($name, array('controller' => $this->name, 'action' => 'view'));
		$this->Navigation->addCrumb('Overview');
		$this->set('data', $data);
	}

	public function add() {
		$this->SecurityUser->controller = $this;
		$this->SecurityUser->setMandatoryModel('Student');

		$this->SecurityUser->StudentContact->validator()->remove('preferred');
		$this->SecurityUser->validator()->remove('username', 'ruleRequired');

		$ConfigItem = ClassRegistry::init('ConfigItem');

		$configContacts = $ConfigItem->getOptionValue('StudentContact');

		$model = 'SecurityUser';
		$id = null;

		$this->Navigation->addCrumb('Add');
		$this->bodyTitle = __('New Student');

		$data = array();
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			if (array_key_exists('Student', $data)) {
				if (array_key_exists('birthplace_area_id', $data['Student'])) {
					if (!array_key_exists($model, $data)) {
						$data[$model] = array();
					}
					$data[$model]['birthplace_area_id'] = $data['Student']['birthplace_area_id'];
					unset($data['Student']['birthplace_area_id']);
				}
				if (array_key_exists('address_area_id', $data['Student'])) {
					if (!array_key_exists($model, $data)) {
						$data[$model] = array();
					}
					$data[$model]['address_area_id'] = $data['Student']['address_area_id'];
					unset($data['Student']['birthplace_area_id']);
				}
			}

			if (array_key_exists('submit', $data) && $data['submit'] == 'changeNationality') {
				unset($this->request->data['StudentIdentity']);
				$data = $this->request->data;
			} else {
				if ($this->SecurityUser->saveAll($data)) {
					$InstitutionSiteStudentModel = ClassRegistry::init('InstitutionSiteStudent');
					$InstitutionSiteStudentModel->validator()->remove('search');
					$dataToSite = $this->Session->read('InstitutionSiteStudent.addNew');

					$securityUserId = $this->SecurityUser->getLastInsertId();
					$this->Student->create();
					$this->Student->save(array('security_user_id' => $securityUserId));
					
					$this->Message->alert('Student.add.success');
					$id = $this->Student->getLastInsertId();
					$this->Session->write('Student.id', $id);

					if (!empty($dataToSite)) {
						$studentStatusId = $InstitutionSiteStudentModel->StudentStatus->getDefaultValue();
						$dataToSite['student_status_id'] = $studentStatusId;
						$dataToSite['student_id'] = $id;
					
						$this->InstitutionSiteSectionStudent->autoInsertSectionStudent($dataToSite);
						$InstitutionSiteStudentModel->save($dataToSite);
					} 

					$this->Session->write('Student.data', $this->Student->findById($id));
					$this->Session->write('Student.security_user_id', $securityUserId);

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
		$dataMask = $this->ConfigItem->getValue('student_identification');
		$arrIdNo = !empty($dataMask) ? array('data-mask' => $dataMask) : array();

		$Country = ClassRegistry::init('Country');
		$nationalityOptions = $Country->getOptions();

		$identityTypeOption = array();
		if (array_key_exists('StudentNationality', $this->request->data)) {
			$identityTypeOption = $this->request->data['StudentNationality'][0];
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

		$this->set('autoid', $this->Utility->getUniqueOpenemisId(array('model'=>'Student')));
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

		$studentIdSession = $this->Session->read('Student.id');	

		if (!empty($studentIdSession)) {
			$this->Navigation->addCrumb('Edit');
		} else {
			$this->Navigation->addCrumb('Add');
			$this->bodyTitle = __('New Student');
		}
		
		$data = array();
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			if (array_key_exists('Student', $data)) {
				if (array_key_exists('birthplace_area_id', $data['Student'])) {
					if (!array_key_exists($model, $data)) {
						$data[$model] = array();
					}
					$data[$model]['birthplace_area_id'] = $data['Student']['birthplace_area_id'];
					unset($data['Student']['birthplace_area_id']);
				}
				if (array_key_exists('address_area_id', $data['Student'])) {
					if (!array_key_exists($model, $data)) {
						$data[$model] = array();
					}
					$data[$model]['address_area_id'] = $data['Student']['address_area_id'];
					unset($data['Student']['birthplace_area_id']);
				}
			}

			if (array_key_exists('submit', $data) && $data['submit'] == 'changeNationality') {
				unset($this->request->data['StudentIdentity']);
			} else {
				if ($this->SecurityUser->saveAll($data)) {
					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => 'view'));
				}
			}
		} else {
			if (!empty($studentIdSession)) {
				$data = $this->Student->findById($studentIdSession);
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
		$dataMask = $this->ConfigItem->getValue('student_identification');
		$arrIdNo = !empty($dataMask) ? array('data-mask' => $dataMask) : array();
		
		$this->set('autoid', $this->Utility->getUniqueOpenemisId(array('model' => 'Student')));
		$this->set('arrIdNo', $arrIdNo);
		$this->set('genderOptions', $genderOptions);
		$this->set('data', $data);
		$this->set('model', $model);
		$this->set('addressAreaId', (isset($addressAreaId))? $addressAreaId: null);
		$this->set('birthplaceAreaId', (isset($birthplaceAreaId))? $birthplaceAreaId: null);
	}

	public function classes() {
		$this->Navigation->addCrumb(ucfirst($this->action));
		$header = __(ucfirst($this->action));
		$studentId = $this->Session->read('Student.id');
		
		$data = $this->InstitutionSiteClassStudent->getListOfClassByStudent($studentId);
		
		foreach($data as $i => $obj) {
			$classId = $obj['InstitutionSiteClassStudent']['institution_site_class_id'];
			$data[$i]['InstitutionSiteClass']['teachers'] = ClassRegistry::init('InstitutionSiteClassStaff')->getStaffs($classId, 'list');
		}
		
		if(empty($data)){
			$this->Message->alert('general.noData');
		}
		
		$this->set(compact('data', 'header'));
	}

	public function delete() {
		$id = $this->Session->read('Student.id');
		$secId = $this->Student->field('security_user_id', array('Student.id' => $id));
		
		if ($this->Student->delete($id)) {
			$this->SecurityUser->delete($secId);
		}
		
		$this->Message->alert('general.delete.success');
		return $this->redirect(array('action' => 'index'));
	}

	public function excel() {
		$this->Student->excel();
	}

	public function assessments() {
		$this->Navigation->addCrumb('Results');
		$header = __('Results');
		if (!$this->Session->check('Student.id')) {
			return $this->redirect(array('action' => 'index'));
		}
		$studentId = $this->Session->read('Student.id');
		$academicPeriods = $this->StudentAssessment->getAcademicPeriods($studentId);
		$programmeGrades = $this->StudentAssessment->getProgrammeGrades($studentId);

		reset($academicPeriods);
		reset($programmeGrades);

		if ($this->request->isPost()) {
			$selectedAcademicPeriodId = $this->request->data['academicPeriod'];
			if (!$this->Session->check('Student.assessment.academicPeriod')) {
				$this->Session->write('Student.assessment.academicPeriod', $selectedAcademicPeriodId);
			}
			$isAcademicPeriodChanged = $this->Session->read('Student.assessment.academicPeriod') !== $this->request->data['academicPeriod'];
			
			$programmeGrades = $this->StudentAssessment->getProgrammeGrades($studentId, $selectedAcademicPeriodId);
			$selectedProgrammeGrade = $isAcademicPeriodChanged ? key($programmeGrades) : $this->request->data['programmeGrade'];
		} else {
			$selectedAcademicPeriodId = key($academicPeriods);
			$selectedProgrammeGrade = key($programmeGrades);
		}
		$data = $this->StudentAssessment->getData($studentId, $selectedAcademicPeriodId, $selectedProgrammeGrade);

		if (empty($data) && empty($academicPeriods) && empty($programmeGrades)) {
			$this->Message->alert('general.noData');
		}

		$this->set('academicPeriods', $academicPeriods);
		$this->set('selectedAcademicPeriod', $selectedAcademicPeriodId);
		$this->set('programmeGrades', $programmeGrades);
		$this->set('selectedProgrammeGrade', $selectedProgrammeGrade);
		$this->set('data', $data);
		$this->set('header',$header);
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
            $id = 0;
            if (!empty($str)) {
                $id = $str['Student']['id'];
            }
            $id = $id + 1;
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
	
	public function import() {
		$this->Navigation->addCrumb('Import');
		$model = 'Student';

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
						return $this->redirect(array('controller' => 'Students', 'action' => 'import'));
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

					$this->set(compact('uploadedName', 'totalRows', 'dataFailed', 'totalImported', 'totalUpdated', 'header', 'excelFile'));
				}
			}
		}
		
		$this->set(compact('model'));
	}

	public function importTemplate(){
		$this->Student->downloadTemplate();
	}
	
	public function downloadFailed($excelFile){
		$this->Student->performDownload($excelFile);
	}

}

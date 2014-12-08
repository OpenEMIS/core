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

App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class InstitutionSitesController extends AppController {
	public $institutionSiteId;
	public $institutionSiteObj;
	public $uses = array(
		'Area',
		'AreaLevel',
		'AreaEducation',
		'AreaEducationLevel',
		'EducationSubject',
		'EducationGrade',
		'EducationGradeSubject',
		'EducationProgramme',
		'EducationFieldOfStudy',
		'EducationCertification',
		'EducationCycle',
		'EducationLevel',
		'EducationSystem',
		'AssessmentItemType',
		'AssessmentItem',
		'AssessmentItemResult',
		'AssessmentResultType',
		'InstitutionSiteClass',
		'InstitutionSiteClassSubject',
		'InstitutionSiteClassGrade',
		'InstitutionSiteCustomField',
		'InstitutionSiteCustomFieldOption',
		'InstitutionSiteCustomValue',
		'InstitutionSite',
		'InstitutionSiteHistory',
		'InstitutionSiteOwnership',
		'InstitutionSiteLocality',
		'InstitutionSiteSector',
		'InstitutionSiteStatus',
		'InstitutionSiteProgramme',
		'InstitutionSiteClassStaff',
		'InstitutionSiteType',
		'InstitutionSiteStudent',
		'InstitutionSiteStaff',
		'SchoolYear',
		'Students.Student',
		'Students.StudentCategory',
		'Students.StudentAttendance',
		'Students.StudentCustomField',
		'Students.StudentCustomFieldOption',
		'Students.StudentDetailsCustomField',
		'Students.StudentDetailsCustomFieldOption',
		'Students.StudentDetailsCustomValue',
		'Staff.Staff',
		'Staff.StaffStatus',
		'Staff.StaffAttendance',
		'Staff.StaffCategory',
		'Staff.StaffPositionTitle',
		'Staff.StaffPositionGrade',
		'Staff.StaffPositionStep',
		'Staff.StaffCustomField',
		'Staff.StaffCustomFieldOption',
		'Staff.StaffDetailsCustomField',
		'Staff.StaffDetailsCustomFieldOption',
		'Staff.StaffDetailsCustomValue',
		'SecurityGroupUser',
		'SecurityGroupArea',
		'Students.StudentAttendanceType',
		'Staff.StaffAttendanceType',
		'InstitutionSiteShift',
		'InstitutionSiteStudentAbsence'
	);
	
	public $helpers = array('Paginator');
	public $components = array(
		'Mpdf',
		'Paginator',
		'FileAttachment' => array(
			'model' => 'InstitutionSiteAttachment',
			'foreignKey' => 'institution_site_id'
		),
		'FileUploader',
		'AreaHandler'
	);
	
	public $modules = array(
		'bankAccounts' => 'InstitutionSiteBankAccount',
		'classesSubject' => 'InstitutionSiteClassSubject',
		'classesStudent' => 'InstitutionSiteClassStudent',
		'classesStaff' => 'InstitutionSiteClassStaff',
		'classes' => 'InstitutionSiteClass',
		'attachments' => 'InstitutionSiteAttachment',
		'additional' => 'InstitutionSiteCustomField',
		'shifts' => 'InstitutionSiteShift',
		'assessments' => 'AssessmentItemResult',
		'InstitutionSiteStudentFee',
		'InstitutionSiteFee',
		'InstitutionSitePosition',
		'InstitutionSiteProgramme',
		'InstitutionSiteStudentAbsence',
		'StudentBehaviour' => array('plugin' => 'Students'),
		'StaffBehaviour' => array('plugin' => 'Staff'),
		'InstitutionSiteStaffAbsence'
	);
	
	public function beforeFilter() {
		parent::beforeFilter();

		$this->Auth->allow('viewMap', 'siteProfile');

		$this->Navigation->addCrumb('Institutions', array('controller' => 'InstitutionSites', 'action' => 'index'));

		if ($this->action === 'index' || $this->action === 'add' || $this->action === 'advanced' || $this->action === 'getCustomFieldsSearch') {
			$this->bodyTitle = 'Institutions';
		} else if ($this->action === 'view'){
			
		} else {
			if ($this->action == 'siteProfile' || $this->action == 'viewMap') {
				$this->layout = 'profile';
			}
			
			if ($this->Session->check('InstitutionSiteId')) {
				$this->institutionSiteId = $this->Session->read('InstitutionSiteId');
				$this->institutionSiteObj = $this->Session->read('InstitutionSiteObj');
				$institutionSiteName = $this->InstitutionSite->field('name', array('InstitutionSite.id' => $this->institutionSiteId));
				
				if($this->action !== 'advanced'){
					$this->bodyTitle = $institutionSiteName;
					$this->Navigation->addCrumb($institutionSiteName, array('controller' => 'InstitutionSites', 'action' => 'view'));
				}
				
			} else {
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
			}
		}
	}
	
	public function index() {
		$this->AccessControl->init($this->Auth->user('id'));

		$this->Navigation->addCrumb('List of Institutions');
		if ($this->request->is('post')) {
			if (isset($this->request->data['InstitutionSite']['SearchField'])) {
				$this->request->data['InstitutionSite']['SearchField'] = Sanitize::escape(trim($this->request->data['InstitutionSite']['SearchField']));

				if ($this->request->data['InstitutionSite']['SearchField'] != $this->Session->read('InstitutionSite.search')) {
					$this->Session->delete('InstitutionSite.search');
					$this->Session->write('InstitutionSite.search', $this->request->data['InstitutionSite']['SearchField']);
				}
			}

			if (isset($this->request->data['sortdir']) && isset($this->request->data['order'])) {
				if ($this->request->data['sortdir'] != $this->Session->read('Search.sortdir')) {
					$this->Session->delete('Search.sortdir');
					$this->Session->write('Search.sortdir', $this->request->data['sortdir']);
				}
				if ($this->request->data['order'] != $this->Session->read('Search.order')) {
					$this->Session->delete('Search.order');
					$this->Session->write('Search.order', $this->request->data['order']);
				}
			}
		}

		$fieldordername = ($this->Session->read('Search.order')) ? $this->Session->read('Search.order') : 'InstitutionSite.name';
		$fieldorderdir = ($this->Session->read('Search.sortdir')) ? $this->Session->read('Search.sortdir') : 'asc';

		$searchKey = stripslashes($this->Session->read('InstitutionSite.search'));

		$conditions = array(
			'SearchKey' => $searchKey,
			'AdvancedSearch' => $this->Session->check('InstitutionSite.AdvancedSearch') ? $this->Session->read('InstitutionSite.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id'),
			'order' => array($fieldordername => $fieldorderdir)
		);

		$order = array('order' => array($fieldordername => $fieldorderdir));
		$limit = ($this->Session->read('Search.perpage')) ? $this->Session->read('Search.perpage') : 30;
		$this->Paginator->settings = array_merge(array('limit' => $limit, 'maxLimit' => 100), $order);

		$data = $this->paginate('InstitutionSite', $conditions);

		$configItem = ClassRegistry::init('ConfigItem');
		$areaLevelID = $configItem->getValue('institution_site_area_level_id');

		$data = $this->InstitutionSite->displayByAreaLevel($data, 'Area', $areaLevelID);

		if (empty($data) && !$this->request->is('ajax')) {
			$this->Message->alert('general.noData');
		}
		$this->set('institutions', $data);
		$this->set('sortedcol', $fieldordername);
		$this->set('sorteddir', ($fieldorderdir == 'asc') ? 'up' : 'down');
		$this->set('searchField', stripslashes($this->Session->read('InstitutionSite.search')));
		if ($this->request->is('post')) {
			$this->render('index_records', 'ajax');
		}
		
		// for resetting institution site id
		if (!$this->AccessControl->check($this->params['controller'], 'add') && count($data) == 1 && !$this->Session->check('InstitutionSite.search')) {
			return $this->redirect(array('action' => 'view', $data[0]['InstitutionSite']['id']));
		} else {
			$this->Session->delete('InstitutionSite.id');
		}
	}
	
	public function advanced() {
        $key = 'InstitutionSite.AdvancedSearch';
		$EducationProgramme = ClassRegistry::init('EducationProgramme');
		$educationProgrammeOptions = $EducationProgramme->findList();
		if ($this->request->is('get')) {
			if ($this->request->is('ajax')) {
				$this->autoRender = false;
				$search = $this->params->query['term'];
				$result = $this->Area->autocomplete($search);
				return json_encode($result);
			} else {
				//$this->Navigation->addCrumb('List of Institutions', array('controller' => 'InstitutionSites', 'action' => 'index'));
				$this->Navigation->addCrumb('Advanced Search');

				if (isset($this->params->pass[0])) {
					if (intval($this->params->pass[0]) === 0) {
						$this->Session->delete($key);
						$this->redirect(array('action' => 'index'));
					}
				}

				// custom fields start
				$sitetype = 0;
				if($this->Session->check('InstitutionSite.AdvancedSearch.siteType')){
					 $sitetype = $this->Session->read('InstitutionSite.AdvancedSearch.siteType');
				}
				$customfields = 'InstitutionSite';
				
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
				$dataFields[$customfields] = $instituionSiteCustField->getInstitutionSiteCustomFields();
				$types = $this->InstitutionSiteType->findList(1);
				//pr(array($customfields));
				$this->set("customfields", array($customfields));
				$this->set('types', $types);
				$this->set('typeSelected', $sitetype);
				$this->set('dataFields', $dataFields);
				//pr($dataFields);
				//$this->render('/Elements/customfields/search');
				// custom fields end
			}
		} else {

			//$search = $this->data['Search'];
			$search = $this->data;
			if (!empty($search)) {
				//pr($this->data);die;
				$this->Session->write($key, $search);
			}
			$this->redirect(array('action' => 'index'));
		}
		$this->set(compact('educationProgrammeOptions'));
	}
        
	public function getCustomFieldsSearch($sitetype = 0,$customfields = 'Institution') {
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
		$dataFields[$customfields] = $instituionSiteCustField->getInstitutionSiteCustomFields();
		$types = $this->InstitutionSiteType->findList(1);
		//pr(array($customfields));
		$this->set("customfields",array($customfields));
		$this->set('types',  $types);		
		$this->set('typeSelected',  $sitetype);
		$this->set('dataFields',  $dataFields);
		$this->render('/Elements/customfields/search');
	}

	public function view($id = 0) {
		$data = array();
		if ($id != 0) {
			$data = $this->InstitutionSite->findById($id);
			if ($data) {
				$this->Session->write('InstitutionSiteId', $id); // deprecated
				$this->Session->write('InstitutionSite.id', $id); // writing to session using array dot notation
				$this->Session->write('InstitutionSite.data', $data);
				$this->Session->write('InstitutionSiteObj', $data); // deprecated
			} else {
				return $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
			}
		} else if ($this->Session->check('InstitutionSite.id')){
			$id = $this->Session->read('InstitutionSite.id');
			$data = $this->InstitutionSite->findById($id);
			$this->Session->write('InstitutionSite.data', $data);
			$this->Session->write('InstitutionSiteObj', $data);
		} else {
			return $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}
		$this->institutionSiteId = $id;
		$this->institutionSiteObj = $data;
		
		$name = $this->Session->read('InstitutionSite.data.InstitutionSite.name');
		$this->bodyTitle = $name;
		$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'view'));
		$this->Navigation->addCrumb('Overview');
		
		$this->set('data', $data);
	}

	public function viewMap($id = false) {

		$this->layout = false;
		if ($id)
			$this->institutionSiteId = $id;
		$string = @file_get_contents('http://www.google.com');
		if ($string) {
			$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
			$this->set('data', $data);
		} else {
			$this->autoRender = false;
		}
	}

	public function edit() {
		$this->Navigation->addCrumb('Edit');
		$id = $this->Session->read('InstitutionSite.id');
		$this->InstitutionSite->id = $id;
		$data = $this->InstitutionSite->findById($id);
		
		if ($this->request->is(array('post', 'put'))) {
			$dateOpened = $this->request->data['InstitutionSite']['date_opened'];
			$dateClosed = $this->request->data['InstitutionSite']['date_closed'];
			if(!empty($dateOpened)) {
				$this->request->data['InstitutionSite']['year_opened'] = date('Y', strtotime($dateOpened));
			}
			if(!empty($dateClosed)) {
				$this->request->data['InstitutionSite']['year_closed'] = date('Y', strtotime($dateClosed));
			}
			$this->request->data['InstitutionSite']['latitude'] = trim($this->request->data['InstitutionSite']['latitude']);
			$this->request->data['InstitutionSite']['longitude'] = trim($this->request->data['InstitutionSite']['longitude']);
			$this->InstitutionSite->set($this->request->data);
			
			if ($this->InstitutionSite->validates()) {
				$result = $this->InstitutionSite->save($this->request->data);
				$this->Message->alert('general.edit.success');
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'view'));
			}
			$data = $this->request->data;
		} else {
			$this->request->data = $data;
            $this->request->data['InstitutionSite']['area_id_select'] = $data['InstitutionSite']['area_id'];
            $this->request->data['InstitutionSite']['area_education_id_select'] = $data['InstitutionSite']['area_education_id'];
		}
		$visible = true;
		$dataMask = $this->ConfigItem->getValue('institution_site_code');
		$arrCode = !empty($dataMask) ? array('data-mask' => $dataMask) : array();
		$typeOptions = $this->InstitutionSiteType->findList($visible);
		$ownershipOptions = $this->InstitutionSiteOwnership->findList($visible);
		$localityOptions = $this->InstitutionSiteLocality->findList($visible);
		$statusOptions = $this->InstitutionSiteStatus->findList($visible);
		$providerOptions = $this->InstitutionSite->InstitutionSiteProvider->getList();
		$sectorOptions = $this->InstitutionSite->InstitutionSiteSector->getList();
		$genderOptions = $this->InstitutionSite->InstitutionSiteGender->getList();

		$this->set(compact('data', 'arrCode', 'typeOptions', 'ownershipOptions', 'localityOptions', 'statusOptions', 'providerOptions', 'sectorOptions', 'genderOptions'));
	}

	public function add() {
        $this->Navigation->addCrumb('Add new Institution');

		$areaId = false;
		$areaEducationId = false;
		if ($this->request->is('post')) {
			$dateOpened = $this->request->data['InstitutionSite']['date_opened'];
			$dateClosed = $this->request->data['InstitutionSite']['date_closed'];
			if(!empty($dateOpened)) {
				$this->request->data['InstitutionSite']['year_opened'] = date('Y', strtotime($dateOpened));
			}
			if(!empty($dateClosed)) {
				$this->request->data['InstitutionSite']['year_closed'] = date('Y', strtotime($dateClosed));
			}
			$this->InstitutionSite->set($this->request->data);
			
			if ($this->InstitutionSite->validates()) {
				$newInstitutionSiteRec = $this->InstitutionSite->save($this->request->data);
				
				$institutionSiteId = $newInstitutionSiteRec['InstitutionSite']['id'];
				$this->Session->write('InstitutionSiteId', $institutionSiteId);
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'view', $institutionSiteId));
			}
			$areaId = $this->request->data['InstitutionSite']['area_id'];
			$areaEducationId = $this->request->data['InstitutionSite']['area_education_id'];
		}
		// Get security group area
		//$groupId = $this->SecurityGroupUser->getGroupIdsByUserId($this->Auth->user('id'));
		//$filterArea = $this->SecurityGroupArea->getAreas($groupId);

		$visible = true;
		$dataMask = $this->ConfigItem->getValue('institution_site_code');
		$arrCode = !empty($dataMask) ? array('data-mask' => $dataMask) : array();
		$typeOptions = $this->InstitutionSiteType->findList($visible);
		$ownershipOptions = $this->InstitutionSiteOwnership->findList($visible);
		$localityOptions = $this->InstitutionSiteLocality->findList($visible);
		$statusOptions = $this->InstitutionSiteStatus->findList($visible);
		$providerOptions = $this->InstitutionSite->InstitutionSiteProvider->getList();
		$sectorOptions = $this->InstitutionSite->InstitutionSiteSector->getList();
		$genderOptions = $this->InstitutionSite->InstitutionSiteGender->getList();
		
		//$this->set('filterArea', $filterArea);
		$this->set('arrCode', $arrCode);
		$this->set('areaId', $areaId);
		$this->set('areaEducationId', $areaEducationId);
		$this->set('typeOptions', $typeOptions);
		$this->set('ownershipOptions', $ownershipOptions);
		$this->set('localityOptions', $localityOptions);
		$this->set('statusOptions', $statusOptions);	
		$this->set('providerOptions', $providerOptions);
		$this->set('sectorOptions', $sectorOptions);
		$this->set('genderOptions', $genderOptions);
	}

	public function delete() {
		$id = $this->Session->read('InstitutionSiteId');
		$name = $this->InstitutionSite->field('name', array('InstitutionSite.id' => $id));
		//$this->InstitutionSite->delete($id);
		if($this->InstitutionSite->delete($id)){
			$this->Utility->alert($name . ' have been deleted successfully.');
		}else{
			//$this->Utility->alert($name . ' have been deleted unsuccessfully. ' . $id);
		}
		
		$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
	}
	
	public function shiftLocationAutoComplete() {
		$this->autoRender = false;
		$search = $this->params->query['term'];
		$result = $this->InstitutionSite->getAutoCompleteList($search);
		return json_encode($result);
	}

	public function history() {
		$this->Navigation->addCrumb('History');

		$arrTables = array('InstitutionSiteHistory', 'InstitutionSiteStatus', 'InstitutionSiteType', 'InstitutionSiteOwnership', 'InstitutionSiteLocality', 'Area');
		$historyData = $this->InstitutionSiteHistory->find('all', array('conditions' => array('InstitutionSiteHistory.institution_site_id' => $this->institutionSiteId), 'order' => array('InstitutionSiteHistory.created' => 'desc')));
		//pr($historyData);
		$data2 = array();
		foreach ($historyData as $key => $arrVal) {

			foreach ($arrTables as $table) {
				//pr($arrVal);die;
				foreach ($arrVal[$table] as $k => $v) {
					$keyVal = ($k == 'name') ? $table . '_name' : $k;
					$keyVal = ($k == 'code') ? $table . '_code' : $keyVal;
					//echo $k.'<br>';
					$data2[$keyVal][$v] = $arrVal['InstitutionSiteHistory']['created'];
				}
			}
		}

		if (empty($data2)) {
			$this->Utility->alert($this->Utility->getMessage('NO_HISTORY'), array('type' => 'info', 'dismissOnClick' => false));
		} else {
			$adminarealevels = $this->AreaEducationLevel->find('list', array('recursive' => 0));
			$arrEducation = array();
			foreach ($data2['area_education_id'] as $val => $time) {
				if ($val > 0) {
					$adminarea = $this->AreaHandler->getAreatoParent($val, array('AreaEducation', 'AreaEducationLevel'));
					$adminarea = array_reverse($adminarea);

					$arrVal = '';
					foreach ($adminarealevels as $levelid => $levelName) {
						$areaVal = array('id' => '0', 'name' => 'a');
						foreach ($adminarea as $arealevelid => $arrval) {
							if ($arrval['level_id'] == $levelid) {
								$areaVal = $arrval;
								$arrVal .= ($areaVal['name'] == 'a' ? '' : $areaVal['name']) . ' (' . $levelName . ') ' . ',';
								continue;
							}
						}
					}
					$arrEducation[] = array('val' => str_replace(',', ' &rarr; ', rtrim($arrVal, ',')), 'time' => $time);
				}
			}

			$myData = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
			$adminarea = $this->AreaHandler->getAreatoParent($myData['InstitutionSite']['area_education_id'], array('AreaEducation', 'AreaEducationLevel'));
			$adminarea = array_reverse($adminarea);
			$arrVal = '';
			foreach ($adminarealevels as $levelid => $levelName) {
				$areaVal = array('id' => '0', 'name' => 'a');
				foreach ($adminarea as $arealevelid => $arrval) {
					if ($arrval['level_id'] == $levelid) {
						$areaVal = $arrval;
						$arrVal .= ($areaVal['name'] == 'a' ? '' : $areaVal['name']) . ' (' . $levelName . ') ' . ',';
						continue;
					}
				}
			}
			$arrEducationVal = str_replace(',', ' &rarr; ', rtrim($arrVal, ','));
			$this->set('arrEducation', $arrEducation);
			$this->set('arrEducationVal', $arrEducationVal);
		}
		$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
		$this->set('data', $data);
		$this->set('data2', $data2);
		$this->set('id', $this->institutionSiteId);
	}

	public function classesAssessments() {
		if (isset($this->params['pass'][0])) {
			$classId = $this->params['pass'][0];
			$class = $this->InstitutionSiteClass->findById($classId);
			if ($class) {
				$class = $class['InstitutionSiteClass'];
				$this->Navigation->addCrumb($class['name'], array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
				$this->Navigation->addCrumb('Results');
				$data = $this->AssessmentItemType->getAssessmentsByClass($classId);

				if (empty($data)) {
					$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT'), array('type' => 'info'));
				}
				$this->set('classId', $classId);
				$this->set('data', $data);
			} else {
				$this->redirect(array('action' => 'classes'));
			}
		} else {
			$this->redirect(array('action' => 'classes'));
		}
	}

	public function classesResults() {
		if (count($this->params['pass']) == 2 || count($this->params['pass']) == 3) {
			$classId = $this->params['pass'][0];
			$assessmentId = $this->params['pass'][1];
			$class = $this->InstitutionSiteClass->findById($classId);
			$selectedItem = 0;
			if ($class) {
				$class = $class['InstitutionSiteClass'];
				$this->Navigation->addCrumb($class['name'], array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
				$this->Navigation->addCrumb('Results');
				$items = $this->AssessmentItem->getItemList($assessmentId);
				if (empty($items)) {
					$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT_ITEM'), array('type' => 'info'));
				} else {
					$selectedItem = isset($this->params['pass'][2]) ? $this->params['pass'][2] : key($items);
					$data = $this->InstitutionSiteClassGradeStudent->getStudentAssessmentResults($classId, $selectedItem, $assessmentId);
					if (empty($data)) {
						$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_STUDENTS'), array('type' => 'info'));
					}
					$this->set('itemOptions', $items);
					$this->set('data', $data);
				}
				$this->set('classId', $classId);
				$this->set('assessmentId', $assessmentId);
				$this->set('selectedItem', $selectedItem);
			} else {
				$this->redirect(array('action' => 'classes'));
			}
		} else {
			$this->redirect(array('action' => 'classes'));
		}
	}

	public function classesResultsEdit() {
		if (count($this->params['pass']) == 2 || count($this->params['pass']) == 3) {
			$classId = $this->params['pass'][0];
			$assessmentId = $this->params['pass'][1];
			$class = $this->InstitutionSiteClass->findById($classId);
			$selectedItem = 0;
			if ($class) {
				$class = $class['InstitutionSiteClass'];
				$this->Navigation->addCrumb($class['name'], array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
				$this->Navigation->addCrumb('Results');
				$items = $this->AssessmentItem->getItemList($assessmentId);
				if (empty($items)) {
					$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT_ITEM'), array('type' => 'info'));
				} else {
					$selectedItem = isset($this->params['pass'][2]) ? $this->params['pass'][2] : key($items);
					$data = $this->InstitutionSiteClassGradeStudent->getStudentAssessmentResults($classId, $selectedItem, $assessmentId);
					if ($this->request->is('get')) {
						if (empty($data)) {
							$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_STUDENTS'), array('type' => 'info'));
						}
						$gradingOptions = $this->AssessmentResultType->findList(true);
						$this->set('classId', $classId);
						$this->set('assessmentId', $assessmentId);
						$this->set('selectedItem', $selectedItem);
						$this->set('itemOptions', $items);
						$this->set('gradingOptions', $gradingOptions);
						$this->set('data', $data);
					} else {
						if (isset($this->data['AssessmentItemResult'])) {
							$result = $this->data['AssessmentItemResult'];
							foreach ($result as $key => &$obj) {
								$obj['assessment_item_id'] = $selectedItem;
								$obj['institution_site_id'] = $this->institutionSiteId;
							}
							if (!empty($result)) {
								$this->AssessmentItemResult->saveMany($result);
								$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
							}
						}
						$this->redirect(array('action' => 'classesResults', $classId, $assessmentId, $selectedItem));
					}
				}
				$this->set('classId', $classId);
				$this->set('assessmentId', $assessmentId);
				$this->set('selectedItem', $selectedItem);
			} else {
				$this->redirect(array('action' => 'classes'));
			}
		} else {
			$this->redirect(array('action' => 'classes'));
		}
	}
	
	//STUDENTS CUSTOM FIELD PER YEAR - STARTS - 
	public function studentsCustFieldYrInits() {
		$action = $this->action;
		$siteid = $this->institutionSiteId;
		$id = @$this->request->params['pass'][0];
		$years = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('student_id' => $id, 'institution_site_id' => $siteid, 'school_year_id' => $selectedYear);
		$arrMap = array('CustomField' => 'StudentDetailsCustomField',
			'CustomFieldOption' => 'StudentDetailsCustomFieldOption',
			'CustomValue' => 'StudentDetailsCustomValue',
			'Year' => 'SchoolYear');

		$studentId = $this->params['pass'][0];
		$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
		$name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
		$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $data['Student']['id']));
		return compact('action', 'siteid', 'id', 'years', 'selectedYear', 'condParam', 'arrMap');
	}

	public function studentsCustFieldYrView() {
		extract($this->studentsCustFieldYrInits());
		$this->Navigation->addCrumb('Academic');
		$customfield = $this->Components->load('CustomField', $arrMap);
		$data = array();
		if ($id && $selectedYear && $siteid)
			$data = $customfield->getCustomFieldView($condParam);

		$displayEdit = true;
		if (count($data['dataFields']) == 0) {
			$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'), array('type' => 'info'));
			$displayEdit = false;
		}
		$this->set(compact('arrMap', 'selectedYear', 'years', 'action', 'id', 'displayEdit'));
		$this->set($data);
		$this->set('id', $id);
		$this->set('myview', 'studentsView');
		$this->render('/Elements/customfields/view');
	}

	public function studentsCustFieldYrEdit() {
		if ($this->request->is('post')) {
			extract($this->studentsCustFieldYrInits());
			$customfield = $this->Components->load('CustomField', $arrMap);
			$cond = array('institution_site_id' => $siteid,
				'student_id' => $id,
				'school_year_id' => $selectedYear);
			$customfield->saveCustomFields($this->request->data, $cond);
			$this->redirect(array('action' => 'studentsCustFieldYrView', $id, $selectedYear));
		} else {
			$this->studentsCustFieldYrView();
			$this->render('/Elements/customfields/edit');
		}
	}

	//STUDENTS CUSTOM FIELD PER YEAR - ENDS - 
	//STAFF CUSTOM FIELD PER YEAR - STARTS - 
	public function staffCustFieldYrInits() {
		$action = $this->action;
		$siteid = $this->institutionSiteId;
		$id = @$this->request->params['pass'][0];
		$years = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('staff_id' => $id, 'institution_site_id' => $siteid, 'school_year_id' => $selectedYear);
		$arrMap = array('CustomField' => 'StaffDetailsCustomField',
			'CustomFieldOption' => 'StaffDetailsCustomFieldOption',
			'CustomValue' => 'StaffDetailsCustomValue',
			'Year' => 'SchoolYear');

		$staffId = $this->params['pass'][0];
		$data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
		$name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
		$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $data['Staff']['id']));
		return compact('action', 'siteid', 'id', 'years', 'selectedYear', 'condParam', 'arrMap');
	}

	public function staffCustFieldYrView() {
		extract($this->staffCustFieldYrInits());
		$this->Navigation->addCrumb('Academic');
		$customfield = $this->Components->load('CustomField', $arrMap);
		$data = array();
		if ($id && $selectedYear && $siteid)
			$data = $customfield->getCustomFieldView($condParam);
		$displayEdit = true;
		if (count($data['dataFields']) == 0) {
			$this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'), array('type' => 'info'));
			$displayEdit = false;
		}
		$this->set(compact('arrMap', 'selectedYear', 'years', 'action', 'id', 'displayEdit'));
		$this->set($data);
		$this->set('id', $id);
		$this->set('myview', 'staffView');
		$this->render('/Elements/customfields/view');
	}

	public function staffCustFieldYrEdit() {
		if ($this->request->is('post')) {
			extract($this->staffCustFieldYrInits());
			$customfield = $this->Components->load('CustomField', $arrMap);
			$cond = array('institution_site_id' => $siteid,
				'staff_id' => $id,
				'school_year_id' => $selectedYear);
			$customfield->saveCustomFields($this->request->data, $cond);
			$this->redirect(array('action' => 'staffCustFieldYrView', $id, $selectedYear));
		} else {
			$this->staffCustFieldYrView();
			$this->render('/Elements/customfields/edit');
		}
	}

	//STAFF CUSTOM FIELD PER YEAR - ENDS -

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

	public function siteProfile($id) {

		$levels = $this->AreaLevel->find('list', array('recursive' => 0));
		$adminarealevels = $this->AreaEducationLevel->find('list', array('recursive' => 0));
		$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $id)));

		$areaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_id']);
		$areaLevel = array_reverse($areaLevel);

		$adminarea = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_education_id'], array('AreaEducation', 'AreaEducationLevel'));
		$adminarea = array_reverse($adminarea);

		$this->set('data', $data);
		$this->set('levels', $levels);
		$this->set('adminarealevel', $adminarealevels);

		$this->set('arealevel', $areaLevel);
		$this->set('adminarea', $adminarea);
	}

	function assoc_splice($source_array, $key_name, $length, $replacement){
		return array_splice($source_array, array_search($key_name, array_keys($source_array)), $length, $replacement);
	}

	// for future reference - PDF report -  start
	public function genReport($name, $type) { //$this->genReport('Site Details','CSV');
		$this->autoRender = false;
		$this->ReportData['name'] = $name;
		$this->ReportData['type'] = $type;

		if (method_exists($this, 'gen' . $type)) {
			if ($type == 'CSV') {
				if (array_key_exists($name, $this->reportMappingAcademic)) {
					$data = $this->getReportDataAcademic($name);
					$this->genCSVAcademic($data, $name);
				} else {
					
					$data = $this->getReportData($name);
					$this->genCSV($data, $this->ReportData['name']);
				}
			} elseif ($type == 'PDF') {
				$data = $this->genReportPDF($this->ReportData['name']);
				$data['name'] = $this->ReportData['name'];
				$this->genPDF($data);
			}
		}
	}

	public function genReportPDF($name) {
		if ($name == 'Overview and More') {
			$profileurl = Router::url(array('controller' => 'InstitutionSites', 'action' => 'siteProfile', $this->institutionSiteId), true);
			$html = file_get_contents($profileurl);
			$html = str_replace('common.css', '', $html);
			$stylesheet = file_get_contents(WWW_ROOT . 'css/mpdf.css');
			$data = compact('html', 'stylesheet');
		}
		return $data;
	}

	public function genPDF($arrData) {
		// initializing mPDF
		$this->Mpdf->init();
		$this->Mpdf->showImageErrors = false; //for debugging
		$this->Mpdf->WriteHTML($arrData['stylesheet'], 1);
		$this->Mpdf->WriteHTML($arrData['html']);
		$this->Mpdf->Output($arrData['name'] . '.pdf', 'I');
	}
	// for future reference - PDF report -  end
	
	public function getFirstWeekdayBySetting(){
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
		
		$firstWeekday = $settingFirstWeekDay;
		
		return $firstWeekday;
	}
	
	public function getLastWeekdayBySetting(){
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
		
		$newIndex = ($firstWeekdayIndex + $settingDaysPerWek - 1) % 7;
		
		if($newIndex == 0){
			$lastWeekday = $weekdaysArr[7];
		}else{
			$lastWeekday = $weekdaysArr[$newIndex];
		}
		
		return $lastWeekday;
	}
	
	public function getWeekListByYearId($yearId, $forOptions=true){
		$settingFirstWeekDay = $this->getFirstWeekdayBySetting();
		$lastWeekDay = $this->getLastWeekdayBySetting();
		
		$currentDate = date("Y-m-d");
		
		//$yearName = $this->SchoolYear->getSchoolYearById($yearId);
		$schoolYearObject = $this->SchoolYear->getSchoolYearObjectById($yearId);
		//pr($schoolYearObject);
		$yearName = $schoolYearObject['name'];
		$startDateOfSchoolYear = $schoolYearObject['start_date'];
		$endDateOfSchoolYear = $schoolYearObject['end_date'];
		$stampFirstDayOfYear = strtotime($startDateOfSchoolYear);
		//pr($stampFirstDayOfYear);
		$stampEndDateOfSchoolYear = strtotime($endDateOfSchoolYear);
		//$stampFirstDayOfYear = mktime(0, 0, 0, 1, 1, $yearName);
		
		$stampFirstWeekDay = strtotime($settingFirstWeekDay, $stampFirstDayOfYear);
		//pr($stampFirstWeekDay);
		$stampLastWeekDay = strtotime($lastWeekDay, $stampFirstWeekDay);
		
		//$dateFirstWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampFirstWeekDay));
		//$dateLastWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampLastWeekDay));
		
		$stampNextFirstWeekDay = $stampFirstWeekDay;
		$stampNextLastWeekDay = $stampLastWeekDay;

		$weekList = array();
		if($stampFirstDayOfYear === $stampFirstWeekDay){
			$startingIndexWeek = 1;
		}else{
			$stampPrevFirstWeekDay = strtotime('-1 week', $stampNextFirstWeekDay);
			$stampPrevLastWeekDay = strtotime('-1 week', $stampNextLastWeekDay);
			$datePrevFirstWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampPrevFirstWeekDay));
			$datePrevLastWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampPrevLastWeekDay));
			
			//if(date('Y', $stampPrevLastWeekDay) === $yearName){
			if($stampPrevLastWeekDay >= $stampFirstDayOfYear){
				$startingIndexWeek = 2;
				if($forOptions){
					if($currentDate >= date("Y-m-d", $stampPrevFirstWeekDay) && $currentDate <= date("Y-m-d", $stampPrevLastWeekDay)){
						$weekList[1] = sprintf('Current Week (%s - %s)', $datePrevFirstWeekDay, $datePrevLastWeekDay);
					}else{
						$weekList[1] = sprintf('Week 1 (%s - %s)', $datePrevFirstWeekDay, $datePrevLastWeekDay);
					}
				}else{
					$weekList[1]['start_date'] = date("Y-m-d", $stampPrevFirstWeekDay);
					$weekList[1]['end_date'] = date("Y-m-d", $stampPrevLastWeekDay);
					$weekList[1]['label'] = sprintf('Week 1 (%s - %s)', $datePrevFirstWeekDay, $datePrevLastWeekDay);
				}
			}else{
				$startingIndexWeek = 1;
			}
		}
		//pr($startingIndexWeek);
		//while(date('Y', $stampNextFirstWeekDay) == $yearName){
		//pr($stampNextFirstWeekDay);
		//pr($stampEndDateOfSchoolYear);
		while($stampNextFirstWeekDay <= $stampEndDateOfSchoolYear){
			$dateNextFirstWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampNextFirstWeekDay));
			$dateNextLastWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampNextLastWeekDay));
			
			if($forOptions){
				if($currentDate >= date("Y-m-d", $stampNextFirstWeekDay) && $currentDate <= date("Y-m-d", $stampNextLastWeekDay)){
					$weekList[$startingIndexWeek] = sprintf('Current Week (%s - %s)', $dateNextFirstWeekDay, $dateNextLastWeekDay);
				}else{
					$weekList[$startingIndexWeek] = sprintf('Week %d (%s - %s)', $startingIndexWeek, $dateNextFirstWeekDay, $dateNextLastWeekDay);
				}
			}else{
				$weekList[$startingIndexWeek]['start_date'] = date("Y-m-d", $stampNextFirstWeekDay);
				$weekList[$startingIndexWeek]['end_date'] = date("Y-m-d", $stampNextLastWeekDay);
				$weekList[$startingIndexWeek]['label'] = sprintf('Week %d (%s - %s)', $startingIndexWeek, $dateNextFirstWeekDay, $dateNextLastWeekDay);
			}
			
			$stampNextFirstWeekDay = strtotime('+1 week', $stampNextFirstWeekDay);
			$stampNextLastWeekDay = strtotime('+1 week', $stampNextLastWeekDay);
			$startingIndexWeek ++;
		}

		return $weekList;
	}
	
	public function getStartEndDateByYearWeek($yearId, $weekId){
		$weekList = $this->getWeekListByYearId($yearId, false);
		if(isset($weekList[$weekId])){
			return $weekList[$weekId];
		}else{
			return NULL;
		}
		
	}
	
	public function getCurrentWeekId($yearId){
		$weekList = $this->getWeekListByYearId($yearId, false);
		$currentDate = date("Y-m-d");
		$currentWeekId = key($weekList);
		foreach($weekList AS $id => $week){
			$startDate = $week['start_date'];
			$endDate = $week['end_date'];
			if(($currentDate >= $startDate && $currentDate <= $endDate) || ($currentDate <= $startDate)){
				$currentWeekId = $id;
				break;
			}
		}
		
		return $currentWeekId;
	}
	
	public function generateAttendanceHeader($startDate, $endDate){
		$header = array(__('ID'), __('Name'));
		
		$firstDate = $startDate;
		while($firstDate <= $endDate){
			$stampStartDate = strtotime($firstDate);
			$header[] = __(date('D', $stampStartDate));
			$stampStartDateNew = strtotime('+1 day', $stampStartDate);
			$firstDate = date("Y-m-d", $stampStartDateNew);
		}
		
		return $header;
	}
	
	public function generateAttendanceWeekDayIndex($startDate, $endDate){
		$index = array();
		
		$firstDate = $startDate;
		while($firstDate <= $endDate){
			$stampStartDate = strtotime($firstDate);
			$index[] = date('Ymd', $stampStartDate);
			$stampStartDateNew = strtotime('+1 day', $stampStartDate);
			$firstDate = date("Y-m-d", $stampStartDateNew);
		}
		
		return $index;
	}
	
}

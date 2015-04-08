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
App::import('Vendor', 'php-excel-reader/excel_reader2');

class InstitutionSitesController extends AppController {
	public $institutionSiteId;
	public $institutionSiteObj;
	private $indexPage;
	public $uses = array(
		'Area',
		'AreaLevel',
		'AreaAdministrative',
		'AreaAdministrativeLevel',
		'EducationSubject',
		'EducationGrade',
		'EducationGradeSubject',
		'EducationProgramme',
		'InstitutionSite',
		'InstitutionSiteActivity',
		'InstitutionSiteClassSubject',
		'InstitutionSiteClassStudent',
		'InstitutionSiteCustomField',
		'InstitutionSiteCustomFieldOption',
		'InstitutionSiteCustomValue',
		'InstitutionSiteOwnership',
		'InstitutionSiteLocality',
		'InstitutionSiteSector',
		'InstitutionSiteStatus',
		'InstitutionSiteProgramme',
		'InstitutionSiteClassStaff',
		'InstitutionSiteType',
		'InstitutionSiteStudent',
		'InstitutionSiteStaff',
		'AcademicPeriod',
		'Students.Student',
		'Staff.Staff',
		'Staff.StaffStatus',
		'Staff.StaffCategory',
		'SecurityGroupUser',
		'SecurityGroupArea',
		'InstitutionSiteShift',
		'InstitutionSiteSectionStudent'
	);
	
	public $helpers = array('Paginator', 'Model');
	public $components = array(
		'ControllerAction',
		'Mpdf',
		'Paginator',
		'FileAttachment' => array(
			'model' => 'InstitutionSiteAttachment',
			'foreignKey' => 'institution_site_id'
		),
		'FileUploader',
		'AreaHandler',
		'Alert',
		'Activity' => array('model' => 'InstitutionSiteActivity'),
		'HighCharts.HighCharts',
		'PhpExcel'
	);
	
	public $modules = array(
		'bankAccounts' => 'InstitutionSiteBankAccount',
		'attachments' => 'InstitutionSiteAttachment',
		'additional' => 'InstitutionSiteCustomField',
		'shifts' => 'InstitutionSiteShift',
		'assessments' => 'AssessmentItemResult',
		'InstitutionSiteStudentFee',
		'InstitutionSiteFee',
		'InstitutionSitePosition',
		'InstitutionSiteStudentAttendance',
		'InstitutionSiteStudentAbsence',
		'StudentBehaviour' => array('plugin' => 'Students'),
		'StaffBehaviour' => array('plugin' => 'Staff'),
		'InstitutionSiteStaffAttendance',
		'InstitutionSiteStaffAbsence',
		'InstitutionSiteClass',
		'InstitutionSiteSection',
		'InstitutionSiteSectionStudent',
		'InstitutionSiteSectionStaff',
		'InstitutionSiteInfrastructure',
		'InstitutionSiteSurveyNew',
		'InstitutionSiteSurveyDraft',
		'InstitutionSiteSurveyCompleted'
	);
	
	public function beforeFilter() {
		parent::beforeFilter();

		$this->Auth->allow('viewMap', 'siteProfile');
		$this->Navigation->addCrumb('Institutions', array('controller' => 'InstitutionSites', 'action' => 'index'));
		$this->indexPage = 'dashboard';
		if ($this->action === 'index' || $this->action === 'add' || $this->action === 'advanced' || $this->action === 'getCustomFieldsSearch') {
			$this->bodyTitle = 'Institutions';
		} else if ($this->action === 'view' || $this->action === 'dashboard') {
			
		} else if ($this->action === 'import' || $this->action === 'importProcess'){
			
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
					$this->Navigation->addCrumb($institutionSiteName, array('controller' => 'InstitutionSites', 'action' => $this->indexPage));
				}
				
			} else {
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
			}
		}
	}
	
	public function index() {
		$this->AccessControl->init($this->Auth->user('id'));
		$this->Navigation->addCrumb('List of Institutions');

		$searchKey = $this->Session->check('InstitutionSite.search.key') ? $this->Session->read('InstitutionSite.search.key') : '';
		if ($this->request->is(array('post', 'put'))) {
			$searchKey = Sanitize::escape($this->request->data['InstitutionSite']['search']);
		}

		$conditions = array(
			'SearchKey' => $searchKey,
			'AdvancedSearch' => $this->Session->check('InstitutionSite.AdvancedSearch') ? $this->Session->read('InstitutionSite.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id')
		);

		$order = empty($this->params->named['sort']) ? array('InstitutionSite.name' => 'asc') : array();
		$data = $this->Search->search($this->InstitutionSite, $conditions, $order);

		$configItem = ClassRegistry::init('ConfigItem');
		$areaLevelID = $configItem->getValue('institution_site_area_level_id');

		$data = $this->InstitutionSite->displayByAreaLevel($data, 'Area', $areaLevelID);

		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->set('data', $data);

		// for resetting institution site id
		if (!$this->AccessControl->check($this->params['controller'], 'add') && count($data) == 1 && !$this->Session->check('InstitutionSite.search')) {
			return $this->redirect(array('action' => $this->indexPage, $data[0]['InstitutionSite']['id']));
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
				$types = $this->InstitutionSiteType->getList();
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
		$types = $this->InstitutionSiteType->getList();
		$this->set("customfields",array($customfields));
		$this->set('types',  $types);		
		$this->set('typeSelected',  $sitetype);
		$this->set('dataFields',  $dataFields);
		$this->render('/Elements/customfields/search');
	}

	public function dashboard($id = 0) {
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
		} else if ($this->Session->check('InstitutionSite.id')) {
			$id = $this->Session->read('InstitutionSite.id');
			$data = $this->InstitutionSite->findById($id);
			$this->Session->write('InstitutionSite.data', $data);
			$this->Session->write('InstitutionSiteObj', $data);
		} else {
			return $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}

		if($this->checkUserAccess($id, 'dashboard')) {
			$this->institutionSiteId = $id;
			$this->institutionSiteObj = $data;
			
			$name = $this->Session->read('InstitutionSite.data.InstitutionSite.name');
			$this->bodyTitle = $name;
			$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => $this->indexPage));
			$this->Navigation->addCrumb('Dashboard');
			$contentHeader = __('Dashboard');

			$highChartDatas = array();
			//Students By Year
			$params = array(
				'conditions' => array('institution_site_id' => $id)
			);
			$highChartDatas[] = $this->InstitutionSiteStudent->getHighChart('number_of_students_by_year', $params);
			
			//Students By Grade for current year
			$params = array(
				'conditions' => array('institution_site_id' => $id)
			);
			$highChartDatas[] = $this->InstitutionSiteSectionStudent->getHighChart('number_of_students_by_grade', $params);

			//Staffs By Position for current year
			$params = array(
				'conditions' => array('institution_site_id' => $id)
			);
			$highChartDatas[] = $this->InstitutionSiteStaff->getHighChart('number_of_staff', $params);

			$this->set('highChartDatas', $highChartDatas);
			$this->set('contentHeader', $contentHeader);
		} else {
			$this->Utility->alert($this->Utility->getMessage('SITE_NO_VIEW_ACCESS'), array('type' => 'warn'));
			return $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}
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
		} else if ($this->Session->check('InstitutionSite.id')) {
			$id = $this->Session->read('InstitutionSite.id');
			$data = $this->InstitutionSite->findById($id);
			$this->Session->write('InstitutionSite.data', $data);
			$this->Session->write('InstitutionSiteObj', $data);
		} else {
			return $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}
		if($this->checkUserAccess($id, 'view')) {
			$this->institutionSiteId = $id;
			$this->institutionSiteObj = $data;
			
			$name = $this->Session->read('InstitutionSite.data.InstitutionSite.name');
			$this->bodyTitle = $name;
			$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => $this->indexPage));
			$this->Navigation->addCrumb('Overview');
			
			$this->set('data', $data);
		} else {
			$this->Utility->alert($this->Utility->getMessage('SITE_NO_VIEW_ACCESS'), array('type' => 'warn'));
			return $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}
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
		
		if($this->checkUserAccess($id, 'edit')) {
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
					$this->redirect(array('controller' => 'InstitutionSites', 'action' => $this->indexPage));
				}
				$data = $this->request->data;
			} else {
				$this->request->data = $data;
	            $this->request->data['InstitutionSite']['area_id_select'] = $data['InstitutionSite']['area_id'];
	            $this->request->data['InstitutionSite']['area_administrative_id_select'] = $data['InstitutionSite']['area_administrative_id'];
			}
			$dataMask = $this->ConfigItem->getValue('institution_site_code');
			$arrCode = !empty($dataMask) ? array('data-mask' => $dataMask) : array();
			
			$statusOptions = $this->InstitutionSite->InstitutionSiteStatus->getList(array('value' => $data['InstitutionSite']['institution_site_status_id']));
			$localityOptions = $this->InstitutionSite->InstitutionSiteLocality->getList(array('value' => $data['InstitutionSite']['institution_site_locality_id']));
			$ownershipOptions = $this->InstitutionSite->InstitutionSiteOwnership->getList(array('value' => $data['InstitutionSite']['institution_site_ownership_id']));
			$typeOptions = $this->InstitutionSite->InstitutionSiteType->getList(array('value' => $data['InstitutionSite']['institution_site_type_id']));
			$providerOptions = $this->InstitutionSite->InstitutionSiteProvider->getList(array('value' => $data['InstitutionSite']['institution_site_provider_id']));
			$sectorOptions = $this->InstitutionSite->InstitutionSiteSector->getList(array('value' => $data['InstitutionSite']['institution_site_sector_id']));
			$genderOptions = $this->InstitutionSite->InstitutionSiteGender->getList(array('value' => $data['InstitutionSite']['institution_site_gender_id']));

			$this->set(compact('data', 'arrCode', 'typeOptions', 'ownershipOptions', 'localityOptions', 'statusOptions', 'providerOptions', 'sectorOptions', 'genderOptions'));
		} else {
			$this->Utility->alert($this->Utility->getMessage('SITE_NO_EDIT_ACCESS'), array('type' => 'warn'));
			return $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}		
	}

	public function add() {
        $this->Navigation->addCrumb('Add new Institution');

		$areaId = false;
		$areaAdministrativeId = false;
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
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => $this->indexPage, $institutionSiteId));
			}
			$areaId = $this->request->data['InstitutionSite']['area_id'];
			$areaAdministrativeId = $this->request->data['InstitutionSite']['area_administrative_id'];
		}
		// Get security group area
		//$groupId = $this->SecurityGroupUser->getGroupIdsByUserId($this->Auth->user('id'));
		//$filterArea = $this->SecurityGroupArea->getAreas($groupId);

		$dataMask = $this->ConfigItem->getValue('institution_site_code');
		$arrCode = !empty($dataMask) ? array('data-mask' => $dataMask) : array();
		$typeOptions = $this->InstitutionSiteType->getList(array('visibleOnly' => 1));
		$ownershipOptions = $this->InstitutionSiteOwnership->getList(array('visibleOnly' => 1));
		$localityOptions = $this->InstitutionSiteLocality->getList(array('visibleOnly' => 1));
		$statusOptions = $this->InstitutionSiteStatus->getList(array('visibleOnly' => 1));
		$providerOptions = $this->InstitutionSite->InstitutionSiteProvider->getList(array('visibleOnly' => 1));
		$sectorOptions = $this->InstitutionSite->InstitutionSiteSector->getList(array('visibleOnly' => 1));
		$genderOptions = $this->InstitutionSite->InstitutionSiteGender->getList(array('visibleOnly' => 1));
		
		//$this->set('filterArea', $filterArea);
		$this->set('arrCode', $arrCode);
		$this->set('areaId', $areaId);
		$this->set('areaAdministrativeId', $areaAdministrativeId);
		$this->set('typeOptions', $typeOptions);
		$this->set('ownershipOptions', $ownershipOptions);
		$this->set('localityOptions', $localityOptions);
		$this->set('statusOptions', $statusOptions);	
		$this->set('providerOptions', $providerOptions);
		$this->set('sectorOptions', $sectorOptions);
		$this->set('genderOptions', $genderOptions);
	}

	public function delete() {
		$id = $this->Session->read('InstitutionSite.id');
		
		if($this->checkUserAccess($id, 'delete')) {
			$name = $this->InstitutionSite->field('name', array('InstitutionSite.id' => $id));
			//$this->InstitutionSite->delete($id);
			if($this->InstitutionSite->delete($id)){
				$this->Utility->alert($name . ' have been deleted successfully.');
			}else{
				$this->log($this->InstitutionSite->validationErrors, 'debug');
				//$this->Utility->alert($name . ' have been deleted unsuccessfully. ' . $id);
			}
			
			$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		} else {
			$this->Utility->alert($this->Utility->getMessage('SITE_NO_DELETE_ACCESS'), array('type' => 'warn'));
			return $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}
	}

	public function excel() {
		$this->InstitutionSite->excel();
	}
	
	public function shiftLocationAutoComplete() {
		$this->autoRender = false;
		$search = $this->params->query['term'];
		$result = $this->InstitutionSite->getAutoCompleteList($search);
		return json_encode($result);
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

	public function siteProfile($id) {

		$levels = $this->AreaLevel->find('list', array('recursive' => 0));
		$adminarealevels = $this->AreaAdministrativeLevel->find('list', array('recursive' => 0));
		$data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $id)));

		$areaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_id']);
		$areaLevel = array_reverse($areaLevel);

		$adminarea = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_administrative_id'], array('AreaAdministrative', 'AreaAdministrativeLevel'));
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
	
	public function getWeekListByAcademicPeriodId($academicPeriodId, $forOptions=true){
		$settingFirstWeekDay = $this->getFirstWeekdayBySetting();
		$lastWeekDay = $this->getLastWeekdayBySetting();
		
		$currentDate = date("Y-m-d");
		
		$academicPeriodObject = $this->AcademicPeriod->getAcademicPeriodObjectById($academicPeriodId);
		$academicPeriodName = $academicPeriodObject['name'];
		$startDateOfAcademicPeriod = $academicPeriodObject['start_date'];
		$endDateOfAcademicPeriod = $academicPeriodObject['end_date'];
		$stampFirstDayOfAcademicPeriod = strtotime($startDateOfAcademicPeriod);
		//pr($stampFirstDayOfAcademicPeriod);
		$stampEndDateOfAcademicPeriod = strtotime($endDateOfAcademicPeriod);
		//$stampFirstDayOfAcademicPeriod = mktime(0, 0, 0, 1, 1, $academicPeriodName);
		
		$stampFirstWeekDay = strtotime($settingFirstWeekDay, $stampFirstDayOfAcademicPeriod);
		//pr($stampFirstWeekDay);
		$stampLastWeekDay = strtotime($lastWeekDay, $stampFirstWeekDay);
		
		//$dateFirstWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampFirstWeekDay));
		//$dateLastWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampLastWeekDay));
		
		$stampNextFirstWeekDay = $stampFirstWeekDay;
		$stampNextLastWeekDay = $stampLastWeekDay;

		$weekList = array();
		if($stampFirstDayOfAcademicPeriod === $stampFirstWeekDay){
			$startingIndexWeek = 1;
		}else{
			$stampPrevFirstWeekDay = strtotime('-1 week', $stampNextFirstWeekDay);
			$stampPrevLastWeekDay = strtotime('-1 week', $stampNextLastWeekDay);
			$datePrevFirstWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampPrevFirstWeekDay));
			$datePrevLastWeekDay = $this->DateTime->formatDateByConfig(date("Y-m-d", $stampPrevLastWeekDay));
			
			//if(date('Y', $stampPrevLastWeekDay) === $academicPeriodName){
			if($stampPrevLastWeekDay >= $stampFirstDayOfAcademicPeriod){
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
		//while(date('Y', $stampNextFirstWeekDay) == $academicPeriodName){
		//pr($stampNextFirstWeekDay);
		//pr($stampEndDateOfAcademicPeriod);
		while($stampNextFirstWeekDay <= $stampEndDateOfAcademicPeriod){
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
	
	public function getStartEndDateByAcademicPeriodWeek($academicPeriodId, $weekId){
		$weekList = $this->getWeekListByAcademicPeriodId($academicPeriodId, false);
		if(isset($weekList[$weekId])){
			return $weekList[$weekId];
		}else{
			return NULL;
		}
		
	}
	
	public function getCurrentWeekId($academicPeriodId){
		$weekList = $this->getWeekListByAcademicPeriodId($academicPeriodId, false);
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
		$header = array_merge($header, $this->generateAttendanceHeaderDates($startDate, $endDate));
		return $header;
	}

	public function generateAttendanceHeaderDates($startDate, $endDate) {
		$header = array();

		$firstDate = $startDate;
		while($firstDate <= $endDate){
			$stampStartDate = strtotime($firstDate);
			$header[] = __(date('D', $stampStartDate));
			$stampStartDateNew = strtotime('+1 day', $stampStartDate);
			$firstDate = date("Y-m-d", $stampStartDateNew);
		}

		return $header;
	}

	public function generateAttendanceDayHeader() {
		return array(__('ID'), __('Name'), __('Type'), __('Reason'));
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
	
	public function getDayViewAttendanceOptions() {
		return array(__('Present'), __('Absent').' - '.__('Excused'), __('Absent').' - '.__('Unexcused'));
	}
	
	public function generateMonthsByDates($startDate, $endDate) {
		$result = array();
		$stampStartDay = strtotime($startDate);
		$stampEndDay = strtotime($endDate);
		$stampToday = strtotime(date('Y-m-d'));
		
		$stampFirstDayOfMonth = strtotime('01-' . date('m', $stampStartDay) . '-' . date('Y', $stampStartDay));
		while($stampFirstDayOfMonth <= $stampEndDay && $stampFirstDayOfMonth <= $stampToday){
			$monthString = date('F', $stampFirstDayOfMonth);
			$monthNumber = date('m', $stampFirstDayOfMonth);
			$year = date('Y', $stampFirstDayOfMonth);
			
			$result[] = array(
				'month' => array('inNumber' => $monthNumber, 'inString' => $monthString),
				'year' => $year
			);
			
			$stampFirstDayOfMonth = strtotime('+1 month', $stampFirstDayOfMonth);
		}
		
		return $result;
	}
	
	public function generateDaysOfMonth($year, $month, $startDate, $endDate){
		$days = array();
		$stampStartDay = strtotime($startDate);
		//pr($startDate);
		$stampEndDay = strtotime($endDate);
		//pr($endDate);
		$stampToday = strtotime(date('Y-m-d'));
		
		$stampFirstDayOfMonth = strtotime($year . '-' . $month . '-01');
		//pr($year . '-' . $month . '-01');
		$stampFirstDayNextMonth = strtotime('+1 month', $stampFirstDayOfMonth);
		//pr(date('Y-m-d', $stampFirstDayNextMonth));
		
		
		if($stampFirstDayOfMonth <= $stampStartDay){
			$tempStamp = $stampStartDay;
		}else{
			$tempStamp = $stampFirstDayOfMonth;
		}
		//pr(date('Y-m-d', $tempStamp));
		
		while($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth && $tempStamp < $stampToday){
			$weekDay = date('l', $tempStamp);
			$date = date('Y-m-d', $tempStamp);
			$day = date('d', $tempStamp);
			
			$days[] = array(
				'weekDay' => $weekDay,
				'date' => $date,
				'day' => $day
			);
			
			$tempStamp = strtotime('+1 day', $tempStamp);
		}

		return $days;
	}

	public function checkUserAccess($institutionSiteId, $action) {
		if ($institutionSiteId != 0 || $this->Session->check('InstitutionSite.id')) {
			$institutionSiteId = ($institutionSiteId != 0) ? $institutionSiteId : $this->Session->read('InstitutionSite.id');
			return $this->AccessControl->newCheck('InstitutionSites', $action, $institutionSiteId);
		} else {
			return false;
		}
	}
	
	public function import(){
		if ($this->request->is(array('post', 'put'))) {
			if(!empty($this->request->data['InstitutionSite']['excel'])){
				$header = $this->getExcelHeader();
				$fielObj = $this->request->data['InstitutionSite']['excel'];
				if($fielObj['error'] == 0){
					$uploaded = $fielObj['tmp_name'];
					//$content = new Spreadsheet_Excel_Reader($uploaded, true);
					//$contentArr = $content->dumptoarray();
					
					$content = array();
					$objPHPExcel = $this->PhpExcel->loadWorksheet($uploaded);
					$sheets = $objPHPExcel->getWorksheetIterator();
					$firstSheetOnly = false;
					foreach($sheets as $sheet){
						if(!$firstSheetOnly){
							$highestRow         = $sheet->getHighestRow(); // e.g. 10
							$highestColumn      = $sheet->getHighestColumn(); // e.g 'F'
							$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
							for ($row = 1; $row <= $highestRow; ++ $row) {
								$tempRow = array();
								 for ($col = 0; $col < $highestColumnIndex; ++ $col) {
									 $cell = $sheet->getCellByColumnAndRow($col, $row);
									 $val = $cell->getValue();
									 $tempRow[] = $val;
								 }
								 $content[] = $tempRow;
							}
						}
						
						$firstSheetOnly = true;
					}
					pr($content);die;
					
					$data = array();
					$dataFailed = array();
					$totalRows = count($contentArr) - 1;
					$totalFailed = 0;
					//pr($contentArr);die;
					foreach($contentArr as $key => $row){
						if($key == 1){continue;}

						$formattedRow = $this->formatExcelRow($row);
						//pr($formattedRow);
						$data[] = $formattedRow;
						$this->InstitutionSite->set($formattedRow);
						$this->InstitutionSite->validator()->remove('area_id_select');
						if ($this->InstitutionSite->validates()) {
//							if($this->InstitutionSite->save($formattedRow)){
//								pr('success');
//							}else{
//								pr('Saving Failed');
//							}
						}else{
							$dataFailed[] = array('error' => $this->InstitutionSite->validationErrors, 'data' => $formattedRow);
							$totalFailed ++;
							$this->log($this->InstitutionSite->validationErrors, 'debug');
							
						}
					}
					
					$this->set(compact('data', 'totalRows', 'header', 'dataFailed'));
				}
			}
		}
		//pr($data);die;
		$model = 'InstitutionSite';
		$this->set(compact('model'));
	}
	
	private function formatExcelRow($row){
		$newRow = array();
		
		$mapping = $this->getExcelMapping();
		foreach($mapping as $key => $value){
			$newKey = $key + 1;
			$column = $value['column'];
			$newRow[$column] = $row[$newKey];
		}
		
		return $newRow;
	}
	
	private function getExcelHeader(){
		$model = 'InstitutionSite';
		$header = array();
		
		$mapping = $this->getExcelMapping();
		foreach($mapping as $key => $value){
			$label = $this->Option->getLabel(sprintf('%s.%s', $model, $value['column']));
			if(!empty($label)){
				$headerCol = $label;
			}else if(!empty($value['header'])){
				$headerCol = __($value['header']);
			}else{
				$headerCol = Inflector::humanize($value['column']);
			}
			$header[] = $headerCol;
		}
		
		return $header;
	}
	
	private function getExcelMapping(){
		$mapping = array(
			array('column' => 'name'),
			array('column' => 'alternative_name'),
			array('column' => 'code'),
			array('column' => 'address'),
			array('column' => 'postal_code'),
			array('column' => 'contact_person'),
			array('column' => 'telephone'),
			array('column' => 'fax'),
			array('column' => 'email'),
			array('column' => 'website'),
			array('column' => 'date_opened'),
			array('column' => 'year_opened'),
			array('column' => 'date_closed'),
			array('column' => 'year_closed'),
			array('column' => 'longitude'),
			array('column' => 'latitude'),
			array('column' => 'area_id'),
			array('column' => 'area_administrative_id', 'header' => ''),
			array('column' => 'institution_site_locality_id', 'header' => ''),
			array('column' => 'institution_site_type_id', 'header' => ''),
			array('column' => 'institution_site_ownership_id', 'header' => ''),
			array('column' => 'institution_site_status_id', 'header' => ''),
			array('column' => 'institution_site_sector_id', 'header' => ''),
			array('column' => 'institution_site_provider_id', 'header' => ''),
			array('column' => 'institution_site_gender_id', 'header' => '')
		);
		
		return $mapping;
	}
	
	public function generateExcelTemplate(){
		
	}
	
	public function importProcess(){
		
	}

}

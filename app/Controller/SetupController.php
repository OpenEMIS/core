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

class SetupController extends AppController {
	public $uses = Array(
		'Institution',
		'InstitutionSite',
		'InstitutionSiteType',
		'InstitutionCustomField',
		'InstitutionCustomFieldOption',
		'InstitutionSiteCustomField',
		'InstitutionSiteCustomFieldOption',
		'CensusCustomField',
		'CensusCustomFieldOption',
		'CensusGrid',
		'CensusGridXCategory',
		'CensusGridYCategory',
		'InfrastructureCategory',
		'Students.Student',
		'Students.StudentStatus',
		'Students.StudentCategory',
		'Students.StudentBehaviourCategory',
		'Teachers.Teacher',
		'Teachers.TeacherStatus',
		'Teachers.TeacherCategory',
                'Teachers.TeacherPositionTitle',
                'Teachers.TeacherPositionGrade',
                'Teachers.TeacherPositionStep',
		'Teachers.TeacherTrainingCategory',
		'Teachers.TeacherLeaveType',
		'Teachers.TeacherBehaviourCategory',
		'Staff.Staff',
                'Staff.StaffPositionTitle',
                'Staff.StaffPositionGrade',
                'Staff.StaffPositionStep',
		'Staff.StaffStatus',
		'SchoolYear',
		'Students.StudentCustomFieldOption',
		'Students.StudentCustomField',
		'Teachers.TeacherCustomFieldOption',
		'Teachers.TeacherCustomField',
		'Staff.StaffCustomFieldOption',
		'Staff.StaffCustomField',
		'Staff.StaffLeaveType',
		'Staff.StaffBehaviourCategory',
		'Bank',
		'BankBranch',
		'FinanceNature',
		'FinanceType',
		'FinanceCategory',
		'FinanceSource',
		'StudentDetailsCustomField',
		'StudentDetailsCustomFieldOption',
		'TeacherDetailsCustomField',
		'TeacherDetailsCustomFieldOption',
		'StaffDetailsCustomField',
		'StaffDetailsCustomFieldOption',
		'QualificationLevel',
		'QualificationInstitution',
		'QualificationSpecialisation',
		'LeaveStatus',
		'Country',
		'IdentityType',
		'Language',
		'ContactType',
		'ExtracurricularType',
		'EmploymentType',
		'SalaryAdditionType',
		'SalaryDeductionType',
		'HealthCondition',
		'HealthRelationship',
		'HealthImmunization',
		'HealthAllergyType',
		'HealthConsultationType',
		'HealthTestType',
                'QualityVisitType',
		'SpecialNeedType',
		'LicenseType',
		'TrainingCourseType',
		'TrainingFieldStudy',
		'TrainingLevel',
		'TrainingModeDelivery',
		'TrainingPriority',
		'TrainingProvider',
		'TrainingRequirement',
		'TrainingStatus'
	);

	
	private $CustomFieldModelLists = array(
		'InstitutionCustomField' => array('hasSiteType' => false, 'label' => 'Institution Custom Fields'),
		'InstitutionSiteCustomField' => array('hasSiteType' => true, 'label' => 'Institution Site Custom Fields'), 
		'CensusCustomField' => array('hasSiteType' => true, 'label' => 'Census Custom Fields'),
		'StudentCustomField' => array('hasSiteType' => false, 'label' => 'Student Custom Fields'),
		'StudentDetailsCustomField' => array('hasSiteType' => false, 'label' => 'Student Details Custom Fields'),
		'TeacherCustomField' => array('hasSiteType' => false, 'label' => 'Teacher Custom Fields'),
		'TeacherDetailsCustomField' => array('hasSiteType' => false, 'label' => 'Teacher Details Custom Fields'),
		'StaffCustomField' => array('hasSiteType' => false, 'label' => 'Staff Custom Fields'),
		'StaffDetailsCustomField' => array('hasSiteType' => false, 'label' => 'Staff Details Custom Fields'),
		'CensusGrid' => array('hasSiteType' => true, 'label' => 'Census Custom Tables')
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Setup', 'action' => 'index'));
	}
	
	public function index() {
		$this->redirect(array('controller' => 'Areas', 'action' => 'index'));
	}
	
	private function getLookupVariables($index=false) {
		$lookup = array();
		
		$lookup[] = array('Institution' => array(
			'optgroup' => true,
			'name' => 'Static Fields',
			'items' => $this->Institution->getLookupVariables()
		));
		$lookup[] = array('Institution' => array(
			'viewMethod' => array('action' => 'customFields', 'InstitutionCustomField'),
			'view' => 'customFields',
			'editMethod' => array('action' => 'customFieldsEdit', 'InstitutionCustomField'),
			'edit' => 'customFieldsEdit',
			'optgroup' => true,
			'name' => 'Custom Fields'
		));
		
		$lookup[] = array('Institution Site' => array(
			'optgroup' => true,
			'name' => 'Static Fields',
			'items' => $this->InstitutionSite->getLookupVariables()
		));
		$lookup[] = array('Institution Site' => array(
			'viewMethod' => array('action' => 'customFields', 'InstitutionSiteCustomField'),
			'view' => 'customFields',
			'editMethod' => array('action' => 'customFieldsEdit', 'InstitutionSiteCustomField'),
			'edit' => 'customFieldsEdit',
			'optgroup' => true,
			'name' => 'Custom Fields'
		));
		$lookup[] = array('Institution Site' => array(
			'viewMethod' => array('action' => 'customFields', 'StudentDetailsCustomField'),
			'view' => 'customFields',
			'editMethod' => array('action' => 'customFieldsEdit', 'StudentDetailsCustomField'),
			'edit' => 'customFieldsEdit',
			'optgroup' => true,
			'name' => 'Student Custom Fields (Academic)'
		));
		
		$lookup[] = array('Institution Site' => array(
			'viewMethod' => array('action' => 'customFields', 'TeacherDetailsCustomField'),
			'view' => 'customFields',
			'editMethod' => array('action' => 'customFieldsEdit', 'TeacherDetailsCustomField'),
			'edit' => 'customFieldsEdit',
			'optgroup' => true,
			'name' => 'Teacher Custom Fields  (Academic)'
		));
		
		
		$lookup[] = array('Institution Site' => array(
			'viewMethod' => array('action' => 'customFields', 'StaffDetailsCustomField'),
			'view' => 'customFields',
			'editMethod' => array('action' => 'customFieldsEdit', 'StaffDetailsCustomField'),
			'edit' => 'customFieldsEdit',
			'optgroup' => true,
			'name' => 'Staff Custom Fields (Academic)'
		));
                
                //Quality
                $lookup[] = array('Quality' => array(
			'optgroup' => true,
			'name' => 'Visit Types',
			'items' => $this->QualityVisitType->getLookupVariables()
		));
		
		// Census
		$lookup[] = array('Institution Site Totals' => array(
			'viewMethod' => array('action' => 'customFields', 'CensusCustomField'),
			'view' => 'customFields',
			'editMethod' => array('action' => 'customFieldsEdit', 'CensusCustomField'),
			'edit' => 'customFieldsEdit',
			'optgroup' => true,
			'name' => 'Custom Fields'
		));
		$lookup[] = array('Institution Site Totals' => array(
			'viewMethod' => array('action' => 'customTables'),
			'view' => 'customTables',
			'editMethod' => array('action' => 'customTablesEdit'),
			'edit' => 'customTablesEdit',
			'optgroup' => true,
			'name' => 'Custom Tables'
		));
		// End Census
		
		// Infrastructure 
		$lookup[] = array('Infrastructure' => array(
			'optgroup' => true,
			'nameEditable' => false,
			'allowAdd' => false,
			'name' => 'Categories',
			'items' => $this->InfrastructureCategory->getLookupVariables()
		));
		
		$infraCategory = $this->InfrastructureCategory->findList(true);
		foreach($infraCategory as $cat) {
			$categoryModel = 'Infrastructure' . Inflector::singularize($cat);
			$categoryModelObj = ClassRegistry::init($categoryModel);
			$lookup[] = array('Infrastructure' => array('optgroup' => true, 'name' => $cat, 'items' => $categoryModelObj->getLookupVariables()));
		}
		// End Infrastructure
		
		// Banks & Branches
		$lookup[] = array('Bank Account' => array(
			'view' => 'banks',
			'edit' => 'banks_edit',
			'optgroup' => true,
			'name' => 'Banks',
			'items' => $this->Bank->getLookupVariables()
		));
		
		$lookup[] = array('Bank Account' => array(
			'view' => 'banks',
			'edit' => 'banks_edit',
			'optgroup' => true, 
			'name' => 'Branches',
			'items' => $this->BankBranch->getLookupVariables()
		));
		// End Banks & Branches
		
		// Finances
		$lookup[] = array('Finances' => array(
			'optgroup' => true,
			'name' => 'Nature',
			'items' => $this->FinanceNature->getLookupVariables()
		));
		
		$lookup[] = array('Finances' => array(
			'optgroup' => true,
			'name' => 'Types',
			'items' => $this->FinanceType->getLookupVariables()
		));
		
		$lookup[] = array('Finances' => array(
			'view' => 'finance_categories',
			'edit' => 'finance_categories_edit',
			'optgroup' => true,
			'name' => 'Categories',
			'items' => $this->FinanceCategory->getLookupVariables()
		));
		
		$lookup[] = array('Finances' => array(
			'optgroup' => true,
			'name' => 'Sources',
			'items' => $this->FinanceSource->getLookupVariables()
		));
		// End Finances
		
		$lookup[] = array('School Year' => array(
			'view' => 'school_year',
			'edit' => 'school_year_edit',
			'items' => $this->SchoolYear->getLookupVariables()
		));

		$lookup[] = array('Assessment' => array(
			'items' => array('Result Type' => array('model' => 'AssessmentResultType'))
		));

		$lookup[] = array('Countries' => array(
			'items' =>  $this->Country->getLookupVariables()
		));

		$lookup[] = array('Identity Types' => array(
			'items' => $this->IdentityType->getLookupVariables()
		));

		$lookup[] = array('Languages' => array(
			'items' => $this->Language->getLookupVariables()
		));
		$lookup[] = array('Contact Types' => array(
			'view' => 'contact_types',
			'items' => $this->ContactType->getLookupVariables(),
			'edit' => 'contact_types_edit',
		));


		$lookup[] = array('Employment Types' => array(
			'items' => $this->EmploymentType->getLookupVariables(),
		));

		
		$lookup[] = array('Extracurricular Type' => array('items' => $this->ExtracurricularType->getLookupVariables()));
		$lookup[] = array('Salary Addition Type' => array('items' => $this->SalaryAdditionType->getLookupVariables()));
		$lookup[] = array('Salary Deduction Type' => array('items' => $this->SalaryDeductionType->getLookupVariables()));
		$lookup[] = array('Special Need Type' => array('items' => $this->SpecialNeedType->getLookupVariables()));
		$lookup[] = array('License Type' => array('items' => $this->LicenseType->getLookupVariables()));
		
		$lookup[] = array('Health' => array(
			'optgroup' => true,
			'name' => 'Relationships',
			'items' => $this->HealthRelationship->getLookupVariables()
		));
		
		$lookup[] = array('Health' => array(
			'optgroup' => true,
			'name' => 'Conditions',
			'items' => $this->HealthCondition->getLookupVariables()
		));
		
		$lookup[] = array('Health' => array(
			'optgroup' => true,
			'name' => 'Immunizations',
			'items' => $this->HealthImmunization->getLookupVariables()
		));
		
		$lookup[] = array('Health' => array(
			'optgroup' => true,
			'name' => 'Allergy Types',
			'items' => $this->HealthAllergyType->getLookupVariables()
		));
		
		$lookup[] = array('Health' => array(
			'optgroup' => true,
			'name' => 'Test Types',
			'items' => $this->HealthTestType->getLookupVariables()
		));
		
		$lookup[] = array('Health' => array(
			'optgroup' => true,
			'name' => 'Consultation Types',
			'items' => $this->HealthConsultationType->getLookupVariables()
		));


		$lookup[] = array('Training' => array(
			'optgroup' => true,
			'name' => 'Course Types',
			'items' => $this->TrainingCourseType->getLookupVariables()
		));

		$lookup[] = array('Training' => array(
			'optgroup' => true,
			'name' => 'Field of Studies',
			'items' => $this->TrainingFieldStudy->getLookupVariables()
		));

		$lookup[] = array('Training' => array(
			'optgroup' => true,
			'name' => 'Levels',
			'items' => $this->TrainingLevel->getLookupVariables()
		));
		
		$lookup[] = array('Training' => array(
			'optgroup' => true,
			'name' => 'Mode of Deliveries',
			'items' => $this->TrainingModeDelivery->getLookupVariables()
		));

		$lookup[] = array('Training' => array(
			'optgroup' => true,
			'name' => 'Priorities',
			'items' => $this->TrainingPriority->getLookupVariables()
		));

		$lookup[] = array('Training' => array(
			'optgroup' => true,
			'name' => 'Providers',
			'items' => $this->TrainingProvider->getLookupVariables()
		));
		
		
		$lookup[] = array('Training' => array(
			'optgroup' => true,
			'name' => 'Requirements',
			'items' => $this->TrainingRequirement->getLookupVariables()
		));

		$lookup[] = array('Training' => array(
			'optgroup' => true,
			'name' => 'Statuses',
			'items' => $this->TrainingStatus->getLookupVariables()
		));
		


		// Student
		//$lookup[] = array('Student' => array('optgroup' => true, 'name' => 'Status', 'items' => $this->StudentStatus->getLookupVariables()));
		$lookup[] = array('Student' => array('optgroup' => true, 'name' => 'Category', 'items' => $this->StudentCategory->getLookupVariables()));
		$lookup[] = array('Student' => array('optgroup' => true, 'name' => 'Behaviour Category', 'items' => $this->StudentBehaviourCategory->getLookupVariables()));
		
		

		$lookup[] = array('Student' => array(
			'viewMethod' => array('action' => 'customFields', 'StudentCustomField'),
			'view' => 'customFields',
			'editMethod' => array('action' => 'customFieldsEdit', 'StudentCustomField'),
			'edit' => 'customFieldsEdit',
			'optgroup' => true,
			'name' => 'Custom Fields'
		));
		
		// End Student
		
		// Teacher
		$teacherOptions = array(
			'Status' => $this->TeacherStatus,
			'Position Types' => $this->TeacherCategory,
                        'Position Titles' => $this->TeacherPositionTitle,
                        'Position Grades' => $this->TeacherPositionGrade,
                        'Position Steps' => $this->TeacherPositionStep,
			'Qualification Levels' => $this->QualificationLevel,
			'Qualification Specialisation' => $this->QualificationSpecialisation,
			'Qualification Institutions' => $this->QualificationInstitution,
			'Training Categories' => $this->TeacherTrainingCategory,
			'Leave Types' => $this->TeacherLeaveType,
			'Leave Statuses' => $this->LeaveStatus,
		);
		
		foreach($teacherOptions as $name => $model) {
			$lookup[] = array('Teacher' => array('optgroup' => true, 'name' => $name, 'items' => $model->getLookupVariables()));
		}
		$lookup[] = array('Teacher' => array(
			'viewMethod' => array('action' => 'customFields', 'TeacherCustomField'),
			'view' => 'customFields',
			'editMethod' => array('action' => 'customFieldsEdit', 'TeacherCustomField'),
			'edit' => 'customFieldsEdit',
			'optgroup' => true,
			'name' => 'Custom Fields'
		));
		$lookup[] = array('Teacher' => array(
			'optgroup' => true,
			'name' => 'Behaviour Category',
			'items' => $this->TeacherBehaviourCategory->getLookupVariables()
		));
		// End Teacher
		
		// Staff
		$staffOptions = array(
			'Status' => $this->StaffStatus,
			'Position Types' => $this->Staff,
                        'Position Titles' => $this->StaffPositionTitle,
                        'Position Grades' => $this->StaffPositionGrade,
                        'Position Steps' => $this->StaffPositionStep,
			'Qualification Levels' => $this->QualificationLevel,
			'Qualification Specialisation' => $this->QualificationSpecialisation,
			'Qualification Institutions' => $this->QualificationInstitution,
			'Leave Types' => $this->StaffLeaveType,
			'Leave Statuses' => $this->LeaveStatus,
		);
		
		foreach($staffOptions as $name => $model) {
			$lookup[] = array('Staff' => array('optgroup' => true, 'name' => $name, 'items' => $model->getLookupVariables()));
		}

		$lookup[] = array('Staff' => array(
			'viewMethod' => array('action' => 'customFields', 'StaffCustomField'),
			'view' => 'customFields',
			'editMethod' => array('action' => 'customFieldsEdit', 'StaffCustomField'),
			'edit' => 'customFieldsEdit',
			'optgroup' => true,
			'name' => 'Custom Fields'
		));
		$lookup[] = array('Staff' => array(
			'optgroup' => true,
			'name' => 'Behaviour Category',
			'items' => $this->StaffBehaviourCategory->getLookupVariables()
		));
		// End Staff
		
                
		$categoryList = array();
		
		foreach($lookup as $i => &$category) {
			$categoryValues = current($category);
			if(isset($categoryValues['optgroup'])) {
				$categoryList[__(key($category))][$i] = __($categoryValues['name']);
			} else {
				$categoryList[$i] = __(key($category));
			}
			foreach($category as &$type) {
				if(isset($type['items']) && $index==$i) {
					foreach($type['items'] as &$obj) {
						if(!isset($obj['options'])) {
							if(isset($obj['model'])) {
								$modelObj = ClassRegistry::init($obj['model']);
								$conditions = isset($obj['conditions']) ? $obj['conditions'] : array();
								$obj['options'] = $modelObj->findOptions(array('conditions' => $conditions));
							}
						}
					}
				}
			}
		}
		$lookup['list'] = $categoryList;
		return $lookup;
	}
	
	public function setupVariables() {
		
		$this->Navigation->addCrumb('Field Options');
		
		$categoryId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : 0;
		$lookup = $this->getLookupVariables($categoryId);
		$category = $lookup[$categoryId];
		$header = __(key($category));
		$categoryValues = current($category);
		
		if(isset($categoryValues['optgroup']) && $categoryValues['optgroup']) {
			$header .= ' - ' . __($categoryValues['name']);
		}
		if(isset($categoryValues['viewMethod'])) {
			$params = $categoryValues['viewMethod'];
			$action = $params['action'];
			unset($params['action']);
			if(count($this->params['pass']) <= 1) {
				array_unshift($params, $categoryId);
			} else {
				$params = $this->params['pass'];
			}
			call_user_func_array(array($this, $action), $params);
		}
		
		$this->set('selectedCategory', $categoryId);
		$this->set('categoryList', $lookup['list']);
		$this->set('category', $category);
		$this->set('header', $header);
		
		if(isset($categoryValues['view'])) {
			$this->render($categoryValues['view']);
		}
	}
	
	public function setupVariablesEdit() {
		if($this->request->is('get')) {
			$header = 'Field Options';
			$this->Navigation->addCrumb('Edit Field Options');
			
			$categoryId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : 0;
			$lookup = $this->getLookupVariables($categoryId);
			$category = $lookup[$categoryId];
			$header = __(key($category));
			$categoryValues = current($category);
			
			if(isset($categoryValues['optgroup']) && $categoryValues['optgroup']) {
				$header .= ' - ' . __($categoryValues['name']);
			}
			if(isset($categoryValues['editMethod'])) {
				$params = $categoryValues['viewMethod'];
				$action = $params['action'];
				unset($params['action']);
				if(count($this->params['pass']) <= 1) {
					array_unshift($params, $categoryId);
				} else {
					$params = $this->params['pass'];
				}
				call_user_func_array(array($this, $action), $params);
			}
			
			$isNameEditable = isset($categoryValues['nameEditable']) ? $categoryValues['nameEditable'] : true;
			$isAddAllowed = isset($categoryValues['allowAdd']) ? $categoryValues['allowAdd'] : true;
			$this->set('isNameEditable', $isNameEditable);
			$this->set('isAddAllowed', $isAddAllowed);
			$this->set('selectedCategory', $categoryId);
			$this->set('categoryList', $lookup['list']);
			$this->set('category', $category);
			$this->set('header', $header);
			
			if(isset($categoryValues['edit'])) {
				$this->render($categoryValues['edit']);
			}
		} else {
			$data = $this->data;
			$categoryId = $data['SetupVariables']['category'];
			unset($data['SetupVariables']);
			
			foreach($data as $model => $dataValues) {
				$modelObj = ClassRegistry::init($model);
				foreach($dataValues as $key => $record) {
					if(isset($record['name']) && strlen(trim($record['name']))==0) {
						unset($dataValues[$key]);
					}
				}
				$modelObj->saveMany($dataValues);
			}
			$this->redirect(array('controller' => 'Setup', 'action' => 'setupVariables', $categoryId));
		}
	}
	
	public function setupVariablesAddRow() {
		$this->layout = 'ajax';
		$model = $this->params->query['model'];
		$order = $this->params->query['order'] + 1;
		$index = $this->params->query['index'];
		$conditions = isset($this->params->query['conditions']) ? $this->params->query['conditions'] : array();
		
		$this->set('params', array($model, $order, $index, $conditions));
	}
	
	public function setupVariablesAddYear() {
		$this->layout = 'ajax';
		$model = 'SchoolYear';
		$index = $this->params->query['index'] + 1;
		$this->set('params', array($model, $index));
	}
	
	public function setupVariablesAddBank() {
		$this->layout = 'ajax';
		$model = $this->params->query['model'];
		$order = $this->params->query['order'] + 1;
		$index = $this->params->query['index'];
		$conditions = isset($this->params->query['conditions']) ? $this->params->query['conditions'] : array();
		
		$this->set('params', array($model, $order, $index, $conditions));
	}

	public function setupVariablesAddContactType() {
		$this->layout = 'ajax';
		$model = $this->params->query['model'];
		$order = $this->params->query['order'] + 1;
		$index = $this->params->query['index'];
		$conditions = isset($this->params->query['conditions']) ? $this->params->query['conditions'] : array();
		
		$this->set('params', array($model, $order, $index, $conditions));
	}

	public function setupVariablesAddTrainingCreditHour() {
		$this->layout = 'ajax';
		$model = $this->params->query['model'];
		$order = $this->params->query['order'] + 1;
		$index = $this->params->query['index'];
		$conditions = isset($this->params->query['conditions']) ? $this->params->query['conditions'] : array();
		
		$this->set('params', array($model, $order, $index, $conditions));
	}
	
	private function getCustomFieldData($model,$sitetype){
		$cond = ($sitetype != '')
			  ? array('conditions'=>array('institution_site_type_id' => $sitetype),'order'=>'order') 
			  : array('order'=>'order');
		$this->{$model}->bindModel(array('hasMany'=>array($model.'Option' => array('order'=> 'order'))));
		return $data = $this->{$model}->find('all',$cond);
	}
	
	public function customFields($category, $model = 'InstitutionCustomField', $sitetype = '') {
		$ref_id = Inflector::underscore($model);
		$ref_id = strtolower($ref_id)."_id";
		$siteTypes = array();
		
		if($this->CustomFieldModelLists[$model]['hasSiteType'])	{
			$siteTypes = $this->InstitutionSiteType->getSiteTypesList();
			if($sitetype == '')$sitetype = key($siteTypes); // initialize to first key if sitetype is '' and not institution custom field
		}else{
			$sitetype = '';
		}
		
		$data = $this->getCustomFieldData($model,$sitetype);
		$this->set('data',$data);
		$this->set('siteTypes',$siteTypes);
		$this->set('sitetype',$sitetype);
		$this->set('defaultModel',$model);
		$this->set('referenceId',$ref_id);//needed for CustomField Options
		$this->set('CustomFieldModelLists',$this->CustomFieldModelLists);
	}
	
	public function customFieldsEdit($category, $model = 'InstitutionCustomField', $sitetype = '') {
		if($this->request->is('post')) {
			$this->{$model}->saveMany($this->request->data[$model]);
			$option = $model.'Option';
			if(isset($this->request->data[$option])){
				$this->{$option}->saveMany($this->request->data[$option]);
			}
			$redirect = array('controller'=>'Setup', 'action'=>'setupVariables', $category, $model);
			if(isset($this->request->data['CustomFields']['institution_site_type_id'])) {
				$redirect[] = $this->request->data['CustomFields']['institution_site_type_id'];
				//$redirect = array_merge($redirect, array($this->request->data['CustomFields']['institution_site_type_id']));
			}
			$this->redirect($redirect);//customFields/InstitutionSiteCustomField/8
		}
		$ref_id = Inflector::underscore($model);
		$ref_id = strtolower($ref_id)."_id";
		
		$siteTypes = array();
		if($this->CustomFieldModelLists[$model]['hasSiteType'])	{
			$siteTypes = $this->InstitutionSiteType->getSiteTypesList();
			if($sitetype == '')$sitetype = key($siteTypes); // initialize to first key if sitetype is '' and not institution custom field
			
		}else{
			$sitetype = '';
		}
		$data = $this->getCustomFieldData($model,$sitetype);
		$this->set('data',$data);
		$this->set('siteTypes',$siteTypes);
		$this->set('sitetype',$sitetype);
		$this->set('defaultModel',$model);
		$this->set('referenceId',$ref_id);//needed for CustomField Options
		$this->set('CustomFieldModelLists',$this->CustomFieldModelLists);
	}
	
	public function customFieldsAdd() {
		$this->layout = 'ajax';
		$type = $this->params->query['type'];
		$model = $this->params->query['model'];
		$order = $this->params->query['order'] + 1;
		$field = $this->params->query['field'];
		$siteType = $this->params->query['siteType'];
		$arrFields = array(
			'name' => __('Field Label'), 
			'order' => $order,
			'type' => $type
		);
		if($this->CustomFieldModelLists[$model]['hasSiteType']) {
			$arrFields['institution_site_type_id'] = $siteType;
		}
		$lastInsertedId = $this->{$model}->save($arrFields);
		$customfieldid = $lastInsertedId[$model]['id'];
		$this->set('params', array($type, $model, $order, $field, $siteType, $customfieldid));
	}
	
	public function customFieldsDelete($model,$id) {
		$this->autoRender = false;
		if ($this->request->is('get')) {
            throw new MethodNotAllowedException();
        }
        $this->{$model}->id = $id;
        if ($this->{$model}->delete($id)) {
			$message = __('Record Deleted!', true);

        }else{
			$message = __('Error Occured. Please, try again.', true);
        }
        if($this->RequestHandler->isAjax()){
			$this->autoRender = false;
			$this->layout = 'ajax';
			echo json_encode(compact('message'));
        }
	}
	
	public function customFieldsAddOption() {
		$this->layout = 'ajax';
		$model = $this->params->query['model'];
		$order = $this->params->query['order'] + 1;
		$field = $this->params->query['field'];
		$fieldId = $this->params->query['fieldId'];
		$this->set('params', array($model, $order, $field, $fieldId));
	}
	
	public function customTables($category, $siteType = '') {
		$siteTypes = $this->InstitutionSiteType->getSiteTypesList();

        if(empty($siteType)  && $siteType != 0) {
            $siteType = key($siteTypes);
        }elseif($siteType == 0 ) {
            $siteType = 0;
        }
		
		$this->CensusGrid->unbindModel(array('belongsTo' => array('CensusGridXCategory','CensusGridYCategory')));
		$data = $this->CensusGrid->find('all', array(
			'recursive' => 0,
			'conditions' => array('institution_site_type_id' => $siteType),
			'order' => array('CensusGrid.order')
		));

		$this->set('siteTypes', $siteTypes);
		$this->set('siteType', $siteType);
		$this->set('data', $data);
		$this->set('CustomFieldModelLists', $this->CustomFieldModelLists);
	}
	
	public function customTablesEdit($category, $siteType = '') {
        $this->Navigation->addCrumb('Edit Custom Table');

        $siteTypes = $this->InstitutionSiteType->getSiteTypesList();
        if(empty($siteType) && $siteType != 0 ) {
            $sitetype = key($siteTypes);
        }
		
        if($this->request->is('post')) {
            $this->autoRender = false;
            foreach($this->data as $model => $arrContent){
                $this->{$model}->saveAll($arrContent);
            }
            $this->redirect(array('action'=>'setupVariables', $category, $siteType));
        }

        $this->CensusGrid->unbindModel(array('belongsTo' => array('CensusGridXCategory','CensusGridYCategory')));

		$data = $this->CensusGrid->find('all', array(
			'recursive' => 0,
			'conditions' => array('institution_site_type_id' => $siteType),
			'order' => array('CensusGrid.order')
		));

        $this->set('siteTypes', $siteTypes);
        $this->set('siteType', $siteType);
        $this->set('data', $data);
        $this->set('CustomFieldModelLists',$this->CustomFieldModelLists);
	}
	
	public function customTablesEditDetail($category, $siteType, $id = '') {
		if(empty($category) || (empty($siteType) && $siteType != 0 )) {
			$this->redirect(array('action' => 'setupVariables'));
		}
		$this->Navigation->addCrumb('Edit Field Options');

		$arr = array('X','Y');
		if($this->request->is('post')) {

			if($this->data['CensusGrid']['id'] == ''){
				$lastInserted = $this->CensusGrid->save($this->data['CensusGrid']);
				$lastInsertId = $lastInserted['CensusGrid']['id'];

				foreach($arr as $val){
					foreach($this->request->data['CensusGrid'.$val.'Category'] as $k => &$arrCVal){
						$arrCVal['census_grid_id'] = $lastInsertId;
					}
					$model = 'CensusGrid'.$val.'Category';
					$this->{$model}->saveAll($this->request->data['CensusGrid'.$val.'Category']);
				}

				$id = $lastInsertId;
			}else{
				foreach($this->data as $model => $arrContent){
					$this->{$model}->saveAll($arrContent);
				}
			}
			//$this->redirect(array('action'=>'CustomTables',$this->data['CensusGrid']['institution_site_type_id']));
			$this->Utility->alert($this->Utility->getMessage('CONFIG_SAVED'));
			$this->redirect(array('action'=>'customTablesEditDetail', $category, $siteType, $id));
		}

		$siteTypes = $this->InstitutionSiteType->getSiteTypesList();
		$data = $this->CensusGrid->findById($id);
		if(!$data){ // for Add
			$data['CensusGrid']['institution_site_type_id'] = 0;
			$data['CensusGridYCategory'][] = array('name'=>'value','order'=>0,'visible' => 1,'census_grid_id'=>'0');
			$data['CensusGridXCategory'][] = array('name'=>'value','order'=>0,'visible' => 1,'census_grid_id'=>'0');
		}
		foreach($arr as $val){
			$tmp = Array();

			//pr($data['CensusGridYCategory']);die;
			foreach ($data['CensusGrid'.$val.'Category'] as $k => $arrV){
				$tmp[$arrV['order']] = $arrV;
			}

			ksort($tmp);
			$data['CensusGrid'.$val.'Category'] = $tmp;

		}

		$this->set('id',$id);
		$this->set('data',$data);
		$this->set('siteTypes',$siteTypes);
		$this->set('siteType', $siteType);
		$this->set('CustomFieldModelLists',$this->CustomFieldModelLists);
		$this->set('selectedCategory', $category);
	}
} 

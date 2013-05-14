<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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
		'Teachers.Teacher',
		'Teachers.TeacherCategory',
		'Teachers.TeacherQualificationCategory',
		'Teachers.TeacherQualificationCertificate',
		'Teachers.TeacherQualificationInstitution',
		'Teachers.TeacherTrainingCategory',
		'Staff.Staff',
		'SchoolYear',
		'Students.StudentCustomFieldOption',
		'Students.StudentCustomField',
		'Teachers.TeacherCustomFieldOption',
		'Teachers.TeacherCustomField',
		'Staff.StaffCustomFieldOption',
		'Staff.StaffCustomField',
		'Bank',
		'BankBranch',
		'FinanceNature',
		'FinanceType',
		'FinanceCategory',
		'FinanceSource'
	);
	
	private $CustomFieldModelLists = array(
		'InstitutionCustomField' => array('hasSiteType' => false, 'label' => 'Institution Custom Fields'),
		'InstitutionSiteCustomField' => array('hasSiteType' => true, 'label' => 'Institution Site Custom Fields'),
		'CensusCustomField' => array('hasSiteType' => true, 'label' => 'Census Custom Fields'),
		'StudentCustomField' => array('hasSiteType' => false, 'label' => 'Student Custom Fields'),
		'TeacherCustomField' => array('hasSiteType' => false, 'label' => 'Teacher Custom Fields'),
		'StaffCustomField' => array('hasSiteType' => false, 'label' => 'Staff Custom Fields'),
		'CensusGrid' => array('hasSiteType' => false, 'label' => 'Census Custom Tables')
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Settings';
		$this->Navigation->addCrumb('Settings', array('controller' => 'Setup', 'action' => 'index'));
	}
	
	public function index() {
		$this->redirect(array('controller' => 'Areas', 'action' => 'index'));
	}
	
	private function getLookupVariables($index=false) {
		$lookup = array();
		
		$lookup[] = array('Institution' => array('items' => $this->Institution->getLookupVariables()));
		$lookup[] = array('Institution Site' => array('items' => $this->InstitutionSite->getLookupVariables()));
		
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
		
		$lookup[] = array('Student' => array('nameEditable' => false, 'items' => $this->Student->getLookupVariables()));
		
		// Teacher
		$teacherOptions = array(
			'Categories' => $this->TeacherCategory,
			'Qualification Categories' => $this->TeacherQualificationCategory,
			'Qualification Certificates' => $this->TeacherQualificationCertificate,
			'Qualification Institutions' => $this->TeacherQualificationInstitution,
			'Training Categories' => $this->TeacherTrainingCategory
		);
		
		foreach($teacherOptions as $name => $model) {
			$lookup[] = array('Teacher' => array('optgroup' => true, 'name' => $name, 'items' => $model->getLookupVariables()));
		}
		// End Teacher
		
		$lookup[] = array('Staff' => array('items' => $this->Staff->getLookupVariables()));
		
		$categoryList = array();
		
		foreach($lookup as $i => &$category) {
			$categoryValues = current($category);
			if(isset($categoryValues['optgroup'])) {
				$categoryItems = $categoryValues['items'];
				$categoryList[__(key($category))][$i] = __($categoryValues['name']);
			} else {
				$categoryList[$i] = __(key($category));
			}
			if($index==$i) {
				foreach($category as &$type) {
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
		$this->Navigation->addCrumb('Setup Variables');
		
		$categoryId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : 0;
		$lookup = $this->getLookupVariables($categoryId);
		$category = $lookup[$categoryId];
		
		$this->set('selectedCategory', $categoryId);
		$this->set('categoryList', $lookup['list']);
		$this->set('category', $category);
		
		$categoryValues = current($category);
		if(isset($categoryValues['view'])) {
			$this->render($categoryValues['view']);
		}
	}
	
	public function setupVariablesEdit() {
		if($this->request->is('get')) {
			$this->Navigation->addCrumb('Edit Setup Variables');
			
			$categoryId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : 0;
			$lookup = $this->getLookupVariables($categoryId);
			$category = $lookup[$categoryId];
			
			$this->set('selectedCategory', $categoryId);
			$this->set('categoryList', $lookup['list']);
			$this->set('category', $category);
			
			$categoryValues = current($category);
			if(isset($categoryValues['edit'])) {
				$this->render($categoryValues['edit']);
			}
			$isNameEditable = isset($categoryValues['nameEditable']) ? $categoryValues['nameEditable'] : true;
			$isAddAllowed = isset($categoryValues['allowAdd']) ? $categoryValues['allowAdd'] : true;
			$this->set('isNameEditable', $isNameEditable);
			$this->set('isAddAllowed', $isAddAllowed);
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
	
	private function getCustomFieldData($model,$sitetype){
		$cond = ($sitetype != '')
			  ? array('conditions'=>array('institution_site_type_id' => $sitetype),'order'=>'order') 
			  : array('order'=>'order');
			
		return $data = $this->{$model}->find('all',$cond);
	}
	
	public function customFields($model = 'InstitutionCustomField',$sitetype = '') {
		$this->Navigation->addCrumb('Custom Fields');
		//$ref_id = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $model);
		$ref_id = Inflector::underscore($model);
		$ref_id = strtolower($ref_id)."_id";
		$siteTypes = array();
		//if(!in_array($model,$this->noSiteTypeCustFields)){
		if($this->CustomFieldModelLists[$model]['hasSiteType'])	{
			//
			$siteTypes = $this->InstitutionSiteType->getSiteTypesList();
			if($sitetype == '')$sitetype = key($siteTypes); // initialize to first key if sitetype is '' and not institution custom field
			
		}else{
			$sitetype = '';
			
		}
		
		$data = $this->getCustomFieldData($model,$sitetype);
		$this->set('data',$data);
		//pr($data);die;
		$this->set('siteTypes',$siteTypes);
		$this->set('sitetype',$sitetype);
		$this->set('defaultModel',$model);
		$this->set('referenceId',$ref_id);//needed for CustomField Options
		$this->set('CustomFieldModelLists',$this->CustomFieldModelLists);
	}
	
	public function customFieldsEdit($model = 'InstitutionCustomField',$sitetype = '') {
		$this->Navigation->addCrumb('Edit Custom Fields');
		if($this->request->is('post')) {
			
			$this->{$model}->saveAll($this->request->data[$model]);
			$option = $model.'Option';
			if(isset($this->request->data[$option])){
				$this->{$option}->saveAll($this->request->data[$option]);
			}
			$redirect = array('controller'=>'Setup','action'=>'customFields',$model);
			if(isset($this->request->data['CustomFields']['institution_site_type_id'])){
				$redirect = array_merge($redirect,array($this->request->data['CustomFields']['institution_site_type_id']));
			}
			$this->redirect($redirect);//customFields/InstitutionSiteCustomField/8
		}
		//$ref_id = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $model);
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
		//pr($data);die;
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
	
	public function customTables($sitetype = ''){
		$this->Navigation->addCrumb('Custom Table');
                
		$siteTypes = $this->InstitutionSiteType->getSiteTypesList();

        if(empty($sitetype) and is_null($this->Session->read("InstitutionSiteType.id"))) {
            $this->Session->delete("InstitutionSiteType.id");
            $sitetype = key($siteTypes);
        }elseif(!empty($sitetype) and $sitetype != $this->Session->read("InstitutionSiteType.id")) {
            $this->Session->write('InstitutionSiteType.id', $sitetype);
        }else{
            $sitetype = $this->Session->read("InstitutionSiteType.id");
        }
                
		$this->CensusGrid->unbindModel(
			array('belongsTo' => array('CensusGridXCategory','CensusGridYCategory'))
		);
		
		$data = $this->CensusGrid->find('all',array('recursive'=>0,'conditions'=>array('institution_site_type_id'=>$sitetype), 'order' => array('CensusGrid.order')));

		$this->set('siteTypes', $siteTypes);
		$this->set('siteType', $this->Session->read("InstitutionSiteType.id"));
		$this->set('data', $data);
		$this->set('CustomFieldModelLists',$this->CustomFieldModelLists);
	}
	
	public function customTablesEdit($sitetype = '') {
        $this->Navigation->addCrumb('Edit Custom Table');

        $siteTypes = $this->InstitutionSiteType->getSiteTypesList();
        if(empty($sitetype)  and is_null($this->Session->read("InstitutionSiteType.id"))) {
            $this->Session->delete("InstitutionSiteType.id");
            $sitetype = key($siteTypes);
        }elseif (!empty($sitetype) and $sitetype != $this->Session->read("InstitutionSiteType.id")) {
            $this->Session->write('InstitutionSiteType.id', $sitetype);
        }else {
            $sitetype = $this->Session->read("InstitutionSiteType.id");
        }

        if($this->request->is('post')) {
            $this->autoRender = false;
            foreach($this->data as $model => $arrContent){
                $this->{$model}->saveAll($arrContent);
            }
            $this->redirect(array('action'=>'CustomTables',$sitetype));
        }

        $this->CensusGrid->unbindModel(
            array('belongsTo' => array('CensusGridXCategory','CensusGridYCategory'))
        );

        $data = $this->CensusGrid->find('all',array('recursive'=>0,'conditions'=>array('institution_site_type_id'=>$sitetype), 'order' => array('CensusGrid.order')));

        $this->set('siteTypes', $siteTypes);
        $this->set('siteType', $sitetype);
        $this->set('data', $data);
        $this->set('CustomFieldModelLists',$this->CustomFieldModelLists);
	}
	public function customTablesEditDetail($id = '') {
        if(empty($id) && is_null($this->Session->read('InstitutionSiteType.id'))){
            $this->redirect(array('controller' => 'Setup', 'action' => 'CustomTables'));
        }

		$this->Navigation->addCrumb('Edit Custom Table Detail');

		$arr = array('X','Y');
		if($this->request->is('post')) {

			if($this->data['CensusGrid']['id'] == ''){
				$lastInserted = $this->CensusGrid->save($this->data['CensusGrid']);
				$lastInsertId = $lastInserted['CensusGrid']['id'];

				foreach($arr as $val){
					foreach($this->request->data['CensusGrid'.$val.'Category'] as $k => &$arrCVal){
						$arrCVal['census_grid_id'] = $lastInsertId;
					}
					//pr($this->request->data['CensusGrid'.$val.'Category']);die;
					$model = 'CensusGrid'.$val.'Category';
					$this->{$model}->saveAll($this->request->data['CensusGrid'.$val.'Category']);
				}

				$id = $lastInsertId;
			}else{
				foreach($this->data as $model => $arrContent){
					$this->{$model}->saveAll($arrContent);
				}
			}
			$this->redirect(array('action'=>'CustomTables',$this->data['CensusGrid']['institution_site_type_id']));
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
		$this->set('sitetype',($data['CensusGrid']['institution_site_type_id'] > 0)? $data['CensusGrid']['institution_site_type_id'] : $this->Session->read('InstitutionSiteType.id') );
		$this->set('CustomFieldModelLists',$this->CustomFieldModelLists);
	}
} 
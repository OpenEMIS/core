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

class FieldOptionController extends AppController {
	public $uses = Array(
		'FieldOption',
		'FieldOptionValue'
	);
	
	public $optionList = array();
	public $options = array();
	
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
		$this->Navigation->addCrumb('Field Options', array('controller' => 'FieldOption', 'action' => 'index'));
		$this->optionList = $this->FieldOption->findOptions(true);
		// change the index to start from 1
		array_unshift($this->optionList, array());
		unset($this->optionList[0]);
		$this->options = $this->buildOptions($this->optionList);
	}
	
	private function buildOptions($list) {
		$options = array();
		foreach($list as $key => $values) {
			$key = $key;
			if(!empty($values['parent'])) {
				$parent = __($values['parent']);
				if(!array_key_exists($parent, $options)) {
					$options[$parent] = array();
				}
				$options[$parent][$key] = __($values['name']);
			} else {
				$options[$key] = __($values['name']);
			}
		}
		return $options;
	}
	
	public function index($selectedOption=1) {
		if(!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}
		$options = $this->options;
		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);
		$model = $this->FieldOptionValue->getModel()->alias;
		$header = $this->FieldOptionValue->getHeader();
		$subOptions = $this->FieldOptionValue->getSubOptions();
		$conditions = array();
		if(!empty($subOptions)) {
			$conditionId = $this->FieldOptionValue->getModel()->getConditionId();
			$selectedSubOption = $this->FieldOptionValue->getFirstSubOptionKey($subOptions);
			if(isset($this->request->params['named'][$conditionId])) {
				$selectedSubOption = $this->request->params['named'][$conditionId];
			}
			$conditions[$conditionId] = $selectedSubOption;
			$this->set(compact('subOptions', 'selectedSubOption', 'conditionId'));
		}
		$data = $this->FieldOptionValue->getAllValues($conditions);
		
		$this->set(compact('data', 'header', 'selectedOption', 'options', 'model'));
		$this->Navigation->addCrumb($header);
	}
	
	public function indexEdit($selectedOption=1) {
		if(!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}
		$options = $this->options;
		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);
		$model = $this->FieldOptionValue->getModel()->alias;
		$header = $this->FieldOptionValue->getHeader();
		$subOptions = $this->FieldOptionValue->getSubOptions();
		$conditions = array();
		if(!empty($subOptions)) {
			$conditionId = $this->FieldOptionValue->getModel()->getConditionId();
			$selectedSubOption = $this->FieldOptionValue->getFirstSubOptionKey($subOptions);
			if(isset($this->request->params['named'][$conditionId])) {
				$selectedSubOption = $this->request->params['named'][$conditionId];
			}
			$conditions[$conditionId] = $selectedSubOption;
			$this->set(compact('selectedSubOption', 'conditionId'));
		}
		$data = $this->FieldOptionValue->getAllValues($conditions);
		if($model === 'FieldOptionValue') {
			$conditions['field_option_id'] = $obj['id'];
		}
		$this->set(compact('data', 'header', 'selectedOption', 'options', 'model', 'conditions'));
		$this->Navigation->addCrumb($header);
	}
	
	public function reorder($selectedOption=1) {
		if ($this->request->is('post') || $this->request->is('put')) {
			$obj = $this->optionList[$selectedOption];
			$this->FieldOptionValue->setParent($obj);
			$data = $this->request->data;
			$model = $this->FieldOptionValue->getModel();
			$conditions = array();
			$redirect = array('action' => 'indexEdit', $selectedOption);
			
			if(!empty($this->request->params['named'])) {
				$conditionId = key($this->request->params['named']);
				$selectedSubOption = current($this->request->params['named']);
				$conditions[$conditionId] = $selectedSubOption;
				$redirect = array_merge($redirect, $conditions);
			}
			
			$model->reorder($data, $conditions);
			return $this->redirect($redirect);
		}
	}
	
	public function add($selectedOption=1) {
		if(!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}
		
		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);
		$header = $this->FieldOptionValue->getHeader();
		$fields = $this->FieldOptionValue->getFields();
		$model = $this->FieldOptionValue->getModel();
		$selectedSubOption = false;
		$conditionId = false;
		
		// get suboption value from index page and set it as the default option
		if(!empty($this->request->params['named'])) {
			$conditionId = key($this->request->params['named']);
			$selectedSubOption = current($this->request->params['named']);
			$this->set(compact('conditionId', 'selectedSubOption'));
			foreach($fields['fields'] as $key => $obj) {
				if($obj['field']==$conditionId) {
					$fields['fields'][$key]['default'] = $selectedSubOption;
				}
			}
		}
		
		if($this->request->is('post') || $this->request->is('put')) {
			if($this->FieldOptionValue->saveValue($this->request->data)) {
				$redirect = array('action' => 'index', $selectedOption);
				if($conditionId !== false) {
					$redirect = array_merge($redirect, array($conditionId => $this->request->data[$model->alias][$conditionId]));
				}
				$this->Message->alert('general.add.success');
				return $this->redirect($redirect);
			} else {
				$this->Message->alert('general.add.failed');
			}
		}
		$this->set(compact('header', 'fields', 'selectedOption'));
		$this->Navigation->addCrumb($header);
	}
	
	public function view($selectedOption=1, $selectedValue=0) {
		if(!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}
		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);
		$data = $this->FieldOptionValue->getValue($selectedValue);
		$selectedSubOption = false;
		$conditionId = false;
		
		if(!empty($this->request->params['named'])) {
			$conditionId = key($this->request->params['named']);
			$selectedSubOption = current($this->request->params['named']);
			$this->set(compact('conditionId', 'selectedSubOption'));
		}
		
		if(empty($data)) {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index', $selectedOption));
		}
		$header = $this->FieldOptionValue->getHeader();
		$fields = $this->FieldOptionValue->getFields();
		$this->set(compact('data', 'header', 'fields', 'selectedOption', 'selectedValue'));
		$this->Navigation->addCrumb($header);
	}
	
	public function edit($selectedOption=1, $selectedValue=0) {
		if($selectedValue == 0) {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index', $selectedOption));
		}
		if(!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}
		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);
		$model = $this->FieldOptionValue->getModel();
		$selectedSubOption = false;
		$conditionId = false;
		
		if(!empty($this->request->params['named'])) {
			$conditionId = key($this->request->params['named']);
			$selectedSubOption = current($this->request->params['named']);
			$this->set(compact('conditionId', 'selectedSubOption'));
		}
		
		if($this->request->is('post') || $this->request->is('put')) {
			if($this->FieldOptionValue->saveValue($this->request->data)) {
				$redirect = array('action' => 'view', $selectedOption, $selectedValue);
				if($conditionId !== false) {
					$redirect = array_merge($redirect, array($conditionId => $this->request->data[$model->alias][$conditionId]));
				}
				$this->Message->alert('general.edit.success');
				return $this->redirect($redirect);
			} else {
				$this->Message->alert('general.edit.failed');
			}
		} else {
			$this->request->data = $this->FieldOptionValue->getValue($selectedValue);
		}
		$header = $this->FieldOptionValue->getHeader();
		$fields = $this->FieldOptionValue->getFields();
		$this->set(compact('header', 'fields', 'selectedOption', 'selectedValue'));
		$this->Navigation->addCrumb($header);
	}
	/*
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
	*/
	
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

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

class FieldOptionComponent extends Component {
	public $render = false;
	public $options = array();
	public $optionFields = array();
	public $components = array('Session');
	
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
	
	public function initialize(Controller $controller) {
		$this->options['InstitutionProvider'] = array('parent' => 'Institution', 'label' => 'Provider');
		$this->options['InstitutionSector'] = array('parent' => 'Institution', 'label' => 'Sector');
		$this->options['InstitutionStatus'] = array('parent' => 'Institution', 'label' => 'Status');
		$this->options['InstitutionCustomField'] = array(
			'parent' => 'Institution', 
			'label' => 'Custom Fields', 
			'viewMethod' => array('action' => 'customFields', 'InstitutionCustomField'),
			'editMethod' => array('action' => 'customFieldsEdit', 'InstitutionCustomField')
		);
		$this->options['InstitutionSiteType'] = array('parent' => 'InstitutionSite', 'label' => 'Type');
		$this->options['InstitutionSiteOwnership'] = array('parent' => 'InstitutionSite', 'label' => 'Ownership');
		$this->options['InstitutionSiteLocality'] = array('parent' => 'InstitutionSite', 'label' => 'Locality');
		$this->options['InstitutionSiteStatus'] = array('parent' => 'InstitutionSite', 'label' => 'Status');
		$this->options['InfrastructureCategory'] = array('parent' => 'Infrastructure', 'label' => 'Category');
		
		$infrastructureCategory = ClassRegistry::init('InfrastructureCategory')->findList(true);
		foreach($infrastructureCategory as $category) {
			$categoryModel = 'Infrastructure' . Inflector::singularize($category);
			$this->options[$categoryModel] = array('parent' => 'Infrastructure', 'label' => $category, 'suboption' => true);
		}
		
		$this->options['Bank'] = array('parent' => 'Bank Account', 'label' => 'Banks');
		$this->options['BankBranch'] = array('parent' => 'Bank Account', 'label' => 'Branches', 'suboption' => true);
		$this->options['FinanceNature'] = array('parent' => 'Finances', 'label' => 'Nature');
		$this->options['FinanceType'] = array('parent' => 'Finances', 'label' => 'Type', 'suboption' => true);
		$this->options['FinanceCategory'] = array('parent' => 'Finances', 'label' => 'Category', 'suboption' => true);
		$this->options['FinanceSource'] = array('parent' => 'Finances', 'label' => 'Source');
		$this->options['AssessmentResultType'] = array('parent' => 'Assessment', 'label' => 'Result Type');
		
		$this->options['Students.StudentCategory'] = array('parent' => 'Student', 'label' => 'Category');
		$this->options['Students.StudentBehaviourCategory'] = array('parent' => 'Student', 'label' => 'Behaviour Category');
		
		$controller->set('options', $this->buildOptions($this->options));
		$controller->set('selectedOption', '');
	}
	
	public function processAction($controller, $action) {
		if(CakeSession::check('Auth.User') == false) {
			$controller->redirect($controller->Auth->loginAction);
		}
		$controller->autoRender = false;
		if(empty($action)) {
			$action = 'index';
		}
		$result = call_user_func_array(array($this, $action), array($controller, $controller->params));
		$name = substr(get_class($this), 0, strpos(get_class($this), 'Component'));
		$controller->Navigation->addCrumb('Field Options');
		if($this->render === false) {
			$controller->render(Inflector::underscore($name) . '/' . Inflector::underscore($action));
		} else {
			$controller->render(Inflector::underscore($name) . '/' . Inflector::underscore($this->render));
		}
		return $result;
	}
	
	private function buildOptions($list) {
		$options = array();
		if(is_int(key($list))) {
			foreach($list as $key => $values) {
				if(isset($values['parent'])) {
					$parent = __($values['parent']);
					if(!array_key_exists($parent, $options)) {
						$options[$parent] = array();
					}
					$options[$parent][$key] = __($values['label']);
				} else {
					$options[$key] = __($values['label']);
				}
			}
		} else {
			foreach($list as $model => $values) {
				if(isset($values['parent'])) {
					$parent = __($values['parent']);
					if(!array_key_exists($parent, $options)) {
						$options[$parent] = array();
					}
					$options[$parent][$model] = __($values['label']);
				} else {
					$options[$model] = __($values['label']);
				}
			}
		}
		return $options;
	}
	
	private function getOption($params, $default=true) {
		$selectedOption = isset($params['pass'][0]) ? $params['pass'][0] : null;
		$selectedSubOption = null;
		$values = array();
		$option = array();
		$suboptions = array();
		$conditions = array();
		$model = '';
		$id = null;
		if(!empty($selectedOption)) {
			if(!array_key_exists($selectedOption, $this->options) && $default) {
				$selectedOption = key($this->options);
			}
		} else {
			$selectedOption = key($this->options);
		}
		$option = $this->options[$selectedOption];
		$header = $option['parent'] . ' - ' . $option['label'];
		
		if(!array_key_exists('viewMethod', $option)) {
			$parameters = array($selectedOption);
			if(isset($option['suboption'])) {
				$selectedSubOption = isset($params['pass'][1]) ? $params['pass'][1] : null;
				$suboptions = ClassRegistry::init($selectedOption)->getSubOptions();
				if(!is_null($selectedSubOption)) {
					if(!array_key_exists($selectedSubOption, $suboptions) && $default) {
						$selectedSubOption = 0;
					}
				} else {
					$selectedSubOption = 0;
				}
				$suboption = $suboptions[$selectedSubOption];
				$model = $suboption['model'];
				if(array_key_exists('conditions', $suboption)) {
					$conditions = $suboption['conditions'];
				}
				if(isset($suboption['parent'])) {
					$header .= ' - ' . $suboption['parent'] . ' - ' . $suboption['label'];
				} else {
					$header .= ' - ' . $suboption['label'];
				}
				
				$parameters[] = $selectedSubOption;
				if(isset($params['pass'][2])) {
					$id = $params['pass'][2];
				}
			} else {
				$model = $selectedOption;
				if(isset($params['pass'][1])) {
					$id = $params['pass'][1];
				}
			}
			$values = compact('selectedOption', 'selectedSubOption', 'model', 'conditions', 'header', 'suboptions', 'id', 'parameters');
		} else {
			$option['header'] = $header;
			$option['selectedOption'] = $selectedOption;
			$values = array('redirect' => $option);
		}
		return $values;
	}
	
	public function index($controller, $params) {
		extract($this->getOption($params));
		
		if(!isset($redirect)) {
			$controller->set('selectedOption', $selectedOption);
			$model = ClassRegistry::init($model);
			
			$data = $model->findOptions(array('conditions' => $conditions));
			if(!is_null($selectedSubOption)) {
				$controller->set('selectedSubOption', $selectedSubOption);
				$controller->set('subOptions', $this->buildOptions($suboptions));
			}
			$controller->set('header', $header);
			$controller->set('data', $data);
			$controller->set('parameters', $parameters);
			$controller->set('model', $model->alias);
			$controller->set('fields', $model->getOptionFields());
		} else {
			//pr($redirect);
			$method = 'viewMethod';
			if(strpos($controller->action, 'Edit') !== false) {
				$method = 'editMethod';
			}
			$action = $redirect[$method]['action'];
			$params = array($controller, $redirect[$method][0]);
			$controller->set('header', $redirect['header']);
			$controller->set('selectedOption', $redirect['selectedOption']);
			call_user_func_array(array($this, $action), $params);
			$this->render = $action;
		}
	}
	
	public function indexEdit($controller, $params) {
		$this->index($controller, $params);
	}
	
	public function reorder($controller, $params) {
		if ($controller->request->is('post')) {
			$data = $controller->request->data;
			$model = key($data);
			$option = $this->getOption($params, false);
			if(isset($option['conditions'])) {
				$data['conditions'] = $option['conditions'];
			}
			$modelObj = ClassRegistry::init($option['model']);
			$modelObj->reorder($data);
			$redirect = array_merge(array('action' => 'fieldOptionIndexEdit'), $option['parameters']);
			return $controller->redirect($redirect);
		}
	}
	
	public function view($controller, $params) {
		extract($this->getOption($params, false));
		if(!empty($selectedOption)) {
			$model = ClassRegistry::init($model);
			if ($model->exists($id)) {
				$data = $model->getOption($id);
				$fields = $model->getOptionFields();
				$controller->set('header', $header);
				$controller->set('model', $model->alias);
				$controller->set('id', $id);
				$controller->set('data', $data);
				$controller->set('fields', $fields);
				$controller->set('parameters', $parameters);
			} else {
				return $controller->redirect(array('action' => 'fieldOption', $selectedOption));
			}
		} else {
			return $controller->redirect(array('action' => 'fieldOption', key($this->options)));
		}
	}
	
	public function edit($controller, $params) {
		extract($this->getOption($params, false));
		
		if(!empty($selectedOption)) {
			$model = ClassRegistry::init($model);
			if (!is_null($id) && $model->exists($id)) { // edit
				if ($controller->request->is('put') || $controller->request->is('post')) {
					$controller->request->data[$model->alias]['id'] = $id;
					if ($model->save($controller->request->data)) {
						$controller->Message->alert('general.edit.success');
					} else {
						$controller->Message->alert('general.edit.failed', array('type' => 'error'));
					}
					$redirect = array_merge(array('action' => 'fieldOptionView'), $parameters);
					$redirect[] = $id;
					return $controller->redirect($redirect);
				} else {
					$model->recursive = 0;
					$controller->request->data = $model->findById($id);
				}
			} else { // add
				if ($controller->request->is('post')) {
					$model->create();
					if ($model->save($controller->request->data)) {
						$controller->Message->alert('general.add.success');
						$redirect = array_merge(array('action' => 'fieldOption'), $parameters);
						return $controller->redirect($redirect);
					} else {
						$controller->Message->alert('general.add.failed', array('type' => 'error'));
					}
				}
				if(!empty($conditions)) {
					$controller->set('conditions', $conditions);
				}
				$order = $model->find('count', array('conditions' => $conditions));
				$controller->set('order', $order+1);
			}
			$controller->set('header', $header);
			$controller->set('model', $model->alias);
			$controller->set('id', $id);
			$controller->set('fields', $model->getOptionFields());
			$controller->set('parameters', $parameters);
		} else {
			return $controller->redirect(array('action' => 'fieldOption', key($this->options)));
		}
	}
	
	private function getCustomFieldData($model, $sitetype){
		$options = array('order' => 'order');
		if($sitetype != '') {
			$options['conditions'] = array('institution_site_type_id' => $sitetype);
		}
		$modelObj = ClassRegistry::init($model);
		$modelObj->bindModel(array('hasMany'=>array($model.'Option' => array('order'=> 'order'))));
		$data = $modelObj->find('all',$options);
		return $data;
	}
	
	public function customFields($controller, $model = 'InstitutionCustomField', $sitetype = '') {
		
		$ref_id = Inflector::underscore($model);
		$ref_id = strtolower($ref_id)."_id";
		$siteTypes = array();
		
		if($this->CustomFieldModelLists[$model]['hasSiteType'])	{
			$siteTypes = ClassRegistry::init('InstitutionSiteType')->getSiteTypesList();
			if($sitetype == '')$sitetype = key($siteTypes); // initialize to first key if sitetype is '' and not institution custom field
		}else{
			$sitetype = '';
		}
		
		$data = $this->getCustomFieldData($model,$sitetype);
		$controller->set('data',$data);
		$controller->set('siteTypes',$siteTypes);
		$controller->set('sitetype',$sitetype);
		$controller->set('defaultModel',$model);
		$controller->set('referenceId',$ref_id);//needed for CustomField Options
		$controller->set('CustomFieldModelLists',$this->CustomFieldModelLists);
	}
	
	public function customFieldsEdit($controller, $model = 'InstitutionCustomField', $sitetype = '') {
		if($controller->request->is('post')) {
			$modelObj = ClassRegistry::init($model);
			$modelObj->saveMany($controller->request->data[$model]);
			$option = $model.'Option';
			$optionObj = ClassRegistry::init($optionObj);
			if(isset($controller->request->data[$option])){
				$optionObj->saveMany($controller->request->data[$option]);
			}
			$redirect = array('action' => 'fieldOption', $model);
			if(isset($controller->request->data['CustomFields']['institution_site_type_id'])) {
				$redirect[] = $controller->request->data['CustomFields']['institution_site_type_id'];
				//$redirect = array_merge($redirect, array($this->request->data['CustomFields']['institution_site_type_id']));
			}
			$controller->redirect($redirect);//customFields/InstitutionSiteCustomField/8
		}
		$ref_id = Inflector::underscore($model);
		$ref_id = strtolower($ref_id)."_id";
		
		$siteTypes = array();
		if($this->CustomFieldModelLists[$model]['hasSiteType'])	{
			$siteTypes = ClassRegistry::init('InstitutionSiteType')->getSiteTypesList();
			if($sitetype == '')$sitetype = key($siteTypes); // initialize to first key if sitetype is '' and not institution custom field
			
		}else{
			$sitetype = '';
		}
		$data = $this->getCustomFieldData($model,$sitetype);
		$controller->set('data',$data);
		$controller->set('siteTypes',$siteTypes);
		$controller->set('sitetype',$sitetype);
		$controller->set('defaultModel',$model);
		$controller->set('referenceId',$ref_id);//needed for CustomField Options
		$controller->set('CustomFieldModelLists',$this->CustomFieldModelLists);
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

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

class StaffLicense extends StaffAppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Staff' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction', 
		'DatePicker' => array('issue_date', 'expiry_date')
	);
	
	public $belongsTo = array(
		'Staff.Staff',
		'LicenseType',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $validate = array(
		'license_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid License Type.'
			)
		),
		'issuer' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Issuer.'
			)
		),
		'license_number' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid License Number.'
			)
		),
	);

	public $headerDefault = 'Licenses';
	
	public function compareDate($field = array(), $compareField = null) {
		$startDate = new DateTime(current($field));
		$endDate = new DateTime($this->data[$this->name][$compareField]);
		return $endDate >= $startDate;
	}
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'issue_date', 'type' => 'datepicker'),
				array('field' => 'name',  'model' =>'LicenseType', 'labelKey' => 'general.type'),
				array('field' => 'issuer'),
				array('field' => 'license_number'),
				array('field' => 'expiry_date', 'type' => 'datepicker'),
				
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function license($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$header = __($this->headerDefault);
		$this->unbindModel(array('belongsTo' => array('ModifiedUser','CreatedUser')));
		$data = $this->findAllByStaffId($controller->Session->read('Staff.id'));
		
		$controller->set(compact('header', 'data'));
	}

	public function licenseView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$header = __($this->headerDefault . ' Details');
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->findById($id);
		
		if(empty($data)){
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action'=>'license'));
		}
		
		$controller->Session->write('StaffLicense.id', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'header', 'fields', 'id'));
	}
	
	public function licenseDelete($controller, $params) {
		return $this->remove($controller, 'license');
	}
	
	public function licenseAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault);
		$controller->set('header', __('Add ' . $this->headerDefault));
		$this->setup_add_edit_form($controller, $params, 'add');
	}
	
	public function licenseEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('header', __('Edit ' . $this->headerDefault));
		$this->setup_add_edit_form($controller, $params, 'edit');
		
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params, $type){
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$licenseTypeOptions = $this->LicenseType->find('list', array('fields'=> array('id', 'name')));
		
		$controller->set('licenseTypeOptions', $licenseTypeOptions);
		if($controller->request->is('get')) {
			
			$this->recursive = -1;
			$data = $this->findById($id);
			if(!empty($data)){
				$controller->request->data = $data;
			}
		}
		else{
			$controller->request->data[$this->name]['staff_id'] = $controller->Session->read('Staff.id');
			if($this->save($controller->request->data)) {
				$controller->Message->alert('general.' . $type . '.success');
				return $controller->redirect(array('action' => 'license'));
			}
		}
	}

	public function autocomplete($search) {
		$field = 'issuer';
		$search = sprintf('%%%s%%', $search);
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT StaffLicense.' . $field),
			'conditions' => array('StaffLicense.' . $field . ' LIKE' => $search
			),
			'order' => array('StaffLicense.' . $field)
		));
		
		$data = array();
		
		foreach($list as $obj) {
			$staffLicenseField = $obj['StaffLicense'][$field];
			
			$data[] = array(
				'label' => trim($staffLicenseField),
				'value' => array($field => $staffLicenseField)
			);
		}

		return $data;
	}
	
	public function licenseAjaxFindLicense($controller, $params){
		if ($controller->request->is('ajax')) {
			$this->render = false;
			$search = $params->query['term'];
			$data = $this->autocomplete($search);

			return json_encode($data);
		}
	}
}

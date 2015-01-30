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

class StaffAward extends StaffAppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Staff' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction', 
		'DatePicker' => array('issue_date')
	);
	
	public $belongsTo = array(
		'Staff.Staff',
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
		'award' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Award.'
			)
		),
		'issuer' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Issuer.'
			)
		)
	);

	public $headerDefault = 'Awards';
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'issue_date'),
				array('field' => 'award', 'labelKey' => 'general.name' ),
				array('field' => 'issuer'),
				array('field' => 'comment'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function award($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStaffId($controller->Session->read('Staff.id'));
		$header = __($this->headerDefault);
		$controller->set(compact('header', 'data'));
	}

	public function awardView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$controller->set('header', __($this->headerDefault . ' Details'));
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->findById($id);
		
		if(empty($data)){
			$controller->Message->alert('general.noData');
			$controller->redirect(array('action'=>'award'));
		}
		
		$controller->Session->write('StaffAward.id', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'data', 'fields', 'id'));
	}
	
	public function awardDelete($controller, $params) {
		return $this->remove($controller, 'award');
	}
	
	public function awardAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault);
		$controller->set('header', __('Add '.$this->headerDefault));
		$this->setup_add_edit_form($controller, $params, 'add');
	}
	
	public function awardEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault);
		$controller->set('header', __('Edit '.$this->headerDefault));
		$this->setup_add_edit_form($controller, $params, 'edit');
		
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params, $type){
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
			if(!empty($data)){
				$controller->request->data = $data;
			}
		}
		else{
			$controller->request->data[$this->name]['staff_id'] = $controller->Session->read('Staff.id');
			if($this->save($controller->request->data)){
				$controller->Message->alert('general.' . $type . '.success');
				return $controller->redirect(array('action' => 'award'));
			}
		}
	}

	public function autocomplete($search, $type='1') {
		$field = 'award';
		if($type=='2'){
			$field = 'issuer';
		}
		$search = sprintf('%%%s%%', $search);
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT StaffAward.' . $field),
			'conditions' => array('StaffAward.' . $field . ' LIKE' => $search
			),
			'order' => array('StaffAward.' . $field)
		));
		
		$data = array();
		
		foreach($list as $obj) {
			$staffAwardField = $obj[$this->alias][$field];
			
			$data[] = array(
				'label' => trim($staffAwardField),
				'value' => array($field => $staffAwardField)
			);
		}

		return $data;
	}
	
	//Ajax method
	public function awardAjaxFindAward($controller, $params) {
		if ($controller->request->is('ajax')) {
			$this->render = false;
			$type = $params['pass'][0];
			$search = $params->query['term'];
			$data = $this->autocomplete($search, $type);

			return json_encode($data);
		}
	}
}

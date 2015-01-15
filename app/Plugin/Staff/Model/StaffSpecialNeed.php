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

class StaffSpecialNeed extends StaffAppModel {
	public $actsAs = array('ControllerAction', 'DatePicker' => array('special_need_date'));
	
	public $belongsTo = array(
		'SpecialNeedType',
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
		'special_need_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Special Need Type.'
			)
		)
	);
	public $headerDefault = 'Special Needs';
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'special_need_date','labelKey' => 'general.date'),
				array('field' => 'name', 'model' => 'SpecialNeedType','labelKey' => 'general.type' ),
				array('field' => 'comment'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	public function specialNeed($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$header = $this->headerDefault;
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStaffId($controller->Session->read('Staff.id'));
		$controller->set(compact('header', 'data'));
	}

	public function specialNeedView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$header = __($this->headerDefault . ' Details');
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->findById($id);
		
		if(empty($data)){
			$controller->Message->alert('general.noData');
			$controller->redirect(array('action'=>'specialNeed'));
		}
		
		$controller->Session->write('StaffSpecialNeed.id', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'data', 'fields', 'id'));
	}
	
	public function specialNeedDelete($controller, $params) {
		return $this->remove($controller, 'specialNeed');
	}
	
	public function specialNeedAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault);
		$controller->set('header', __('Add ' . $this->headerDefault));
		$this->setup_add_edit_form($controller, $params, 'add');
	}
	
	public function specialNeedEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault);
		$controller->set('header', __('Edit ' . $this->headerDefault));
		$this->setup_add_edit_form($controller, $params, 'edit');
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params, $type){
		if (!empty($controller->request->data)) {
			$specialNeedTypeOptions = $this->SpecialNeedType->getList(array('value' => $controller->request->data['StaffSpecialNeed']['special_need_type_id']));
		} else {
			$specialNeedTypeOptions = $this->SpecialNeedType->getList(array('value' => 0));
		}

		$controller->set('specialNeedTypeOptions', $specialNeedTypeOptions);
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
				return $controller->redirect(array('action' => 'specialNeed'));
			}
		}
	}
}

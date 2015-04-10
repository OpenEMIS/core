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
	public $useTable = 'user_special_needs';
	public $actsAs = array(
		'Excel' => array('header' => array('SecurityUser' => array('openemis_no', 'first_name', 'last_name'))),
		'ControllerAction2', 
		'DatePicker' => array('special_need_date')
	);
	
	public $belongsTo = array(
		'SecurityUser',
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

	/* Excel Behaviour */
	public function excelGetConditions() {
		$conditions = array();
		if (CakeSession::check('Staff.security_user_id')) {
			$id = CakeSession::read('Staff.security_user_id');
			$conditions = array($this->alias.'.security_user_id' => $id);
		}
		return $conditions;
	}
	/* Excel Behaviour */
	
	public function beforeAction() {
		parent::beforeAction();
		if (!$this->Session->check('Staff.id')) {
			return $this->redirect(array('controller' => $this->controller->name, 'action' => 'index'));
		}
		$this->Navigation->addCrumb(__('Special Needs'));

		$this->fields['security_user_id']['type'] = 'hidden';
		$this->fields['security_user_id']['value'] = $this->Session->read('Staff.security_user_id');
		$this->fields['special_need_type_id']['type'] = 'select';
		$this->fields['special_need_type_id']['options'] = $this->SpecialNeedType->getList();
	}

	public function index() {
		$userId = $this->Session->read('Staff.security_user_id');
		$this->contain(array('SpecialNeedType' => array('id', 'name')));
		$data = $this->findAllBySecurityUserId($userId);
		$this->setVar(compact('data'));
	}
}

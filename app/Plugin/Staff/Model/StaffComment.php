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

class StaffComment extends StaffAppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Staff' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction2', 
		'DatePicker' => array('comment_date')
	);

	public $belongsTo = array(
		'Staff.Staff',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
	);
	public $validate = array(
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Title'
			)
		),
		'comment' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Comment'
			)
		),
	);

	public function beforeAction() {
		parent::beforeAction();
		if (!$this->Session->check('Staff.id')) {
			return $this->redirect(array('controller' => $this->controller->name, 'action' => 'index'));
		}
		$this->Navigation->addCrumb(__('Comments'));
		$this->setVar('contentHeader', __('Comments'));

		$this->fields['staff_id']['type'] = 'hidden';
		$this->fields['staff_id']['value'] = $this->Session->read('Staff.id');
	}
	
	public function index() {
		$staffId = $this->Session->read('Staff.id');
		$this->recursive = -1;
		$data = $this->findAllByStaffId($staffId);
		$this->setVar(compact('data'));
	}
}

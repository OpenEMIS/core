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

class StudentLanguage extends StudentsAppModel {
	public $useTable = 'user_languages';
	public $actsAs = array(
		'Excel' => array('header' => array('SecurityUser' => array('openemis_no', 'first_name', 'last_name'))),
		'ControllerAction2', 
		'DatePicker' => array('evaluation_date')
	);

	public $belongsTo = array(
		'SecurityUser',
		'Language',
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
		'language_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Language'
			)
		),
		'listening' => array(
			'ruleRequired' => array(
				'rule' => array('range', -1, 6),
				'allowEmpty' => true,
				'message' => 'Please enter a number between 0 and 5'
			)
		),
		'speaking' => array(
			'ruleRequired' => array(
				'rule' => array('range', -1, 6),
				'allowEmpty' => true,
				'message' => 'Please enter a number between 0 and 5'
			)
		),
		'reading' => array(
			'ruleRequired' => array(
				'rule' => array('range', -1, 6),
				'allowEmpty' => true,
				'message' => 'Please enter a number between 0 and 5'
			)
		),
		'writing' => array(
			'ruleRequired' => array(
				'rule' => array('range', -1, 6),
				'allowEmpty' => true,
				'message' => 'Please enter a number between 0 and 5'
			)
		),
	);

	/* Excel Behaviour */
	public function excelGetConditions() {
		$conditions = array();
		if (CakeSession::check('Student.security_user_id')) {
			$id = CakeSession::read('Student.security_user_id');
			$conditions = array($this->alias.'.security_user_id' => $id);
		}
		return $conditions;
	}
	/* End Excel Behaviour */

	public function beforeAction() {
		parent::beforeAction();
		if (!$this->Session->check('Student.id')) {
			return $this->redirect(array('controller' => $this->controller->name, 'action' => 'index'));
		}
		$this->Navigation->addCrumb(__('Languages'));

		$this->fields['security_user_id']['type'] = 'hidden';
		$this->fields['security_user_id']['value'] = $this->Session->read('Student.security_user_id');
		$this->fields['language_id']['type'] = 'select';
		$this->fields['language_id']['options'] = $this->Language->getList();

		$gradeOptions = array();
		for ($i = 0; $i < 6; $i++) {
			$gradeOptions[$i] = $i;
		}
		$this->fields['listening']['type'] = 'select';
		$this->fields['listening']['options'] = $gradeOptions;
		$this->fields['speaking']['type'] = 'select';
		$this->fields['speaking']['options'] = $gradeOptions;
		$this->fields['reading']['type'] = 'select';
		$this->fields['reading']['options'] = $gradeOptions;
		$this->fields['writing']['type'] = 'select';
		$this->fields['writing']['options'] = $gradeOptions;
	}
	
	public function index() {
		$userId = $this->Session->read('Student.security_user_id');
		$this->contain(array('Language' => array('id', 'name')));
		$data = $this->findAllBySecurityUserId($userId);
		$this->setVar(compact('data'));
	}
}

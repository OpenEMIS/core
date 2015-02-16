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

class StudentNationality extends StudentsAppModel {
	public $useTable = 'user_nationalities';
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('openemis_no', 'first_name', 'last_name'))),
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'Students.Student',
		'Country',
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
		'country_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Country'
			)
		)
	);

	public function beforeAction() {
		parent::beforeAction();
		if (!$this->Session->check('Student.id')) {
			return $this->redirect(array('controller' => $this->controller->name, 'action' => 'index'));
		}
		$this->Navigation->addCrumb(__('Nationalities'));

		$this->fields['security_user_id']['type'] = 'hidden';
		$this->fields['security_user_id']['value'] = $this->Session->read('Student.security_user_id');
		$this->fields['country_id']['type'] = 'select';
		$this->fields['country_id']['options'] = $this->Country->getOptions();
	}
	
	public function index() {
		$securityUserId = $this->Session->read('Student.security_user_id');
		$this->contain(array('Country' => array('id', 'name')));
		$data = $this->findAllBySecurityUserId($securityUserId);
		$this->setVar(compact('data'));
	}
}

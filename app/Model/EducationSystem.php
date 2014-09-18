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

App::uses('AppModel', 'Model');

class EducationSystem extends AppModel {
	public $actsAs = array('ControllerAction2', 'Reorder');
	
	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);
	public $hasMany = array('EducationLevel');
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This name is already exists in the system'
			)
		)
	);
	
	public function beforeAction() {
		parent::beforeAction();
		
		$this->fields['order']['visible'] = false;
		if ($this->action == 'add') {
			$this->fields['order']['type'] = 'hidden';
			$this->fields['order']['visible'] = true;
			$this->fields['order']['value'] = 0;
			$this->fields['visible']['type'] = 'hidden';
			$this->fields['visible']['value'] = 1;
		} else {
			$this->fields['visible']['type'] = 'select';
			$this->fields['visible']['options'] = $this->controller->Option->get('yesno');
		}
		$this->setVar('selectedAction', $this->alias);
	}
	
	public function index() {
		$data = $this->find('all', array('order' => array('order')));
		$this->setVar(compact('data'));
	}
}

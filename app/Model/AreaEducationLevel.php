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

class AreaEducationLevel extends AppModel {
	public $actsAs = array('ControllerAction2');
	
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
	
	public $hasMany = array('AreaEducation');
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				 'message' => 'Please enter the name.'
			)
		)
	);
	
	public function beforeAction() {
        parent::beforeAction();
		
		if ($this->action == 'add') {
			$maxLevel = $this->find('first', array('fields' => ('MAX(AreaEducationLevel.level) AS maxLevel')));
			$this->fields['level']['type'] = 'hidden';
			$this->fields['level']['value'] = $maxLevel[0]['maxLevel']+1;
		} else if ($this->action == 'edit') {
			$this->fields['level']['visible'] = false;
		}
		
		$this->Navigation->addCrumb('Area Education Levels');
		$this->setVar('contentHeader', __('Area Education Levels'));
    }
	
	public function index() {
		$data = $this->find('all', array('order' => array('level')));
		$this->setVar(compact('data'));
	}
}

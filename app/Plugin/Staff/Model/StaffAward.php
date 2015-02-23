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
	public $useTable = 'user_awards';
	public $actsAs = array(
		'Excel' => array('header' => array('SecurityUser' => array('openemis_no', 'first_name', 'last_name'))),
		'ControllerAction2',
		'DatePicker' => array('issue_date')
	);
	
	public $belongsTo = array(
		'SecurityUser',
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
	
	public function beforeAction() {
		parent::beforeAction();
		if (!$this->Session->check('Staff.id')) {
			return $this->redirect(array('controller' => $this->controller->name, 'action' => 'index'));
		}
		$this->Navigation->addCrumb(__('Special Needs'));

		$this->fields['security_user_id']['type'] = 'hidden';
		$this->fields['security_user_id']['value'] = $this->Session->read('Staff.security_user_id');

		$this->fields['award']['attr']['onfocus'] = 'jsForm.autocomplete(this)';
		$this->fields['award']['attr']['autocompleteURL'] = $this->controller->name.'/'.$this->alias.'/autocompleteAward';

		$this->fields['issuer']['attr']['onfocus'] = 'jsForm.autocomplete(this)';
		$this->fields['issuer']['attr']['autocompleteURL'] = $this->controller->name.'/'.$this->alias.'/autocompleteIssuer';
	}

	public function index() {
		$userId = $this->Session->read('Staff.security_user_id');
		$this->recursive = -1;
		$data = $this->findAllBySecurityUserId($userId);
		$this->setVar(compact('data'));
	}

	public function autocompleteAward() {
		if ($this->request->is('ajax')) {
			$this->render = false;
			$this->layout = 'ajax';
			$search = $this->controller->params->query['term'];
			$data = $this->autocomplete($search, 'award');
			return json_encode($data);
		}
	}

	public function autocompleteIssuer() {
		if ($this->request->is('ajax')) {
			$this->render = false;
			$this->layout = 'ajax';
			$search = $this->controller->params->query['term'];
			$data = $this->autocomplete($search, 'issuer');
			return json_encode($data);
		}
	}

	public function autocomplete($search, $type='award') {
		$field = 'award';
		if($type=='issuer'){
			$field = 'issuer';
		}
		$search = sprintf('%%%s%%', $search);
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT '.$this->alias.'.' . $field),
			'conditions' => array($this->alias.'.' . $field . ' LIKE' => $search
			),
			'order' => array($this->alias.'.' . $field)
		));
		
		$data = array();
		
		foreach($list as $obj) {
			$awardField = $obj[$this->alias][$field];
			$data[] = $awardField;
			// $data[] = array(
			// 	'label' => trim($staffAwardField),
			// 	'value' => array($field => $staffAwardField)
			// );
		}

		return $data;
	}
}

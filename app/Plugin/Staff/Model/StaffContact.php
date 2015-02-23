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

class StaffContact extends StaffAppModel {
	public $useTable = 'user_contacts';
	public $actsAs = array(
		'Excel' => array('header' => array('SecurityUser' => array('openemis_no', 'first_name', 'last_name'))),
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'SecurityUser',
		'ContactType',
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
		'contact_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a Contact Type'
			)
		),
		'value' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid value'
			)
		),
		'preferred' => array(
			'comparison' => array(
				'rule' => array('validatePreferred', 'preferred'),
				'allowEmpty' => true,
				'message' => 'Please select a preferred for the selected contact type'
			)
		),
	);

	/* Excel Behaviour */
	public function excelGetFieldLookup() {
		$alias = $this->alias;
		$lookup = array(
			"$alias.preferred" => array(0 => 'No', 1 => 'Yes')
		);
		return $lookup;
	}
	/* End Excel Behaviour */

	function validatePreferred($check1, $field2) {
		$flag = false;
		foreach ($check1 as $key => $value1) {
			$preferred = $this->data[$this->alias][$field2];
			$contactOption = $this->data[$this->alias]['contact_option_id'];
			if ($preferred == "0" && $contactOption != "5") {
				if (isset($this->data[$this->alias]['id'])) {
					$contactId = $this->data[$this->alias]['id'];
					$count = $this->find('count', array('conditions' => array('ContactType.contact_option_id' => $contactOption, array('NOT' => array('StaffContact.id' => array($contactId))))));
					if ($count != 0) {
						$flag = true;
					}
				} else {
					$count = $this->find('count', array('conditions' => array('ContactType.contact_option_id' => $contactOption)));
					if ($count != 0) {
						$flag = true;
					}
				}
			} else {
				$flag = true;
			}
		}
		return $flag;
	}

	public function beforeValidate($options = array()) {
		if (isset($this->data[$this->alias]['contact_option_id'])) {
			$contactOption = $this->data[$this->alias]['contact_option_id'];
			switch ($contactOption) {
				case 1:
				case 2:
				case 3:
					$this->validate['value'] = array('customVal' => array(
							'rule' => 'numeric',
							'required' => true,
							'message' => 'Please enter a valid Numeric value'
					));
					break;
				case 4:
					$this->validate['value'] = array('customVal' => array(
							'rule' => 'email',
							'required' => true,
							'message' => 'Please enter a valid Email'
					));
					break;
				case 5:
					$this->validate['value'] = array('customVal' => array(
							'rule' => 'notEmpty',	
							'required' => true,
							'message' => 'Please enter a valid Value'
					));
					break;
				default:
					break;
			}
		}
		return true;
	}
	
	public function beforeAction() {
		parent::beforeAction();

		$this->fields['security_user_id']['type'] = 'hidden';
		$this->fields['security_user_id']['value'] = $this->Session->read('Staff.security_user_id');
		$this->fields['preferred']['type'] = 'select';
		$this->fields['preferred']['options'] = $this->controller->Option->get('yesno');

		$this->fields['contact_option_id']['type'] = 'select';
		$this->fields['contact_option_id']['visible'] = true;
		$this->fields['contact_option_id']['options'] = $this->ContactType->ContactOption->getOptions();
		$this->fields['contact_option_id']['attr'] = array('onchange' => "$('#reload').click()");

		$currContactOptionID = 1;
		if(!empty($this->request->data)) {
			if (array_key_exists($this->alias, $this->request->data)) {
				if (array_key_exists('contact_option_id', $this->request->data[$this->alias])) {
					$currContactOptionID = $this->request->data[$this->alias]['contact_option_id'];
					if (array_key_exists('submit', $this->request->data) && $this->request->data['submit'] == 'reload') {
						unset($this->request->data[$this->alias]['contact_type_id']);
					}
				}
			}
		}
		$this->fields['contact_type_id']['type'] = 'select';
		if ($this->action == 'edit' || $this->action == 'add') {
			$this->fields['contact_type_id']['options'] = $this->ContactType->getOptions(array('contact_option_id' => $currContactOptionID));
		} else {
			$this->fields['contact_type_id']['options'] = $this->ContactType->getOptions();
		}
		
		$order = 0;
		$this->setFieldOrder('contact_option_id', $order++);
		$this->setFieldOrder('contact_type_id', $order++);
		$this->setFieldOrder('value', $order++);
		$this->setFieldOrder('preferred', $order++);
	}

	public function index() {
		$userId = $this->Session->read('Staff.security_user_id');
		$this->contain(array('ContactType'));
		$data = $this->findAllBySecurityUserId($userId);
		$contactOptions = $this->ContactType->ContactOption->getOptions();
		
		$this->setVar(compact('data', 'contactOptions'));
	}

	public function view($id) {
		$this->render = 'auto';
		if ($this->exists($id)) {
			$this->contain(array('ContactType' => array('ContactOption')));
			$data = $this->findById($id);
			$data[$this->alias]['contact_option_id'] = $data['ContactType']['ContactOption']['name'];

			$this->Session->write($this->alias.'.id', $id);
			$this->setVar(compact('data'));
		} else {
			$this->Message->alert('general.view.notExists');
			return $this->redirect(array('action' => get_class($this)));
		}
	}

	public function add() {
		if (array_key_exists('submit', $this->request->data) && $this->request->data['submit'] == 'reload') {
			$this->render = 'auto';
		} else {
			parent::add();
		}
	}

	public function edit($id) {
		if (array_key_exists('submit', $this->request->data) && $this->request->data['submit'] == 'reload') {
			$this->render = 'auto';
		} else {
			parent::edit($id);
		}
	}
}

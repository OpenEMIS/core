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

class SecurityUserLogin extends SecurityUser {
	public $useTable = 'security_users';
	
	public $actsAs = array('ControllerAction2');
	
	public function beforeAction() {
		parent::beforeAction();
		$this->fields['currentPassword'] = array();
		$this->fields['newPassword'] = array();
		$this->fields['retypeNewPassword'] = array();
		$this->fields['username']['type'] = 'string';
		$this->fields['password']['type'] = 'password';
		foreach ($this->fields['password'] as $key => $value) {
			$this->fields['newPassword'][$key] = $value;
			$this->fields['retypeNewPassword'][$key] = $value;
		}

		$this->fields['id']['type'] = 'hidden';
		foreach ($this->fields as $key => $value) {
			if (!in_array($key, array('id','username', 'password', 'newPassword', 'retypeNewPassword'))) {
				$this->fields[$key]['visible'] = false;
			}
		}

		if (in_array($this->action,array('view', 'edit'))) {
			$this->fields['nav_tabs'] = array(
				'type' => 'element',
				'element' => '../Security/SecurityUser/nav_tabs',
				'override' => true,
				'visible' => true
			);
		}
		$this->setVar('selectedAction', $this->alias);
	}

	public function view($id) {
		$this->redirect(array('controller' => 'Security', 'action' => 'SecurityUser', 'view', $id));
	}

	public function edit($id) {

		$this->fields['password']['visible'] = true;
		$this->fields['password']['value'] = '';
		parent::edit($id);
	}
}

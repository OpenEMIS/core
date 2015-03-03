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

App::uses('AppController', 'Controller');

class PreferencesController extends AppController {
	public $uses = array(
		'ConfigItem',
		'SecurityUser',
		'SecurityGroupUser'
	);

	public $modules = array(
		'SecurityUserLogin'
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Preferences';
		$this->Navigation->addCrumb('Preferences', array('controller' => 'Preferences'));
	}
	
	public function index() {
		return $this->redirect(array('action' => 'account'));
	}
	
	public function account() {
		$this->Navigation->addCrumb('Account');
		$header = __('Account');
		$userId = $this->Auth->user('id');

		$data = $this->SecurityUser->find(
			'first',
			array(
				'contain' => array(
					'UserContact' => array(
						'fields' => array('id', 'value', 'preferred'),
						'ContactType' => array(
							'fields' => array('id', 'name'),
							'ContactOption' => array('fields' => array('id', 'name'))
						)
					),
					'SecurityGroupUser' => array(
						'SecurityGroup' => array('fields'=> array('id', 'name')),
						'SecurityRole' => array('fields'=> array('id', 'name'))
					)
				),
				'conditions' => array(
					'id' => $userId
				)
			)
		);

		$this->set(compact('data','header'));
	}
	
	public function accountEdit() {
		$this->Navigation->addCrumb('Account');
		$header = __('Account');
		$userId = $this->Auth->user('id');
		
		$data = $this->SecurityUser->find(
			'first',
			array(
				'contain' => array(
					'UserContact' => array(
						'fields' => array('id', 'value', 'preferred'),
						'ContactType' => array(
							'fields' => array('id', 'name'),
							'ContactOption' => array('fields' => array('id', 'name'))
						)
					),
					'SecurityGroupUser' => array(
						'SecurityGroup' => array('fields'=> array('id', 'name')),
						'SecurityRole' => array('fields'=> array('id', 'name'))
					)
				),
				'conditions' => array(
					'id' => $userId
				)
			)	
		);
		
		if($this->request->is('post') || $this->request->is('put')) {
			if($this->SecurityUser->save($this->data)) {
				$name = ModelHelper::getName($this->data['SecurityUser']);
				$this->Utility->alert($name . ' '.__('has been updated successfully.'));
				$this->Session->write('Auth.User', array_merge($this->Auth->user(), $this->data['SecurityUser']));
				$this->redirect(array('action' => 'account'));
			} 
		} else {
			$this->request->data = $data;
		}
		$this->set(compact('data', 'header'));
		$this->set('statusOptions', $this->SecurityUser->status);
	}
	
	public function password() {
		$this->Navigation->addCrumb('Password');
		$header = __('Password');
		$allowChangePassword = (bool) $this->ConfigItem->getValue('change_password');
		
		if(!$allowChangePassword) {
			$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'), array('type' => 'warn'));
		}
		$this->set('allowChangePassword', $allowChangePassword);
		
		if($this->request->is('post')) {
			$data = $this->data;
			$data['SecurityUser']['id'] = $this->Auth->user('id');
			$status = array('status' => 'ok', 'msg' => __('Password has been changed.'));
			$error = $this->validateChangePassword($data['SecurityUser']['oldPassword'], $data['SecurityUser']['newPassword'], $data['SecurityUser']['retypePassword']);
			if(!empty($error)){
				$status = array('status' => 'error', 'msg' => __($error));
			}else{
				$oldPasswordHash = $this->Auth->password($data['SecurityUser']['oldPassword'], null, true);
				$newPasswordHash = $this->Auth->password($data['SecurityUser']['newPassword'], null, true);
				unset($data['SecurityUser']['oldPassword']);
				unset($data['SecurityUser']['retypePassword']);
				$data['SecurityUser']['password'] = $data['SecurityUser']['newPassword'];
				unset($data['SecurityUser']['newPassword']);
				
				if(!$this->SecurityUser->save($data)){
					$status = array('status' => 'error', 'msg' => __('Please try again later.'));
				} else {
					$username = $this->Auth->user('username');
					$this->log('[' . $username . '] Changing password from ' . $oldPasswordHash . ' to ' . $newPasswordHash, 'security');
					$status = array('status' => 'ok', 'msg' => __('Password has been changed.'));
				}
			}
			$this->Utility->alert($status['msg'], array('type'=>$status['status']));
		}
		
		$this->set(compact('header'));
	}

	private function validateChangePassword($currentPassword, $newPassword, $retypePassword) {
		$error = '';
		$this->SecurityUser->id = $this->Auth->user('id');
		$user = $this->SecurityUser->read();
		if (empty($currentPassword)) {
			$error = __('Please enter your current password.');
		} elseif (strcmp(trim($user['SecurityUser']['password']), trim($this->Auth->password($currentPassword))) != 0) {
			$error = __('Current password does not match.');
		}
		
		if (empty($error)) {
			if(strlen($newPassword) < 1) {
				$error = __('New password required.');
			}else if(strlen($newPassword) < 6) {
				$error = __('Please enter a min of 6 alpha numeric characters.');
			}else if(preg_match('/^[A-Za-z0-9_]+$/',$newPassword) == 0 || preg_match('/^[A-Za-z0-9_]+$/',$newPassword) ==  false) {
				$error = __('Please enter alpha numeric characters.');
			}else if((strlen($newPassword) != strlen($retypePassword)) || $newPassword != $retypePassword){
				$error = __('Passwords do not match.');
			}
		}
		return $error;
	}
}

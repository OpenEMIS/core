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
}

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

class AreaAdministrativeLevel extends AppModel {
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
	
	public $hasMany = array('AreaAdministrative');
	
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
			$params = $this->controller->params;
			$selectedCountry = isset($params['pass'][1]) ? $params['pass'][1] : 0;
			$maxLevel = $this->find('first', array(
				'fields' => (
					'MAX(AreaAdministrativeLevel.level) AS maxLevel'
				),
				'conditions' => array(
					'AreaAdministrativeLevel.area_administrative_id' => $selectedCountry
				)
			));
			$maxLevelVal = isset($maxLevel[0]['maxLevel']) ? $maxLevel[0]['maxLevel']+1 : 1;

			$this->fields['level']['type'] = 'hidden';
			$this->fields['level']['value'] = $maxLevelVal;
			$this->fields['area_administrative_id']['type'] = 'hidden';
			$this->fields['area_administrative_id']['value'] = $selectedCountry;

			$params = array($selectedCountry);
			$this->setVar(compact('params'));
		} else if ($this->action == 'edit') {
			$this->fields['level']['visible'] = false;
			$this->fields['area_administrative_id']['visible'] = false;
		} else if ($this->action == 'view') {
			$this->fields['area_administrative_id']['visible'] = false;
		}

		$this->Navigation->addCrumb('Area Levels (Administrative)');
		$this->setVar('contentHeader', __('Area Levels (Administrative)'));
    }
	
	public function index() {
		$params = $this->controller->params;

		$this->AreaAdministrative->contain('AreaAdministrativeLevel');
		$countryOptions = $this->AreaAdministrative->find('list', array(
			'conditions' => array(
				'AreaAdministrativeLevel.level' => 0
			)
		));
		if(!empty($countryOptions)) {
			$selectedCountry = isset($params['pass'][1]) ? $params['pass'][1] : key($countryOptions);
			$this->contain();
			$data = $this->find('all', array(
				'conditions' => array(
					'AreaAdministrativeLevel.area_administrative_id' => $selectedCountry,
					'AreaAdministrativeLevel.level >' => 0
				),
				'order' => array('level')
			));

			$this->setVar(compact('countryOptions', 'selectedCountry'));
		} else {
			$data = array();
			$this->Message->alert('general.noData');
		}

		$this->setVar(compact('data'));
	}
}

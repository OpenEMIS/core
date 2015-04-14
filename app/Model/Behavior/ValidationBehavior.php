<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2015-02-10

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

class ValidationBehavior extends ModelBehavior {

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}

	public function compareDate(Model $model, $field = array(), $compareField = null, $equals = false) {
		$alias = $model->alias;
		try {
			$startDate = new DateTime(current($field));
		} catch (Exception $e) {
			return 'Please input a proper date.';
			exit(1);
		}
		if($compareField) {
			try {
				$endDate = new DateTime($model->data[$alias][$compareField]);
			} catch (Exception $e) {
				return 'Please input a proper date on '.(ucwords(str_replace('_', ' ', $compareField)));
				exit(1);
			}
			if($equals) {
				return $endDate >= $startDate;
			} else {
				return $endDate > $startDate;
			}
		} else {
			return true;
		}
	}

	public function changePassword(Model $model, $field, $allowChangeAll) {
		$username = array_key_exists('username', $model->data[$model->alias])? $model->data[$model->alias]['username']: null;
		$password = array_key_exists('password', $model->data[$model->alias])? $model->data[$model->alias]['password']: null;

		if (!$allowChangeAll) {
			if (AuthComponent::user('id') != $model->data[$model->alias]['id']) {
				die('illegal cp');
			}
		} 

		$password = AuthComponent::password($password);
		$count = $model->find('count', array('recursive' => -1, 'conditions' => array('username' => $username, 'password' => $password)));
		return $count==1;
	}	

	public function checkDateInput($value) {
		try {
		    $date = new DateTime($value);
		} catch (Exception $e) {
		    return 'Please input a proper date';
		    exit(1);
		}
		return true;		
	}

}

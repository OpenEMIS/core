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

		$this->loadValidationMessages($Model);
	}

	public function loadValidationMessages($model) {
		$alias = $model->alias;

		if (!empty($model->validate)) {
			foreach ($model->validate as $field => $rules) {
				foreach ($rules as $rule => $attr) {

					// need to check if $attr is an array, else InstitutionSites/dashboard will have errors
					if (is_array($attr) && !isset($attr['message'])) {
						$code = $model->alias . '.' . $field . '.' . $rule;
						if (isset($attr['messageCode'])) {
							$code = $attr['messageCode'] . '.' . $field . '.' . $rule;
						}
						$message = $this->get($code);
						$model->validate[$field][$rule]['message'] = $message;
					}
				}
			}
		}
	}

	public function get($code) {
		$index = explode('.', $code);
		$message = $this->validationMessages;
		
		foreach ($index as $i) {
			if (isset($message[$i])) {
				$message = $message[$i];
			} else {
				$message = '[Message Not Found]';
				break;
			}
		}
		return !is_array($message) ? __($message) : $message;
	}

	public function getValidationMessage(Model $model, $code) {
		return $this->get($code);
	}

	// To check start date is earlier than end date from start date field
	public function compareDate(Model $model, $field = array(), $compareField = null, $equals = false) {
		try {
			$startDate = new DateTime(current($field));
		} catch (Exception $e) {
		    return 'Please input a proper date';
			exit(1);
		}
		if($compareField) {
			$options = array('equals' => $equals, 'reverse' => false);
			return $this->doCompareDates($model, $startDate, $compareField, $options);
		} else {
			return true;
		}
	}

	// To check end date is later than start date from end date field
	public function compareDateReverse(Model $model, $field = array(), $compareField = null, $equals = false) {
		try {
			$endDate = new DateTime(current($field));
		} catch (Exception $e) {
		    return 'Please input a proper date';
			exit(1);
		}
		if($compareField) {
			$options = array('equals' => $equals, 'reverse' => true);
			return $this->doCompareDates($model, $endDate, $compareField, $options);
		} else {
			return true;
		}
	}

	protected function doCompareDates($model, $dateOne, $compareField, $options) {
		$alias = $model->alias;
		$equals = $options['equals'];
		$reverse = $options['reverse'];
		try {
			$dateTwo = new DateTime($model->data[$alias][$compareField]);
		} catch (Exception $e) {
			return 'Please input a proper date for '.(ucwords(str_replace('_', ' ', $compareField)));
			exit(1);
		}
		if($equals) {
			if ($reverse) {
				return $dateOne >= $dateTwo;
			} else {
				return $dateTwo >= $dateOne;
			}
		} else {
			if ($reverse) {
				return $dateOne > $dateTwo;
			} else {
				return $dateTwo > $dateOne;
			}
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

	public function checkDateInput(Model $model, $field = array()) {
		try {
		    $date = new DateTime(current($field));
		} catch (Exception $e) {
		    return 'Please input a proper date';
		    exit(1);
		}
		return true;		
	}

	public $validationMessages = array(
		'general' => array(
			'name' => array('ruleRequired' => 'Please enter a name'),
			'code' => array(
				'ruleRequired' => 'Please enter a code',
				'ruleUnique' => 'Please enter a unique code'
			),
			'title' => array('ruleRequired' => 'Please enter a title'),
			'address' => array(
				'ruleRequired' => 'Please enter a valid Address',
				'ruleMaximum255' => 'Please enter an address within 255 characters'
			),
			'postal_code' => array('ruleRequired' => 'Please enter a Postal Code'),
			'email' => array('ruleRequired' => 'Please enter a valid Email'),
		),

		'InstitutionSite' => array(
			'institution_site_locality_id' => array(
				'ruleRequired' => 'Please select a Locality'
			),
			'institution_site_status_id' => array(
				'ruleRequired' => 'Please select a Status'
			),
			'institution_site_type_id' => array(
				'ruleRequired' => 'Please select a Type'
			),
			'institution_site_ownership_id' => array(
				'ruleRequired' => 'Please select an Ownership'
			),
			'area_id_select' => array(
				'ruleRequired' => 'Please select a valid Area'
			),
			'date_opened' => array(
				'ruleRequired' => 'Please select the Date Opened',
				'ruleCompare' => 'Please select the Date Opened'
			),
			'date_closed' => array(
				'ruleCompare' => 'Date Closed cannot be earlier than Date Opened'
			),
			'longitude' => array(
				'ruleLongitude' => 'Please enter a valid Longitude'
			),
			'latitude' => array(
				'ruleLatitude' => 'Please enter a valid Latitude'
			),
			'institution_site_provider_id' => array(
				'ruleRequired' => 'Please select a Provider'
			),
			'institution_site_sector_id' => array(
				'ruleRequired' => 'Please select a Sector'
			),
			'institution_site_gender_id' => array(
				'ruleRequired' => 'Please select a Gender'
			)
		)



	);
}

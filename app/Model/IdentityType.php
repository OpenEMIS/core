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

App::uses('FieldOptionValue', 'Model');

class IdentityType extends FieldOptionValue {
	public $useTable = 'field_option_values';
	public $hasMany = array('Staff.StaffIdentity', 'Students.StudentIdentity');

	public function getList($customOptions = array()) {
		$list = parent::getList($customOptions);

		if (array_key_exists('country_id', $customOptions)) {
			$Country = ClassRegistry::init('Country');
			$defaultIdentityTypeForCountry = $Country->find(
				'first',
				array(
					'recursive' => -1,
					'contain' => array('IdentityType'=>array('fields'=>array('id'))),
					'conditions' => array('Country.id'=>$customOptions['country_id'])
				)
			);
			
			if (array_key_exists('IdentityType', $defaultIdentityTypeForCountry)) {
				$defaultIdentityTypeForCountry = $defaultIdentityTypeForCountry['IdentityType']['id'];
			}
			// country default will override identitytype default
			// need to find if there is such a value
			foreach ($list as $key => $value) {
				if ($value['value'] == $defaultIdentityTypeForCountry) {
					$foundValue = $value['value']; continue;
				}
			}
			if (isset($foundValue)) {
				foreach ($list as $key => $value) {
					$list[$key]['selected'] = ($value['value'] == $foundValue);
				}
			}
		}

		return $list;
	}

}

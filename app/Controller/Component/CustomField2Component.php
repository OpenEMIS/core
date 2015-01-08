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

class CustomField2Component extends Component {
	public function get($code) {
		$options = array(
			'fieldType' => array(
				1 => __('Label'),
				2 => __('Text'),
				3 => __('Dropdown'),
				4 => __('Checkbox'),
				5 => __('Textarea'),
				6 => __('Number'),
				7 => __('Table')
			),
			'mandatory' => array(
				1 => __('Yes'),
				0 => __('No')
			),
			'unique' => array(
				1 => __('Yes'),
				0 => __('No')
			),
			'visible' => array(
				1 => __('Yes'),
				0 => __('No')
			)
		);
		
		$index = explode('.', $code);
		foreach($index as $i) {
			if(isset($options[$i])) {
				$option = $options[$i];
			} else {
				$option = array('[Option Not Found]');
				break;
			}
		}
		return $option;
	}

    public function getMandatoryDisabled($fieldTypeId=1) {
		$arrMandatory = array(2,5,6);
		if(in_array($fieldTypeId, $arrMandatory)) {
			$result = '';
		} else {
			$result = 'disabled';
		}

		return $result;
    }

	public function getUniqueDisabled($fieldTypeId=1) {
		$arrUnique = array(2,6);
		if(in_array($fieldTypeId, $arrUnique)) {
			$result = '';
		} else {
			$result = 'disabled';
		}
		return $result;
    }
}

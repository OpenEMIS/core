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

class OptionComponent extends Component {
	public function get($code) {
		$options = array(
			'yesno' => array(1 => __('Yes'), 0 => __('No')),
			'bloodtype' => array('O+' => 'O+', 'O-' => 'O-', 'A+' => 'A+', 'A-' => 'A-', 'B+'=>'B+' ,'B-' => 'B-', 'AB+' => 'AB+', 'AB-' => 'AB-'),
			'passfail' => array(1 => __('Passed'), 0 => __('Failed')),
			'enableOptions' => array(0 => __('Disabled'),1 => __('Enabled')),
			'teachOptions' => array(0 => __('Non-Teaching'), 1 => __('Teaching')),
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
}

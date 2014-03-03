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

class MessageComponent extends Component {
	public $components = array('Session', 'Message');
	public $alertTypes = array(
		'ok' => 'alert_ok',
		'error' => 'alert_error',
		'info' => 'alert_info',
		'warn' => 'alert_warn'
	);
	
	public $messages = array(
		'general' => array(
			'error' => 'An unexpected error has been encounted. Please contact the administrator for assistance.',
			'add' => array(
				'success' => 'Record has been added successfully.',
				'failed' => 'Record is not added due to errors encountered.',
			),
			'edit' => array(
				'success' => 'Record has been updated successfully.',
				'failed' => 'Record is not updated due to errors encountered.'
			),
			'view' => array(
				'notExists' => 'The Record does not exist.'
			),
			'delete' => array(
				'success' => 'Record has been deleted successfully.',
				'failed' => 'Record is not deleted due to errors encountered.',
			)
		),
		'security' => array(
			'login' => array(
				'timeout' => 'Your session has timed out. Please login again.',
				'fail' => 'You have entered an invalid username or password.', 
				'inactive' => 'You are not an authorized user.'
			)
		),
		'search' => array(
			'no_result' => 'No result returned from the search.'
		)
	);
	
	public function get($code) {
		$index = explode('.', $code);
		$message = $this->messages;
		foreach($index as $i) {
			if(isset($message[$i])) {
				$message = $message[$i];
			} else {
				$message = '[Message Not Found]';
				break;
			}
		}
		return __($message);
	}
	
	public function alert($code, $settings=array()) {
		$types = $this->alertTypes;
		$_settings = array(
			'type' => key($types),
			'types' => $types,
			'dismissOnClick' => true,
			'params' => array()
		);
		$_settings = array_merge($_settings, $settings);
		if(!array_key_exists($_settings['type'], $types)) {
			$_settings['type'] = key($types);
		}
		$message = $this->get($code);
		if(!empty($_settings['params'])) {
			$message = vsprintf($message, $_settings['params']);
		}
		$_settings['message'] = $message;
		$this->Session->write('_alert', $_settings);
	}
}

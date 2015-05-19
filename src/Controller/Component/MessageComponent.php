<?php
namespace App\Controller\Component;

use Cake\Controller\Component;

class MessageComponent extends Component {
	public $alertTypes = array(
		'ok' => 'alert-success',
		'error' => 'alert-danger',
		'info' => 'alert-info',
		'warn' => 'alert-warning'
	);

	public $messages = array(
		'general' => array(
			'notExists' => array('type' => 'info', 'msg' => 'The record does not exist.'),
			'notEditable' => array('type' => 'warn', 'msg' => 'This record is not editable'),
			'exists' => array('type' => 'error', 'msg' => 'The record is exists in the system.'),
			'noData' => array('type' => 'info', 'msg' => 'There are no records.'),
			'error' => array('type' => 'error', 'msg' => 'An unexpected error has been encounted. Please contact the administrator for assistance.'),
			'add' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been added successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not added due to errors encountered.')
			),
			'edit' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been updated successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not updated due to errors encountered.')
			),
			'delete' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been deleted successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not deleted due to errors encountered.'),
			),
			'duplicate' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been duplicated successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not duplicated due to errors encountered.'),
			),
			'invalidDate' => array('type' => 'error', 'msg' => 'You have entered an invalid date.'),
			'invalidUrl' => array('type' => 'error', 'msg' => 'You have entered an invalid url.'),
			'notSelected' => array('type' => 'error', 'msg' => 'No Record has been selected/saved.')
		),
		'security' => array(
			'login' => array(
				'timeout' => array('type' => 'error', 'msg' => 'Your session has timed out. Please login again.'),
				'fail' => array('type' => 'error', 'msg' => 'You have entered an invalid username or password.'),
				'inactive' => array('type' => 'error', 'msg' => 'You are not an authorized user.')
			),
			'noAuthorization' => array('type' => 'error', 'msg' => 'You are not an authorized user.')
		)
	);

	public function get($code) {
		$index = explode('.', $code);

		$message = $this->messages;		
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
        
	public function alert($code, $settings = array()) {
		$types = $this->alertTypes;
		$_settings = array(
			'type' => key($types),
			'types' => $types,
			'dismissOnClick' => true,
			'params' => array()
		);
		$_settings = array_merge($_settings, $settings);
		$message = $this->get($code);
	
		if (!array_key_exists($_settings['type'], $types)) {
			$_settings['type'] = key($types);
		} else {
			$_settings['type'] = $message['type'];
		}
		if (!empty($_settings['params'])) {
			$message['msg'] = vsprintf($message['msg'], $_settings['params']);
		}
		$_settings['message'] = $message['msg'];

		$session = $this->request->session();
		$session->write('_alert', $_settings);
	}

	public function stopAlert() {
		$session = $this->request->session();
		$session->delete('_alert');
	}
}

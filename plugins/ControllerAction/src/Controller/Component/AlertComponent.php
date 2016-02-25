<?php
namespace ControllerAction\Controller\Component;

use Cake\Controller\Component;
use App\Model\Traits\MessagesTrait;

class AlertComponent extends Component {
	use MessagesTrait;

	public $alertTypes = array(
		'ok' => 'alert-success',
		'error' => 'alert-danger',
		'info' => 'alert-info',
		'warn' => 'alert-warning'
	);

	public function __call($name, $args) {
		$types = [
			'success' => ['class' => 'alert-success'],
			'error' => ['class' => 'alert-danger'],
			'warning' => ['class' => 'alert-warning'],
			'info' => ['class' => 'alert-info']
		];

		$_options = [
			'type' => 'code',
			'closeButton' => true,
			'reset' => false
		];

		if (isset($args[1]) && is_array($args[1])) {
			$_options = array_merge($_options, $args[1]);
		}
		
		if (array_key_exists($name, $types)) {
			$class = $types[$name]['class'];
			$message = '';
			if ($_options['type'] == 'code') {
				$code = $args[0];
				$message = $this->getMessage($code);
			} else {
				$message = $args[0];
			}
			$_options['class'] = $class;
			$_options['message'] = $message;
			
			$session = $this->request->session();
			$alerts = [];

			if ($_options['reset'] && $session->check('_alert')) {
				$session->delete('_alert');
			}

			if ($session->check('_alert')) {
				$alerts = $session->read('_alert');
			}

			$alerts[] = $_options;
			$session->write('_alert', $alerts);
		}
	}

	public function clear() {
		$session = $this->request->session();
		$session->delete('_alert');
	}
}

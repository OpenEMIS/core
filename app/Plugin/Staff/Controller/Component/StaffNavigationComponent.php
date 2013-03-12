<?php
class StaffNavigationComponent extends Component {
	private $controller;
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	public function getLinks($navigation) {
		$controller = 'Staff';

		$links = array(
			array(
				array(
					'_controller' => $controller,
					$navigation->createLink('List of Staff', 'index', array('pattern' => 'index$')),
					$navigation->createLink('Add new Staff', 'add', array('pattern' => 'add$'))
				)
			),
			array(
				'STAFF INFORMATION' => array(
					'_controller' => $controller,
					$navigation->createLink('Details', 'view', array('pattern' => 'view$|^edit$|history$')),
					$navigation->createLink('Attachments', 'attachments'),
					$navigation->createLink('Additional Info', 'additional'),
					$navigation->createLink('Institutions', 'institutions'),
				)
			)
		);
		return array('Staff' => array('controller' => $controller, 'links' => $links));
	}

}
?>

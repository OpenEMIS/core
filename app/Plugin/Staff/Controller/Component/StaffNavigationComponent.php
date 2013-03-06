<?php
class StaffNavigationComponent extends Component {
	private $controller;
	public $components = array('Navigation');
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	public function getLinks() {
		$controller = 'Staff';

		$links = array(
			array(
				array(
					'_controller' => $controller,
					$this->Navigation->createLink('List of Staff', 'index', array('pattern' => 'index$')),
					$this->Navigation->createLink('Add new Staff', 'add', array('pattern' => 'add$'))
				)
			),
			array(
				'STAFF INFORMATION' => array(
					'_controller' => $controller,
					$this->Navigation->createLink('Details', 'view', array('pattern' => 'view$|^edit$|history$')),
					$this->Navigation->createLink('Attachments', 'attachments'),
					$this->Navigation->createLink('Additional Info', 'additional'),
					$this->Navigation->createLink('Institutions', 'institutions'),
				)
			)
		);
		return array('Staff' => array('controller' => $controller, 'links' => $links));
	}

}
?>

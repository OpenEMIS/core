<?php
class TeachersNavigationComponent extends Component {
	private $controller;
	public $components = array('Navigation');
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}

	public function getLinks() {
		$controller = 'Teachers';

		$links = array(
			array(
				array(
					'_controller' => $controller,
					$this->Navigation->createLink('List of Teachers', 'index', array('pattern' => 'index$')),
					$this->Navigation->createLink('Add new Teacher', 'add', array('pattern' => 'add$'))
				)
			),
			array(
				'TEACHER INFORMATION' => array(
					'_controller' => $controller,
					$this->Navigation->createLink('Details', 'view', array('pattern' => 'view$|^edit$|history$')),
					$this->Navigation->createLink('Attachments', 'attachments'),
					$this->Navigation->createLink('Additional Info', 'additional'),
					$this->Navigation->createLink('Institutions', 'institutions')
					//$this->Navigation->createLink('Programmes', 'programmes')
				),
				'OTHER INFORMATION' => array(
					'_controller' => $controller,
					$this->Navigation->createLink('Qualifications', 'qualifications'),
					$this->Navigation->createLink('Training', 'training')
				)
			)
		);
		return array('Teachers' => array('controller' => $controller, 'links' => $links));
	}
}
?>

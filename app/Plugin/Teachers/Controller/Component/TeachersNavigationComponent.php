<?php
class TeachersNavigationComponent extends Component {
	private $controller;
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}

	public function getLinks($navigation) {
		$controller = 'Teachers';

		$links = array(
			array(
				array(
					'_controller' => $controller,
					$navigation->createLink('List of Teachers', 'index', array('pattern' => 'index$')),
					$navigation->createLink('Add new Teacher', 'add', array('pattern' => 'add$'))
				)
			),
			array(
				'TEACHER INFORMATION' => array(
					'_controller' => $controller,
					$navigation->createLink('Details', 'view', array('pattern' => 'view$|^edit$|history$')),
					$navigation->createLink('Attachments', 'attachments'),
					$navigation->createLink('Additional Info', 'additional'),
					$navigation->createLink('Institutions', 'institutions')
					//$this->Navigation->createLink('Programmes', 'programmes')
				),
				'OTHER INFORMATION' => array(
					'_controller' => $controller,
					$navigation->createLink('Qualifications', 'qualifications'),
					$navigation->createLink('Training', 'training')
				)
			)
		);
		return array('Teachers' => array('controller' => $controller, 'links' => $links));
	}
}
?>

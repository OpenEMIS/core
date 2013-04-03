<?php
class StudentsNavigationComponent extends Component {
	private $controller;
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	public function getLinks($navigation) {
		$controller = 'Students';

		$links = array(
			array(
				array(
					'_controller' => $controller,
					$navigation->createLink('List of Students', 'index', array('pattern' => 'index$')),
					$navigation->createLink('Add new Student', 'add', array('pattern' => 'add$'))
				)
			),
			array(
				'STUDENT INFORMATION' => array(
					'_controller' => $controller,
					$navigation->createLink('Details', 'view', array('pattern' => 'view$|^edit$|history$')),
					$navigation->createLink('Attachments', 'attachments'),
					$navigation->createLink('Additional Info', 'additional')
					//$navigation->createLink('Institutions', 'institutions')
				)/*,
				'ASSESSMENT' => array(
					'_controller' => $controller,
					$this->Navigation->createLink('Assessment Results', 'assessments')
				)*/
			)
		);
		return array('Students' => array('controller' => $controller, 'links' => $links));
	}
}
?>

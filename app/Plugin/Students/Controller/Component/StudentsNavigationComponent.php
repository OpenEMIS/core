<?php
class StudentsNavigationComponent extends Component {
	private $controller;
	public $components = array('Navigation');
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	public function getLinks() {
		$controller = 'Students';

		$links = array(
			array(
				array(
					'_controller' => $controller,
					$this->Navigation->createLink('List of Students', 'index', array('pattern' => 'index$')),
					$this->Navigation->createLink('Add new Student', 'add', array('pattern' => 'add$'))
				)
			),
			array(
				'STUDENT INFORMATION' => array(
					'_controller' => $controller,
					$this->Navigation->createLink('Details', 'view', array('pattern' => 'view$|^edit$|history$')),
					$this->Navigation->createLink('Attachments', 'attachments'),
					$this->Navigation->createLink('Additional Info', 'additional'),
					$this->Navigation->createLink('Institutions', 'institutions')
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

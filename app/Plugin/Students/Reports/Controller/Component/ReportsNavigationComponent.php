<?php
class ReportsNavigationComponent extends Component {
	private $controller;
	public $components = array('Navigation');
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	public function getLinks() {
		$controller = 'Reports';

		$links = array(
			array(
				array(
					'_controller' => $controller,
					$this->Navigation->createLink('Institution Reports', 'Institution', array('pattern' => 'index$|Institution')),
					$this->Navigation->createLink('Student Reports', 'Student'),
					$this->Navigation->createLink('Teacher Reports', 'Teacher'),
					$this->Navigation->createLink('Staff Reports', 'Staff'),
					$this->Navigation->createLink('Consolidated Reports', 'Consolidated'),
					$this->Navigation->createLink('Indicator Reports', 'Indicator'),
					$this->Navigation->createLink('Data Quality Reports', 'DataQuality'),
					$this->Navigation->createLink('Custom Reports', 'Custom'),
                    $this->Navigation->createLink('OLAP Report', 'olap')//,
					//$this->Navigation->createLink('Ad Hoc Reports', 'adhoc/', array('pattern' => 'index$'))
				)
			)
		);
		
		
		return array('Reports' => array('controller' => $controller, 'links' => $links));
	}
}
?>

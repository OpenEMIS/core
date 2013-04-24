<?php
class ReportsNavigationComponent extends Component {
	private $controller;
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	public function getLinks($navigation) {
		$controller = 'Reports';

		$links = array(
			array(
				array(
					'_controller' => $controller,
					$navigation->createLink('Institution Reports', 'Institution'),
					$navigation->createLink('Student Reports', 'Student'),
					$navigation->createLink('Teacher Reports', 'Teacher'),
					$navigation->createLink('Staff Reports', 'Staff'),
					$navigation->createLink('Consolidated Reports', 'Consolidated'),
					$navigation->createLink('Indicator Reports', 'Indicator'),
					$navigation->createLink('Data Quality Reports', 'DataQuality'),
					$navigation->createLink('Custom Reports', 'Custom')//,
                    //$navigation->createLink('OLAP Reports', 'olap')//,
					//$navigation->createLink('Ad Hoc Reports', 'adhoc/', array('pattern' => 'index$'))
				)
			)
		);
		$navigation->ignoreLinks($links, 'Reports');
		return array('Reports' => array('controller' => $controller, 'links' => $links));
	}
}
?>

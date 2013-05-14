<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

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

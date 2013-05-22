<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

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
				'INFORMATION' => array(
					'_controller' => $controller,
					$navigation->createLink('General', 'view', array('pattern' => 'view$|^edit$|history$')),
					$navigation->createLink('Attachments', 'attachments'),
					$navigation->createLink('More', 'additional')
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

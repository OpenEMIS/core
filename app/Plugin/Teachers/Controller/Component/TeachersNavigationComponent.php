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

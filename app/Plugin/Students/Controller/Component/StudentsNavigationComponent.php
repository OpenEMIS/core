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
					$navigation->createLink('List of Students', $controller, 'index', 'index$|advanced'),
					$navigation->createLink('Add new Student', $controller, 'add', 'add$')
				)
			),
			array(
				'GENERAL' => array(
					$navigation->createLink('Overview', $controller, 'view', 'view$|^edit$|\bhistory\b'),
					$navigation->createLink('Contacts', $controller, 'contacts'),
					$navigation->createLink('Identities', $controller, 'identities'),
					$navigation->createLink('Nationalities', $controller, 'nationalities'),
					$navigation->createLink('Languages', $controller, 'languages'),
					$navigation->createLink('Bank Accounts', $controller, 'bankAccounts'),
					$navigation->createLink('Comments', $controller, 'comments'),
					$navigation->createLink('Special Needs', $controller, 'special_need', '^special_need'),
					$navigation->createLink('Awards', $controller, 'award', '^award'),
					$navigation->createLink('Attachments', $controller, 'attachments'),
					$navigation->createLink('More', $controller, 'additional','additional|^custFieldYrView$')
				),
				'DETAILS' => array(
                    $navigation->createLink('Classes', $controller, 'classes'),
                    $navigation->createLink('Attendance', $controller, 'attendance'),
                    $navigation->createLink('Behaviour', $controller, 'behaviour','behaviour|^behaviourView$'),
                    $navigation->createLink('Results', $controller, 'assessments'),
					$navigation->createLink('Extracurricular', $controller, 'extracurricular')
                ),
				'HEALTH' => array(
                    $navigation->createLink('Overview', $controller, 'health_view', 'health_view|health_edit'),
                    $navigation->createLink('History', $controller, 'health_history', '^health_history'),
                    $navigation->createLink('Family', $controller, 'health_family', '^health_family'),
                    $navigation->createLink('Immunizations', $controller, 'health_immunization', '^health_immunization'),
					$navigation->createLink('Medications', $controller, 'health_medication', '^health_medication'),
					$navigation->createLink('Allergies', $controller, 'health_allergy', '^health_allergy'),
					$navigation->createLink('Tests', $controller, 'health_test', '^health_test'),
					$navigation->createLink('Consultations', $controller, 'health_consultation', '^health_consultation')
                )
			)
		);
		return array('Students' => array('controller' => $controller, 'links' => $links));
	}
}
?>

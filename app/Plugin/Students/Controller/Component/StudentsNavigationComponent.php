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
					$navigation->createLink('Special Needs', $controller, 'specialNeed', '^specialNeed'),
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
                    $navigation->createLink('Overview', $controller, 'healthView', 'healthView|healthEdit'),
                    $navigation->createLink('History', $controller, 'healthHistory', '^healthHistory'),
                    $navigation->createLink('Family', $controller, 'healthFamily', '^healthFamily'),
                    $navigation->createLink('Immunizations', $controller, 'healthImmunization', '^healthImmunization'),
					$navigation->createLink('Medications', $controller, 'healthMedication', '^healthMedication'),
					$navigation->createLink('Allergies', $controller, 'healthAllergy', '^healthAllergy'),
					$navigation->createLink('Tests', $controller, 'healthTest', '^healthTest'),
					$navigation->createLink('Consultations', $controller, 'healthConsultation', '^healthConsultation')
                )
			)
		);
		return array('Students' => array('controller' => $controller, 'links' => $links));
	}
}
?>

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

class StudentBehaviour extends StudentsAppModel {
    public $actsAs = array(
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution'
                ),
                'Student' => array(
                    'identification_no' => 'Student OpenEMIS ID',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'preferred_name' => ''
                ),
                'StudentBehaviourCategory' => array(
                    'name' => 'Category'
                ),
                'StudentBehaviour' => array(
                    'date_of_behaviour' => 'Date',
                    'title' => 'Title',
                    'description' => 'Description',
                    'action' => 'Action'
                )
            ),
            'fileName' => 'Report_Student_Behaviour'
		)
	);
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->getCSVHeader($this->reportMapping[$index]['fields']);
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		// General > Overview and More
		if ($index == 1) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = $this->getCSVFields($this->reportMapping[$index]['fields']);
			$options['order'] = array('Student.identification_no', 'StudentBehaviour.date_of_behaviour', 'StudentBehaviour.id');
			$options['conditions'] = array('StudentBehaviour.institution_site_id' => $institutionSiteId);

			$options['joins'] = array(
				array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'StudentBehaviour.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'student_behaviour_categories',
                        'alias' => 'StudentBehaviourCategory',
                        'conditions' => array('StudentBehaviour.student_behaviour_category_id = StudentBehaviourCategory.id')
                    ),
                    array(
                        'table' => 'students',
                        'alias' => 'Student',
                        'conditions' => array('StudentBehaviour.student_id = Student.id')
                    )
			);

			$data = $this->find('all', $options);
			
			$newData = array();
			
			foreach ($data AS $row) {
                $row['StudentBehaviour']['date_of_behaviour'] = $this->formatDateByConfig($row['StudentBehaviour']['date_of_behaviour']);
                $newData[] = $row;
            }

			return $newData;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}

}

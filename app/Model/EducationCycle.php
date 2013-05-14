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

App::uses('AppModel', 'Model');

class EducationCycle extends AppModel {
	/*
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name for the Education Cycle.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This name is already exists in the system.'
			)
		),
		'admission_age' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter the admission age'
		)
	);
	*/
	
	public $belongsTo = array('EducationLevel');
	public $hasMany = array('EducationProgramme');
	
	public function getOfficialAgeByGrade($gradeId) {
		$age = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('EducationCycle.admission_age', 'EducationGrade.order'),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.education_cycle_id = EducationCycle.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.education_programme_id = EducationProgramme.id',
						'EducationGrade.id = ' . $gradeId
					)
				)
			)
		));
		return $age['EducationCycle']['admission_age'] + $age['EducationGrade']['order'] - 1;
	}

    public function getCycles() {
        $this->unbindModel(array('hasMany' => array('EducationProgramme'), 'belongsTo' => array('EducationLevel')));
        // $records = $this->find('list', array('conditions' => array('EducationCycle.visible' => 1)));
        $records = $this->find('all', array('conditions' => array('EducationCycle.visible' => 1)));
        $records = $this->formatArray($records);
        return $records;
    }
}

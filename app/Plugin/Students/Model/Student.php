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

class Student extends StudentsAppModel {
    private $debug = false;
	public $actsAs = array(
		'TrackHistory' => array('historyTable' => 'Students.StudentHistory'),
		'CascadeDelete' => array(
			'cascade' => array(
				'Students.StudentAttachment',
				'Students.StudentCustomValue'
			)
		)
	);
	
	public $sqlPaginateCount;
	public $validate = array(
		'first_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid First Name'
			)
		),
		'last_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Last Name'
			)
		),
		'identification_no' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Identification No'
			),
			'ruleUnique' => array(
        		'rule' => 'isUnique',
        		'required' => true,
        		'message' => 'Please enter a unique Identification No'
		    )
		),
		'gender' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', 'not equal', '0'),
				'required' => true,
				'message' => 'Please select a Gender'
			)
		),
		'address' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Address'
			)
		),
		'postal_code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Postal Code'
			)
		),
		'date_of_birth' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Date of Birth'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select a Date of Birth'
			)
		),
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		)
	);
	
	// Used by InstitutionSiteController for searching
	public function search($searchStr, $programmeId, $institutionSiteId, $yearId, $limit=false) {
		$notExists = '
			NOT EXISTS (
				SELECT institution_site_students.student_id 
				FROM institution_site_students
				JOIN institution_site_programmes
					ON institution_site_programmes.id = institution_site_students.institution_site_programme_id
					AND institution_site_programmes.institution_site_id = %d
					AND institution_site_programmes.education_programme_id = %d
					AND institution_site_programmes.school_year_id = %d
				WHERE institution_site_students.student_id = Student.id
			)';
			
		$this->formatResult = true;
		$searchStr = '%' . $searchStr . '%';
		$conditions = array(
			sprintf($notExists, $institutionSiteId, $programmeId, $yearId),
			'OR' => array(
				'Student.identification_no LIKE' => $searchStr,
				'Student.first_name LIKE' => $searchStr,
				'Student.last_name LIKE' => $searchStr
			)
		);
		
		$options = array(
			'recursive' => -1,
			'conditions' => $conditions,
			'order' => array('Student.first_name')
		);
		$count = $this->find('count', $options);
		
		$data = false;
		if($limit === false || $count < $limit) {
			$options['fields'] = array('Student.id, Student.identification_no, Student.first_name, Student.last_name');
			$data = $this->find('all', $options);
		}		
		return $data;
	}
	
	public function paginateJoins(&$conditions) {
		$joins = array();
		
		if(strlen($conditions['SearchKey']) != 0) {
			$joins[] = array(
				'table' => 'student_history',
				'alias' => 'StudentHistory',
				'type' => 'LEFT',
				'conditions' => array('StudentHistory.student_id = Student.id')
			);
		}
		
		if(array_key_exists('InstitutionSiteId', $conditions)) {
			$institutionSiteId = $conditions['InstitutionSiteId'];
			unset($conditions['InstitutionSiteId']);
			
			$programmeConditions = !empty($institutionSiteId) ? implode(',', $institutionSiteId) : 0;
			
			$joins[] = array(
				'table' => 'institution_site_students',
				'alias' => 'InstitutionSiteStudent',
				'type' => 'LEFT',
				'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
			);
			$joins[] = array(
				'table' => 'institution_site_programmes',
				'alias' => 'InstitutionSiteProgramme',
				'type' => !empty($institutionSiteId) ? 'LEFT' : 'INNER',
				'conditions' => array(
					'InstitutionSiteProgramme.id = InstitutionSiteStudent.institution_site_programme_id',
					'InstitutionSiteProgramme.institution_site_id IN (' . $programmeConditions .')'
				)
			);
		}
		return $joins;
	}
	
	public function paginateConditions($conditions) {
		if(strlen($conditions['SearchKey']) != 0) {
			$search = "%".$conditions['SearchKey']."%";
			$conditions['OR'] = array(
				'Student.identification_no LIKE' => $search,
				'Student.first_name LIKE' => $search,
				'Student.last_name LIKE' => $search,
				'StudentHistory.identification_no LIKE' => $search,
				'StudentHistory.first_name LIKE' => $search,
				'StudentHistory.last_name LIKE' => $search
			);
		}
		unset($conditions['SearchKey']);
		return $conditions;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$fields = array(
			'Student.id',
			'Student.identification_no',
			'Student.first_name',
			'Student.last_name',
			'Student.gender',
			'Student.date_of_birth'
		);
		
		if(strlen($conditions['SearchKey']) != 0) {
			$fields[] = 'StudentHistory.id';
			$fields[] = 'StudentHistory.identification_no';
			$fields[] = 'StudentHistory.first_name';
			$fields[] = 'StudentHistory.last_name';
			$fields[] = 'StudentHistory.gender';
			$fields[] = 'StudentHistory.date_of_birth';
		}
		
		$data = $this->find('all', array(
			'fields' => $fields,
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'group' => 'Student.id',
			'order' => $order
		));
		return $data;
	}
        
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$count = $this->find('count', array(
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'group' => 'Student.id'
		));
		return $count;
	}
}
?>
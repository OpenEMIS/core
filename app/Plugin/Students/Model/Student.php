<?php

class Student extends StudentsAppModel {
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
	
	// Used by SetupController
	public function getLookupVariables() {
		$lookup = array();
		
		$StudentCategory = ClassRegistry::init('Students.StudentCategory');
		
		$StudentCategory->formatResult = true;
		$categoryList = $StudentCategory->find('all', array(
			'recursive' => 0,
			'conditions' => array('StudentCategory.id >' => 4), // Not fetching system default categories for editing
			'order' => array('StudentCategory.order')
		));
		$lookup['Category'] = array('model' => 'Students.StudentCategory', 'options' => $categoryList);
		
		return $lookup;
	}
	
	// Used by InstitutionSiteController for searching
	public function search($searchStr, $programmeId, $institutionSiteId, $yearId, $limit=false) {
		$studentTable = 'institution_site_programme_students';
		$programmeTable = 'institution_site_programmes';
		$notExists = '
			NOT EXISTS (
				SELECT institution_site_programme_students.student_id 
				FROM institution_site_programme_students
				JOIN institution_site_programmes
					ON institution_site_programmes.id = institution_site_programme_students.institution_site_programme_id
					AND institution_site_programmes.institution_site_id = %d
					AND institution_site_programmes.education_programme_id = %d
					AND institution_site_programmes.school_year_id = %d
				WHERE institution_site_programme_students.student_id = Student.id
			)';
			
		$this->formatResult = true;
		$ProgrammeStudent = ClassRegistry::init('InstitutionSiteProgrammeStudent');
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
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		//public $hasMany = array('InstitutionSiteStudents');
		//pr($conditions);
		$securityCond = $conditions['Security'];
		
		$this->bindModel(array('hasMany' => array('InstitutionSiteProgrammeStudent')));
	   if($conditions['SearchKey'] != ''){
			$conditions = array( 'OR' => array(
				'Student.identification_no LIKE' => "%".$conditions['SearchKey']."%",
			   'Student.first_name LIKE' => "%".$conditions['SearchKey']."%",
			   'Student.last_name LIKE' =>"%".$conditions['SearchKey']."%",
			   'StudentHistory.identification_no LIKE' => "%".$conditions['SearchKey']."%",
			   'StudentHistory.first_name LIKE' => "%".$conditions['SearchKey']."%",
			   'StudentHistory.last_name LIKE' =>"%".$conditions['SearchKey']."%"
			));
			
			//$conditions = array_merge($conditions,array('AND'=>$securityCond));
			$conditions = array('AND'=>array($conditions,$securityCond));
			
			$data = $this->find('all',array('fields' => array('Student.*','StudentHistory.*'),'joins' => array(
															array(
																'table' => 'student_history',
																'alias' => 'StudentHistory',
																'type' => 'LEFT',
																'conditions' => array(
																	'StudentHistory.student_id = Student.id'
																)
															),
															array(
																'table' => 'institution_site_programme_students',
																'alias' => 'InstitutionSiteProgrammeStudent',
																'type' => 'LEFT',
																'conditions' => array(
																	'InstitutionSiteProgrammeStudent.student_id = Student.id'
																)
															)
														),
											'conditions'=>$conditions,
											'limit' => $limit,
											'offset' => (($page-1)*$limit),
                                            'group' => 'Student.id',
											'order'=>$order));
			$this->sqlPaginateCount = $this->find('count',array( 'joins' => array(
															array(
																'table' => 'student_history',
																'alias' => 'StudentHistory',
																'type' => 'LEFT',
																'conditions' => array(
																	'StudentHistory.student_id = Student.id'
																)
															),
															array(
																'table' => 'institution_site_programme_students',
																'alias' => 'InstitutionSiteProgrammeStudent',
																'type' => 'LEFT',
																'conditions' => array(
																	'InstitutionSiteProgrammeStudent.student_id = Student.id'
																)
															)
														)
														,'conditions'=>$conditions
                                                        ,'group' => 'Student.id'));
	   }else{
		   
		   //$conditions = array_merge($conditions,array('AND'=>$securityCond));
		    $data = $this->find('all',array( 'limit' => $limit,
												'offset' => (($page-1)*$limit),
												'order'=>$order,
												'joins' => array(
																array(
																	'table' => 'institution_site_programme_students',
																	'alias' => 'InstitutionSiteProgrammeStudent',
																	'type' => 'LEFT',
																	'conditions' => array(
																		'InstitutionSiteProgrammeStudent.student_id = Student.id'
																	)
																)
															)
												,'conditions'=>$securityCond
										)
								);
			$this->sqlPaginateCount = $this->find('count',array('joins' => 
																array(
																	array(
																		'table' => 'institution_site_programme_students',
																		'alias' => 'InstitutionSiteProgrammeStudent',
																		'type' => 'LEFT',
																		'conditions' => array(
																			'InstitutionSiteProgrammeStudent.student_id = Student.id'
																		)
																	)
																)
															,'conditions'=>$securityCond)
												);
		   
	   }
	   
	   return $data;
	} 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		return $this->sqlPaginateCount;
	}
}
?>
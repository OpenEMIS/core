<?php

class Teacher extends TeachersAppModel {
	public $actsAs = array(
		'TrackHistory' => array('historyTable' => 'Teachers.TeacherHistory'),
		'CascadeDelete' => array(
			'cascade' => array(
				'Teachers.TeacherAttachment',
				'Teachers.TeacherCustomValue',
				'Teachers.TeacherQualification',
				'Teachers.TeacherTraining'
			)
		)
	);

	public $sqlPaginateCount;
	public $validate = array(
		'first_name' => array(
			'required' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid First Name'
			)
		),
		'last_name' => array(
			'required' => array(
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
		)/*,
		'address_area_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select an Address Area'
			)
		),
		'birthplace_area_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Birthplace Area'
			)
		)*/
	);
	
	public function getLookupVariables() {
		$lookup = array(
			'Categories' => array('model' => 'Teachers.TeacherCategory')
		);
		return $lookup;
	}
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
	   if($conditions['SearchKey'] != ''){
			$conditions = array( 'OR' => array(
				'Teacher.identification_no LIKE' => "%".$conditions['SearchKey']."%",
			   'Teacher.first_name LIKE' => "%".$conditions['SearchKey']."%",
			   'Teacher.last_name LIKE' =>"%".$conditions['SearchKey']."%",
			   'TeacherHistory.identification_no LIKE' => "%".$conditions['SearchKey']."%",
			   'TeacherHistory.first_name LIKE' => "%".$conditions['SearchKey']."%",
			   'TeacherHistory.last_name LIKE' =>"%".$conditions['SearchKey']."%"
			));
			
			$data = $this->find('all',array('fields' => array('Teacher.*','TeacherHistory.*'),'joins' => array(
															array(
																'table' => 'teacher_history',
																'alias' => 'TeacherHistory',
																'type' => 'LEFT',
																'conditions' => array(
																	'TeacherHistory.teacher_id = Teacher.id'
																)
															)
														),
											'conditions'=>$conditions,
											'limit' => $limit,
											'offset' => (($page-1)*$limit),
                                                                                        'group' => 'Teacher.id',
											'order'=>$order));
			$this->sqlPaginateCount = $this->find('count',array( 'joins' => array(
															array(
																'table' => 'teacher_history',
																'alias' => 'TeacherHistory',
																'type' => 'LEFT',
																'conditions' => array(
																	'TeacherHistory.teacher_id = Teacher.id'
																)
															)
														)
														,'conditions'=>$conditions
                                                                                                                ,'group' => 'Teacher.id'));
	   }else{
		    $data = $this->find('all',array( 'limit' => $limit,'offset' => (($page-1)*$limit),'order'=>$order));
			$this->sqlPaginateCount = $this->find('count');
		   
	   }
	   //pr($data);
	   return $data;
	} 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		return $this->sqlPaginateCount;
	}
}
?>
<?php

class Staff extends StaffAppModel {
	public $useTable = 'staff';

	public $actsAs = array(
		'TrackHistory' => array('historyTable' => 'Staff.StaffHistory'),
		'CascadeDelete' => array(
			'cascade' => array(
				'Staff.StaffAttachment',
				'Staff.StaffCustomValue'
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
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
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
			'Categories' => array('model' => 'Staff.StaffCategory')
		);
		return $lookup;
	}
	
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$securityCond = $conditions['Security'];
	   if($conditions['SearchKey'] != ''){
			$conditions = array( 'OR' => array(
			   'Staff.identification_no LIKE' => "%".$conditions['SearchKey']."%",
			   'Staff.first_name LIKE' => "%".$conditions['SearchKey']."%",
			   'Staff.last_name LIKE' =>"%".$conditions['SearchKey']."%",
			   'StaffHistory.identification_no LIKE' => "%".$conditions['SearchKey']."%",
			   'StaffHistory.first_name LIKE' => "%".$conditions['SearchKey']."%",
			   'StaffHistory.last_name LIKE' =>"%".$conditions['SearchKey']."%"
			));
			//$conditions = array_merge($conditions,array('AND'=>$securityCond));
			$conditions = array('AND'=>array($conditions,$securityCond));
			$data = $this->find('all',array('fields' => array('Staff.*','StaffHistory.*'),'joins' => array(
															array(
																'table' => 'staff_history',
																'alias' => 'StaffHistory',
																'type' => 'LEFT',
																'conditions' => array(
																	'StaffHistory.staff_id = Staff.id'
																)
															),
															array(
																'table' => 'institution_site_staff',
																'alias' => 'InstitutionSiteStaff',
																'type' => 'LEFT',
																'conditions' => array(
																	'InstitutionSiteStaff.staff_id = Staff.id'
																)
															)
														),
											'conditions'=>$conditions,
											'limit' => $limit,
											'offset' => (($page-1)*$limit),
                                            'group' => 'Staff.id',
											'order'=>$order));
			$this->sqlPaginateCount = $this->find('count',array( 'joins' => array(
															array(
																'table' => 'staff_history',
																'alias' => 'StaffHistory',
																'type' => 'LEFT',
																'conditions' => array(
																	'StaffHistory.staff_id = Staff.id'
																)
															),
															array(
																'table' => 'institution_site_staff',
																'alias' => 'InstitutionSiteStaff',
																'type' => 'LEFT',
																'conditions' => array(
																	'InstitutionSiteStaff.staff_id = Staff.id'
																)
															)
														)
														,'conditions'=>$conditions
                                                        ,'group' => 'Staff.id'));
	   }else{
		    $data = $this->find('all',array( 'limit' => $limit,'offset' => (($page-1)*$limit),'order'=>$order,'joins' => array(
																array(
																	'table' => 'institution_site_staff',
																	'alias' => 'InstitutionSiteStaff',
																	'type' => 'LEFT',
																	'conditions' => array(
																		'InstitutionSiteStaff.staff_id = Staff.id'
																	)
																)
															)
												,'conditions'=>$securityCond));
			
			$this->sqlPaginateCount = $this->find('count',array('joins' => array(
																array(
																	'table' => 'institution_site_staff',
																	'alias' => 'InstitutionSiteStaff',
																	'type' => 'LEFT',
																	'conditions' => array(
																		'InstitutionSiteStaff.staff_id = Staff.id'
																	)
																)
															)
												,'conditions'=>$securityCond));
		   
	   }
	   //pr($data);
	   return $data;
	} 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		return $this->sqlPaginateCount;
	}
}
?>
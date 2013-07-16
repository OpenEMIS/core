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
			'Positions' => array('model' => 'Teachers.TeacherCategory')
		);
		return $lookup;
	}
	
	public function search($search, $params=array()) {
		$model = $this->alias;
		$data = array();
		$search = '%' . $search . '%';
		$limit = isset($params['limit']) ? $params['limit'] : false;
		
		$conditions = array(
			'OR' => array(
				$model . '.identification_no LIKE' => $search,
				$model . '.first_name LIKE' => $search,
				$model . '.last_name LIKE' => $search
			)
		);
		
		$options = array(
			'recursive' => -1,
			'conditions' => $conditions,
			'order' => array($model . '.first_name')
		);
		
		$count = $this->find('count', $options);
		
		$data = false;
		if($limit === false || $count < $limit) {
			$options['fields'] = array($model . '.*');
			$data = $this->find('all', $options);
		}
		return $data;
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

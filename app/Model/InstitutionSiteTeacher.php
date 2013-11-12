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

App::uses('AppModel', 'Model');

class InstitutionSiteTeacher extends AppModel {
	public $belongsTo = array('TeacherStatus', 'TeacherCategory');
	
	public function isPositionNumberExists($positionNo, $startDate) {
		$this->formatResult = true;
		$data = $this->find('first', array(
			'fields' => array(
				'Teacher.first_name AS first_name', 'Teacher.last_name AS last_name',
				'Institution.name AS institution_name', 'InstitutionSite.name AS institution_site_name'
			),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'teachers',
					'alias' => 'Teacher',
					'conditions' => array('Teacher.id = InstitutionSiteTeacher.teacher_id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.id = InstitutionSiteTeacher.institution_site_id')
				),
				array(
					'table' => 'institutions',
					'alias' => 'Institution',
					'conditions' => array('Institution.id = InstitutionSite.institution_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteTeacher.position_no LIKE' => $positionNo,
				'OR' => array(
					'InstitutionSiteTeacher.end_date >' => $startDate,
					'InstitutionSiteTeacher.end_date IS NULL'
				)
			)
		));
		return $data;
	}
	
	public function saveEmployment($data, $institutionSiteId, $teacherId) {
		$categoryList = array();
		$startDateList = array();
		$index = 0;
		foreach($data as $i => &$obj) {
			$obj['institution_site_id'] = $institutionSiteId;
			$obj['teacher_id'] = $teacherId;
			$obj['start_year'] = date('Y', strtotime($obj['start_date']));
			if(strtotime($obj['end_date']) < 0) {
				unset($obj['end_date']);
			} else {
				$obj['end_year'] = date('Y', strtotime($obj['end_date']));
			}
		}
		$this->saveMany($data);
	}
	
	public function getPositions($teacherId, $institutionSiteId=0) {
		$fields = array(
			'InstitutionSiteTeacher.id', 'InstitutionSiteTeacher.position_no', 'InstitutionSiteTeacher.no_of_hours',
			'InstitutionSiteTeacher.start_date', 'InstitutionSiteTeacher.end_date', 'InstitutionSiteTeacher.teacher_status_id',
			'InstitutionSiteTeacher.salary', 'TeacherCategory.name', 'TeacherStatus.name'
		);
		
		$joins = array();
		$conditions = array('InstitutionSiteTeacher.teacher_id' => $teacherId);
		
		if($institutionSiteId==0) {
			$fields[] = 'Institution.name AS institution';
			$fields[] = 'InstitutionSite.name as institution_site';
			
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = InstitutionSiteTeacher.institution_site_id')
			);
			$joins[] = array(
				'table' => 'institutions',
				'alias' => 'Institution',
				'conditions' => array('Institution.id = InstitutionSite.institution_id')
			);
		} else {
			$conditions['InstitutionSiteTeacher.institution_site_id'] = $institutionSiteId;
		}
		
		$data = $this->find('all', array(
			'fields' => $fields,
			'joins' => $joins,
			'conditions' => $conditions,
			'order' => array('InstitutionSiteTeacher.start_date DESC', 'InstitutionSiteTeacher.end_date')
		));
		return $data;
	}
	
	public function getData($id) {
        $options['joins'] = array(
            array('table' => 'institution_sites',
                'alias' => 'InstitutionSite',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSite.id = InstitutionSiteTeacher.institution_site_id'
                )
            ),
            array('table' => 'institutions',
                'alias' => 'Institution',
                'type' => 'LEFT',
                'conditions' => array(
                    'Institution.id = InstitutionSite.institution_id'
                )
            )
        );

        $options['conditions'] = array('InstitutionSiteTeacher.teacher_id' => $id);

        $options['fields'] = array(
            'InstitutionSite.name',
            'Institution.id',
            'Institution.name',
            'Institution.code',
            'InstitutionSiteTeacher.id',
            'InstitutionSiteTeacher.institution_site_id',
            'InstitutionSiteTeacher.start_date',
            'InstitutionSiteTeacher.end_date',
        );

        $list = $this->find('all', $options);

        return $list;
    }

    public function getInstitutionSelectionValues($list) {
        $InstitutionSite = ClassRegistry::init('InstitutionSite');
        return $data = $InstitutionSite->find('all',array('fields'=>array('InstitutionSite.id','Institution.name','InstitutionSite.name'),'conditions'=>array('InstitutionSite.id  '=>$list)));
    }
	
	// Used by institution site classes
	public function getTeacherSelectList($year, $institutionSiteId, $classId) {
        // Filtering section
        $InstitutionSiteClassTeacher = ClassRegistry::init('InstitutionSiteClassTeacher');
        $teachersExclude = $InstitutionSiteClassTeacher->getTeachers($classId);
        $ids = '';
        foreach($teachersExclude as $obj){
            $ids .= $obj['Teacher']['id'].',';
        }
        $ids = rtrim($ids,',');
        if($ids!=''){
            $conditions = 'Teacher.id NOT IN (' . $ids . ')';
        }else{
            $conditions = '';
        }
        // End filtering

		$data = $this->find('all', array(
			'fields' => array(
				'Teacher.id', 'Teacher.identification_no', 'Teacher.first_name', 
				'Teacher.last_name', 'Teacher.gender'
			),
			'joins' => array(
				array(
					'table' => 'teachers',
					'alias' => 'Teacher',
					'conditions' => array('Teacher.id = InstitutionSiteTeacher.teacher_id',$conditions)
				)
			),
			'conditions' => array(
				'InstitutionSiteTeacher.institution_site_id' => $institutionSiteId,
				'InstitutionSiteTeacher.start_year <=' => $year,
				'OR' => array(
					'InstitutionSiteTeacher.end_year >=' => $year,
					'InstitutionSiteTeacher.end_year IS NULL'
				)
			),
			'group' => array('Teacher.id'),
			'order' => array('Teacher.first_name')
		));
		return $data;
	}
	
	public function paginateJoins($conditions) {
		$year = $conditions['year'];
		$joins = array(
			array(
				'table' => 'teachers',
				'alias' => 'Teacher',
				'conditions' => array('Teacher.id = InstitutionSiteTeacher.teacher_id')
			)
		);
		return $joins;
	}
	
	public function paginateConditions($conditions) {
		if(isset($conditions['year'])) {
			$year = $conditions['year'];
			unset($conditions['year']);
			
			if(strlen($year)>0) {
				$conditions = array_merge($conditions, array( // if the year falls between the start and end date
					'InstitutionSiteTeacher.start_year <=' => $year,
					'OR' => array(
						'InstitutionSiteTeacher.end_year >=' => $year,
						'InstitutionSiteTeacher.end_year IS NULL'
					)
				));
			}
		}
		return $conditions;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$data = $this->find('all', array(
			'fields' => array('Teacher.id', 'Teacher.identification_no', 'Teacher.first_name', 'Teacher.last_name', 'TeacherCategory.name'),
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'order' => $order,
			'group' => array('Teacher.id')
		));
		return $data;
	}
	 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$count = $this->find('count', array(
			'joins' => $this->paginateJoins($conditions), 
			'conditions' => $this->paginateConditions($conditions)
		));
		return $count;
	}
}

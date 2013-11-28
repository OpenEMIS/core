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

class InstitutionSiteStaff extends AppModel {
	public $useTable = 'institution_site_staff';
	public $belongsTo = array('StaffStatus', 'StaffCategory');
	
	public function isPositionNumberExists($positionNo, $startDate) {
		$this->formatResult = true;
		$yr = $startDate['year'];
		$mth = $startDate['month'];
		$day = $startDate['day'];
		
		while(!checkdate($mth, $day, $yr)) {
			$day--;
		}
		$date = sprintf('%d-%d-%d', $yr, $mth, $day);
		$data = $this->find('first', array(
			'fields' => array(
				'Staff.first_name AS first_name', 'Staff.last_name AS last_name',
				'Institution.name AS institution_name', 'InstitutionSite.name AS institution_site_name'
			),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('Staff.id = InstitutionSiteStaff.staff_id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.id = InstitutionSiteStaff.institution_site_id')
				),
				array(
					'table' => 'institutions',
					'alias' => 'Institution',
					'conditions' => array('Institution.id = InstitutionSite.institution_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteStaff.position_no LIKE' => $positionNo,
				'OR' => array(
					'InstitutionSiteStaff.end_date >' => $date,
					'InstitutionSiteStaff.end_date IS NULL'
				)
			)
		));
		return $data;
	}
	
	public function saveEmployment($data, $institutionSiteId, $staffId) {
		$categoryList = array();
		$startDateList = array();
		$index = 0;
		foreach($data as $i => &$obj) {
			$obj['institution_site_id'] = $institutionSiteId;
			$obj['staff_id'] = $staffId;
			$obj['start_year'] = date('Y', strtotime($obj['start_date']));
			if(strtotime($obj['end_date']) < 0) {
				unset($obj['end_date']);
			} else {
				$obj['end_year'] = date('Y', strtotime($obj['end_date']));
			}
		}
		$this->saveMany($data);
	}
	
	public function getPositions($staffId, $institutionSiteId=0) {
		$fields = array(
			'InstitutionSiteStaff.id', 'InstitutionSiteStaff.position_no', 'InstitutionSiteStaff.FTE',
			'InstitutionSiteStaff.start_date', 'InstitutionSiteStaff.end_date', 'InstitutionSiteStaff.staff_status_id',
			'StaffCategory.name', 'StaffStatus.name'
		);
		
		$joins = array();
		$conditions = array('InstitutionSiteStaff.staff_id' => $staffId);
		
		if($institutionSiteId==0) {
			$fields[] = 'Institution.name AS institution';
			$fields[] = 'InstitutionSite.name as institution_site';
			
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = InstitutionSiteStaff.institution_site_id')
			);
			$joins[] = array(
				'table' => 'institutions',
				'alias' => 'Institution',
				'conditions' => array('Institution.id = InstitutionSite.institution_id')
			);
		} else {
			$conditions['InstitutionSiteStaff.institution_site_id'] = $institutionSiteId;
		}
		
		$data = $this->find('all', array(
			'fields' => $fields,
			'joins' => $joins,
			'conditions' => $conditions,
			'order' => array('InstitutionSiteStaff.start_date DESC', 'InstitutionSiteStaff.end_date')
		));
		return $data;
	}
	
	public function getData($id) {
		$options['joins'] = array(
            array('table' => 'institution_sites',
                'alias' => 'InstitutionSite',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSite.id = InstitutionSiteStaff.institution_site_id'
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

        $options['conditions'] = array('InstitutionSiteStaff.staff_id' => $id);

        $options['fields'] = array(
        	'InstitutionSite.name',
            'Institution.id',
        	'Institution.name',
        	'Institution.code',
            'InstitutionSiteStaff.id',
            'InstitutionSiteStaff.institution_site_id',
            'InstitutionSiteStaff.start_date',
            'InstitutionSiteStaff.end_date',
        );

		$list = $this->find('all', $options);

		return $list;
	}

    public function getInstitutionSelectionValues($list) {
        $InstitutionSite = ClassRegistry::init('InstitutionSite');
        return $data = $InstitutionSite->find('all',array('fields'=>array('InstitutionSite.id','Institution.name','InstitutionSite.name'),'conditions'=>array('InstitutionSite.id  '=>$list)));
    }
	
	public function paginateJoins($conditions) {
		$year = $conditions['year'];
		$joins = array(
			array(
				'table' => 'staff',
				'alias' => 'Staff',
				'conditions' => array('Staff.id = InstitutionSiteStaff.staff_id')
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
					'InstitutionSiteStaff.start_year <=' => $year,
					'OR' => array(
						'InstitutionSiteStaff.end_year >=' => $year,
						'InstitutionSiteStaff.end_year IS NULL'
					)
				));
			}
		}
		return $conditions;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$data = $this->find('all', array(
			'fields' => array('Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.last_name', 'StaffCategory.name'),
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'order' => $order,
			'group' => array('Staff.id')
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

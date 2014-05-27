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
	public $fteOptions = array(10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100);
	public $actsAs = array('ControllerAction');
	public $useTable = 'institution_site_staff';
	public $belongsTo = array('StaffStatus',/* 'StaffCategory',*/ 'InstitutionSitePosition'/*'StaffPositionTitle', 'StaffPositionGrade', 'StaffPositionStep'*/);
	
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
				'Staff.first_name AS first_name', 'Staff.middle_name AS middle_name', 'Staff.last_name AS last_name',
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
			'StaffCategory.name', 'StaffStatus.name', 
                        'StaffPositionTitle.name', 'StaffPositionGrade.name', 'StaffPositionStep.name'
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
			'fields' => array('Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.last_name', 'Staff.preferred_name', 'StaffCategory.name'),
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
	
	
	
	 public function staff($controller, $params) {
        $controller->Navigation->addCrumb('List of Staff');
        $page = isset($params->named['page']) ? $params->named['page'] : 1;
        $model = 'Staff';
        $orderBy = $model . '.first_name';
        $order = 'asc';
        $yearOptions = $controller->SchoolYear->getYearListValues('start_year');
        $selectedYear = isset($params['pass'][0]) ? $params['pass'][0] : '';
        $prefix = sprintf('InstitutionSite%s.List.%%s', $model);
        if ($controller->request->is('post')) {
            $selectedYear = $controller->request->data[$model]['school_year'];
            $orderBy = $controller->request->data[$model]['orderBy'];
            $order = $controller->request->data[$model]['order'];

            $controller->Session->write(sprintf($prefix, 'order'), $order);
            $controller->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
        } else {
            if ($controller->Session->check(sprintf($prefix, 'orderBy'))) {
                $orderBy = $controller->Session->read(sprintf($prefix, 'orderBy'));
            }
            if ($controller->Session->check(sprintf($prefix, 'order'))) {
                $order = $controller->Session->read(sprintf($prefix, 'order'));
            }
        }
        $conditions = array('year' => $selectedYear, 'InstitutionSiteStaff.institution_site_id' => $controller->institutionSiteId);

        $controller->paginate = array('limit' => 15, 'maxLimit' => 100, 'order' => sprintf('%s %s', $orderBy, $order));
        $data = $controller->paginate('InstitutionSiteStaff', $conditions);

        // Checking if user has access to add
        $_add_staff = $controller->AccessControl->check('InstitutionSites', 'staffAdd');
        $controller->set('_add_staff', $_add_staff);
        // End Access Control

		$controller->set(compact('page', 'orderBy', 'order', 'yearOptions', 'selectedYear', 'data'));
        /*$controller->set('page', $page);
        $controller->set('orderBy', $orderBy);
        $controller->set('order', $order);
        $controller->set('yearOptions', $yearOptions);
        $controller->set('selectedYear', $selectedYear);
        $controller->set('data', $data);*/
    }
	
	
	public function staffAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Staff');
		
		
		$positionOptions = $this->InstitutionSitePosition->getInstitutionSitePositionList();
		$selectedPositionId = !empty($positionOptions)?key($positionOptions):0;
		
		$FTEOtpions = $this->getFTEOptions($selectedPositionId, array('date' => date("Y-m-d")));
		
		$statusOptions = $this->StaffStatus->findList(true);
		
		$controller->set(compact('positionOptions', 'FTEOtpions', 'statusOptions'));
      //  $yearRange = $controller->SchoolYear->getYearRange();
       // $categoryOptions = $this->StaffCategory->findList(true);
       // $positionTitleptions = $this->StaffPositionTitle->findList(true);
       // $positionGradeOptions = $this->StaffPositionGrade->findList(true);
      //  $positionStepOptions = $this->StaffPositionStep->findList(true);
       // $statusOptions = $this->StaffStatus->findList(true);

       // $this->set('minYear', current($yearRange));
      //  $this->set('maxYear', array_pop($yearRange));
        //$this->set('categoryOptions', $categoryOptions);
       // $this->set('positionTitleptions', $positionTitleptions);
       // $this->set('positionGradeOptions', $positionGradeOptions);
       // $this->set('positionStepOptions', $positionStepOptions);
      //  $this->set('statusOptions', $statusOptions);
    }
	
	public function staffAjaxRetriveUpdatedFTE($controller, $params) {
		$this->render = false;
		if ($controller->request->is('ajax')) {
			$positionId = empty($controller->request->query['positionId']) ? $params['pass'][0] : $controller->request->query['positionId'];

			$selectedDate = empty($controller->request->query['selectedDate']) ? null : $controller->request->query['selectedDate'];
		
			$FTEOtpions = $this->getFTEOptions($positionId, array('date' => $selectedDate));
			$returnString = '';

			foreach ($FTEOtpions as $obj) {
				$returnString .= '<option value="' . $obj . '">' . $obj . '</option>';
			}
			echo $returnString;
		}
	}

	public function getFTEOptions($positionId, $options = array()){// $FTE_value = 0, $date = null, $includeSelfNum = false, $showAllFTE = false) {
       
        $options['showAllFTE'] = !empty($options['showAllFTE'])? $options['showAllFTE']: false;
        $options['includeSelfNum'] = !empty($options['includeSelfNum'])? $options['includeSelfNum']: false;
        $options['FTE_value'] = !empty($options['FTE_value'])? $options['FTE_value']: 0;
        $options['date'] = !empty($options['date'])? $options['date']: null;
       
        if ($options['showAllFTE']) {
            foreach ($this->fteOptions as $obj) {
                $filterFTEOptions[$obj] = $obj;
            }
        } else {
            $conditions = array('AND' => array('institution_site_position_id' => $positionId));
            if(!empty($options['date'])){
                $conditions['AND'][] = array('start_date <= ' => $options['date'], 'OR' => array('end_date >= ' => $options['date'], 'end_date is null'));
            }
            
			$data = $this->find('all', array(
				'conditions' =>$conditions,
				'fields' => array('COALESCE(SUM(FTE),0) as totalFTE', 'institution_site_position_id'),
				'group' => array('institution_site_position_id'),
					)
			);

            $totalFTE = empty($data[0][0]['totalFTE'])? 0: $data[0][0]['totalFTE'] * 100;
            $remainingFTE = 100 - intval($totalFTE);
            $remainingFTE = ($remainingFTE < 0) ? 0 : $remainingFTE;

            $highestFTE = (($remainingFTE > $options['FTE_value']) ? $remainingFTE : $options['FTE_value']);

            $filterFTEOptions = array();

            foreach ($this->fteOptions as $obj) {
                if ($highestFTE >= $obj) {
                    $filterFTEOptions[$obj] = $obj;
                }
            }

            if ($totalFTE > 0 && $options['includeSelfNum']) {
                $filterFTEOptions[$options['FTE_value']] = $options['FTE_value'];
            }
        }

        return $filterFTEOptions;
    }

	
	public function staffSearch($controller, $params) {
        //$this->layout = 'ajax';
		//$this->render = false;
		//if($controller->request->is('ajax')){
			$search = trim($params->query['searchString']);
			$params = array('limit' => 100);
			$Staff = ClassRegistry::init('Staff.Staff');
			$data = $Staff->search($search, $params);
			//pr($data);
			$controller->set(compact('search','data'));
		//}
        
    }
	
	public function getAutoCompleteList($search,  $institutionSiteId) {
        $search = sprintf('%%%s%%', $search);
		
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT Staff.id', 'Staff.*'),
			'joins' => array(
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('InstitutionSiteStaff.staff_id = Staff.id')
				)
			),
			'conditions' => array(
				'OR' => array(
					'Staff.first_name LIKE' => $search,
					'Staff.last_name LIKE' => $search,
					'Staff.middle_name LIKE' => $search,
					'Staff.preferred_name LIKE' => $search,
					'Staff.identification_no LIKE' => $search
				),
				'InstitutionSiteStaff.institution_site_id' => $institutionSiteId
			),
			'order' => array('Staff.first_name', 'Staff.middle_name', 'Staff.last_name', 'Staff.preferred_name')
		));

        $data = array();
        foreach ($list as $obj) {
            $staff = $obj['Staff'];
            $data[] = array(
                'label' => sprintf('%s - %s %s %s %s', $staff['identification_no'], $staff['first_name'], $staff['middle_name'], $staff['last_name'], $staff['preferred_name']),
                'value' => $staff['id']
            );
        }
        return $data;
    }
	
	public function getStaffByInstitutionSite($institutionSiteId, $startDate, $endDate) {
		//$startYear = date('Y', strtotime($startDate));
		//$endYear = date('Y', strtotime($endDate));
		
		$conditions = array(
			'InstitutionSiteStaff.institution_site_id = ' . $institutionSiteId
		);
		
//		$conditions['OR'] = array(
//				array(
//					'InstitutionSiteStaff.start_year <= "' . $endYear . '"',
//					'InstitutionSiteStaff.end_year IS NULL'
//				),
//				array(
//					'InstitutionSiteStaff.start_year <= "' . $endYear . '"',
//					'InstitutionSiteStaff.end_year >= "' . $startYear . '"',
//					'InstitutionSiteStaff.end_year IS NOT NULL'
//				)
//		);
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT Staff.id',
				'Staff.identification_no',
				'Staff.first_name',
				'Staff.middle_name',
				'Staff.last_name',
				'Staff.preferred_name'
			),
			'joins' => array(
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('InstitutionSiteStaff.Staff_id = Staff.id')
				)
			),
			'conditions' => $conditions
		));
		
		return $data;
	}
}

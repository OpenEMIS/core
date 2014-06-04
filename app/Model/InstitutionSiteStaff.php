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
	public $actsAs = array('ControllerAction', 'DatePicker' => array('start_date', 'end_date'));
	public $useTable = 'institution_site_staff';
	public $belongsTo = array(
		'InstitutionSite',
		'Staff.Staff',
		'StaffStatus', /* 'StaffCategory', */
		'InstitutionSitePosition', /* 'StaffPositionTitle', 'StaffPositionGrade', 'StaffPositionStep' */
		'StaffType' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_type_id'
		)
	);
	
	
	public function compareDate($field = array(), $compareField = null) {
        $startDate = new DateTime(current($field));
        $endDate = new DateTime($this->data[$this->name][$compareField]);
        return $endDate >= $startDate;
    }

	public function isPositionNumberExists($positionNo, $startDate) {
		$this->formatResult = true;
		$yr = $startDate['year'];
		$mth = $startDate['month'];
		$day = $startDate['day'];

		while (!checkdate($mth, $day, $yr)) {
			$day--;
		}
		$date = sprintf('%d-%d-%d', $yr, $mth, $day);
		$data = $this->find('first', array(
			'fields' => array(
				'Staff.first_name AS first_name', 'Staff.middle_name AS middle_name', 'Staff.last_name AS last_name',
				/*'Institution.name AS institution_name',*/ 'InstitutionSite.name AS institution_site_name'
			),
			'recursive' => -1,
			/* 'joins' => array(
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
			  ), */
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
		foreach ($data as $i => &$obj) {
			$obj['institution_site_id'] = $institutionSiteId;
			$obj['staff_id'] = $staffId;
			$obj['start_year'] = date('Y', strtotime($obj['start_date']));
			if (strtotime($obj['end_date']) < 0) {
				unset($obj['end_date']);
			} else {
				$obj['end_year'] = date('Y', strtotime($obj['end_date']));
			}
		}
		$this->saveMany($data);
	}

	public function getPositions($staffId, $institutionSiteId = 0) {
		$this->unbindModel(array('belongsTo' => array('InstitutionSitePosition')));
		
		$fields = array(
			'InstitutionSiteStaff.id', 'InstitutionSiteStaff.position_no', 'InstitutionSiteStaff.FTE',
			'InstitutionSiteStaff.start_date', 'InstitutionSiteStaff.end_date', 'InstitutionSiteStaff.staff_status_id', 'StaffType.name',
			/* 'StaffCategory.name', */ 'StaffStatus.name', 'InstitutionSitePosition.id', 'InstitutionSitePosition.position_no', 'StaffPositionTitle.name', 'StaffPositionGrade.name'
			/*'StaffPositionTitle.name', 'StaffPositionGrade.name', 'StaffPositionStep.name'*/
		);

		$joins = array(
			array(
				'table' => 'institution_site_positions',
				'alias' => 'InstitutionSitePosition',
				//'type' => 'LEFT',
				'conditions' => array('InstitutionSitePosition.id = InstitutionSiteStaff.institution_site_position_id')
			),
			array(
				'table' => 'staff_position_titles',
				'alias' => 'StaffPositionTitle',
				'type' => 'LEFT',
				'conditions' => array('StaffPositionTitle.id = InstitutionSitePosition.staff_position_title_id')
			),
			array(
				'table' => 'staff_position_grades',
				'alias' => 'StaffPositionGrade',
				'type' => 'LEFT',
				'conditions' => array('StaffPositionGrade.id = InstitutionSitePosition.staff_position_grade_id')
			)
		);
		$conditions = array('InstitutionSiteStaff.staff_id' => $staffId);

		if ($institutionSiteId == 0) {
			//$fields[] = 'Institution.name AS institution';
			$fields[] = 'InstitutionSite.name as institution_site';
			
			/* $joins[] = array(
			  'table' => 'institution_sites',
			  'alias' => 'InstitutionSite',
			  'conditions' => array('InstitutionSite.id = InstitutionSiteStaff.institution_site_id')
			  );
			  $joins[] = array(
			  'table' => 'institutions',
			  'alias' => 'Institution',
			  'conditions' => array('Institution.id = InstitutionSite.institution_id')
			  ); */
		} else {
			$this->unbindModel(array('belongsTo' => array('InstitutionSite')));
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

	/*public function getData($id) {
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
	}*/

	public function getInstitutionSelectionValues($list) {
		$InstitutionSite = ClassRegistry::init('InstitutionSite');
		return $data = $InstitutionSite->find('all', array('fields' => array('InstitutionSite.id', 'Institution.name', 'InstitutionSite.name'), 'conditions' => array('InstitutionSite.id  ' => $list)));
	}

	public function paginateJoins($conditions) {
		$year = $conditions['year'];
		$joins = array();
		$joins = array(
			/* array(
			  'table' => 'staff',
			  'alias' => 'Staff',
			  'conditions' => array('Staff.id = InstitutionSiteStaff.staff_id')
			  ), */
			array(
				'table' => 'institution_site_positions',
				'alias' => 'InstitutionSitePosition',
				//'type' => 'LEFT',
				'conditions' => array('InstitutionSitePosition.id = InstitutionSiteStaff.institution_site_position_id')
			),
			array(
				'table' => 'staff_position_titles',
				'alias' => 'StaffPositionTitle',
				'type' => 'LEFT',
				'conditions' => array('StaffPositionTitle.id = InstitutionSitePosition.staff_position_title_id')
			)
		);
		return $joins;
	}

	public function paginateConditions($conditions) {
		if (isset($conditions['year'])) {
			$year = $conditions['year'];
			unset($conditions['year']);

			if (strlen($year) > 0) {
				$conditions = array_merge($conditions, array(// if the year falls between the start and end date
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
		$this->unbindModel(array('belongsTo' => array('InstitutionSitePosition')));
		$data = $this->find('all', array(
			'fields' => array('Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.last_name', 'Staff.preferred_name', 'InstitutionSitePosition.position_no', 'StaffPositionTitle.name'/* , 'StaffCategory.name' */),
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'limit' => $limit,
			'offset' => (($page - 1) * $limit),
			'order' => $order,
			'group' => array('Staff.id')
		));
		return $data;
	}

	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$this->unbindModel(array('belongsTo' => array('InstitutionSitePosition')));
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
//pr($data);
		// Checking if user has access to add
		$_add_staff = $controller->AccessControl->check('InstitutionSites', 'staffAdd');
		$controller->set('_add_staff', $_add_staff);
		// End Access Control

		$controller->set(compact('page', 'orderBy', 'order', 'yearOptions', 'selectedYear', 'data'));
	}

	public function staffView($controller, $params) {
		if (isset($params['pass'][0])) {
			$staffId = $params['pass'][0];
			$controller->Session->write('InstitutionSiteStaffId', $staffId);
			$data = $this->Staff->findById($staffId); //('first', array('conditions' => array('Staff.id' => $staffId)));
			
			$name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
			
			$controller->Navigation->addCrumb($name);
			$header = __('Staff Information');
			$positions = $this->getPositions($staffId, $controller->institutionSiteId);
			//pr($controller->institutionSiteId);
			//if (!empty($positions)) {
				$controller->set(compact('data', 'positions', 'header'));
			//} else {
			//	return $controller->redirect(array('action' => 'staff'));
			//}
		} else {
			return $controller->redirect(array('action' => 'staff'));
		}
	}

	public function staffAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Staff');
		$positionOptions = $this->InstitutionSitePosition->getInstitutionSitePositionList();
		$selectedPositionId = !empty($positionOptions) ? key($positionOptions) : 0;
		$FTEOtpions = $this->getFTEOptions($selectedPositionId, array('date' => date("Y-m-d")));
		$statusOptions = $this->StaffStatus->findList(true);

		//Create 1 more field options "Staff Type"=> "full time / part time"
		$staffTypeOptions = $this->StaffType->getList();
		$staffTypeDefault = $this->StaffType->getDefaultValue();
		$controller->set(compact('positionOptions', 'FTEOtpions', 'statusOptions', 'staffTypeOptions', 'selectedPositionId', 'staffTypeDefault'));
	}

	public function staffSave($controller, $params) {
		$this->render = false;
		if ($controller->request->is('post')) { //pr($controller->request->data);die;
			$data = $controller->request->data['InstitutionSiteStaff'];
			if (isset($data['staff_id'])) {
				//if (!empty($data['start_date']['day']) && !empty($data['start_date']['month']) && !empty($data['start_date']['year'])) {
					$data['institution_site_id'] = $controller->institutionSiteId;
					$selectedDate = strtotime($data['start_date']);
					$data['start_year'] = date('Y',$selectedDate);
					$data['FTE'] = !empty($data['FTE'])?$data['FTE']/100 : '';
					/*$yr = $data['start_year'];//$data['start_date']['year'];
					$mth = date('m',$selectedDate);//$data['start_date']['month'];
					$day = date('d',$selectedDate);//$data['start_date']['day'];

					while (!checkdate($mth, $day, $yr)) {
						$day--;
					}*/
					//$data['start_date'] = sprintf('%d-%d-%d', $yr, $mth, $day);
					$insert = true;
					if (!empty($data['position_no'])) {
						$obj = $this->isPositionNumberExists($data['position_no'], $data['start_date']);
						/*if (!$obj) {
							$obj = $this->isPositionNumberExists($data['position_no'], $data['start_date']);
						}*/
						if ($obj) {
							$staffObj = $this->Staff->find('first', array(
								'fields' => array('Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.last_name', 'Staff.gender'),
								'conditions' => array('Staff.id' => $data['staff_id'])
							));
							$position = $data['position_no'];
							$name = '<b>' . trim($obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']) . '</b>';
							$school = '<b>' . trim($obj['institution_name'] . ' - ' . $obj['institution_site_name']) . '</b>';
							$msg = __('Position Number') . ' (' . $position . ') ' . __('is already being assigned to ') . $name . ' from ' . $school . '. ';
							$msg .= '<br>' . __('Please choose another position number.');
							$controller->Utility->alert($msg, array('type' => 'warn'));
							$insert = false;
						}
					}
				/*} else {
					$insert = false;
					$controller->Utility->alert($controller->Utility->getMessage('INVALID_DATE'), array('type' => 'error'));
				}*/
				if (isset($insert) && $insert) {
					$this->save($data);
					$controller->Message->alert('general.add.success');
					//$controller->Utility->alert($controller->Utility->getMessage('CREATE_SUCCESS'));
				}
				return $controller->redirect(array('action' => 'staffAdd'));
			}
		}
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

	public function getFTEOptions($positionId, $options = array()) {// $FTE_value = 0, $date = null, $includeSelfNum = false, $showAllFTE = false) {
		$options['showAllFTE'] = !empty($options['showAllFTE']) ? $options['showAllFTE'] : false;
		$options['includeSelfNum'] = !empty($options['includeSelfNum']) ? $options['includeSelfNum'] : false;
		$options['FTE_value'] = !empty($options['FTE_value']) ? $options['FTE_value'] : 0;
		$options['date'] = !empty($options['date']) ? $options['date'] : null;

		if ($options['showAllFTE']) {
			foreach ($this->fteOptions as $obj) {
				$filterFTEOptions[$obj] = $obj;
			}
		} else {
			$conditions = array('AND' => array('institution_site_position_id' => $positionId));
			if (!empty($options['date'])) {
				$conditions['AND'][] = array('start_date >= ' => $options['date'], 'OR' => array('end_date > ' => $options['date'], 'end_date is null'));
			}

			$data = $this->find('all', array(
				'conditions' => $conditions,
				'fields' => array('COALESCE(SUM(FTE),0) as totalFTE', 'institution_site_position_id'),
				'group' => array('institution_site_position_id'),
					)
			);
			
			$totalFTE = empty($data[0][0]['totalFTE']) ? 0 : $data[0][0]['totalFTE'] * 100;
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
		$controller->set(compact('search', 'data'));
		//}
	}

	public function staffPositionDelete($controller, $params) {
		/*$this->render = false;
		if ($controller->request->is('post')) {
			$result = array('alertOpt' => array());
			$controller->Utility->setAjaxResult('alert', $result);
			$id = $params->data['id'];

			if ($this->delete($id)) {
				$msgData = $controller->Message->get('general.delete.success');
				$result['alertOpt']['text'] = $msgData['msg']; // __('File is deleted successfully.');
			} else {
				$msgData = $controller->Message->get('general.delete.failed');
				$result['alertType'] = $this->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = $msgData; //__('Error occurred while deleting file.');
			}
			return json_encode($result);
		}*/
		$this->render = false;
		
		if ($controller->Session->check('InstitutionSiteStaffId')) {
			$InstitutionSitePositionId = isset($params['pass'][0]) ? $params['pass'][0] : 0;
            $id = $controller->Session->read('InstitutionSiteStaffId');
            if ($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
            $controller->Session->delete('InstitutionSiteStaffId');
            $controller->redirect(array('action' => 'positionsHistory', $InstitutionSitePositionId));
        }
	}

	public function getStaffSelectList($year, $institutionSiteId, $classId) {
        // Filtering section
		
        $InstitutionSiteClassStaff = ClassRegistry::init('InstitutionSiteClassStaff');
        $staffsExclude = $InstitutionSiteClassStaff->getStaffs($classId);
        $ids = '';
        foreach($staffsExclude as $obj){
            $ids .= $obj['Staff']['id'].',';
        }
        $ids = rtrim($ids,',');
        if($ids!=''){
            $conditions = 'Staff.id NOT IN (' . $ids . ')';
        }else{
            $conditions = '';
        }
        // End filtering

		$data = $this->find('all', array(
			'fields' => array(
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 
				'Staff.last_name', 'Staff.gender'
			),
			'conditions' => array(
				'InstitutionSiteStaff.institution_site_id' => $institutionSiteId,
				'InstitutionSiteStaff.start_year <=' => $year,
				'OR' => array(
					'InstitutionSiteStaff.end_year >=' => $year,
					'InstitutionSiteStaff.end_year IS NULL'
				)
			),
			'group' => array('Staff.id'),
			'order' => array('Staff.first_name')
		));
		return $data;
	}
}

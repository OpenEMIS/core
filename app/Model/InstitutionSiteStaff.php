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
App::uses('Sanitize', 'Utility');
App::uses('AppModel', 'Model');

class InstitutionSiteStaff extends AppModel {
	public $useTable = 'institution_site_staff';
	public $fteOptions = array(5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100);
	public $actsAs = array(
		'Search',
		'ControllerAction2', 
		'DatePicker' => array('start_date', 'end_date'),
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		),
		'DatePicker' => array('start_date', 'end_date'),
		'Year' => array('start_date' => 'start_year', 'end_date' => 'end_year')
	);
	public $validate = array(
		'search' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a OpenEMIS ID or name.'
			)
		),
		'staff_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a staff.'
			)
		),
		'institution_site_position_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a Position.'
			)
		),
		'staff_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Type.'
			)
		),
		'FTE' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a FTE.'
			)
		)
	);

	public $belongsTo = array(
		'Staff.Staff',
		'InstitutionSite',
		'InstitutionSitePosition',
		'Staff.StaffType',
		'Staff.StaffStatus'
	);
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
				'Staff' => array(
					'openemis_no' => 'OpenEMIS ID',
					'first_name' => 'First Name',
					'middle_name' => 'Middle Name',
					'third_name' => 'Third Name',
					'last_name' => 'Last Name',
					'preferred_name' => 'Preferred Name',
					'gender' => 'Gender',
					'date_of_birth' => 'Date of Birth'
				),
				'StaffContact' => array(
					'GROUP_CONCAT(DISTINCT CONCAT(ContactType.name, "-", StaffContact.value))' => 'Contacts'
				),
				'StaffIdentity' => array(
					'GROUP_CONCAT(DISTINCT CONCAT(IdentityType.name, "-", StaffIdentity.number))' => 'Identities'
				),
				'StaffNationality' => array(
					'GROUP_CONCAT(DISTINCT Country.name)' => 'Nationality'
				),
				'StaffStatus' => array(
					'name' => 'Status'
				),
				'StaffCustomField' => array(
				),
				'InstitutionSite' => array(
					'name' => 'Institution Name',
					'code' => 'Institution Code'
				),
				'InstitutionSiteType' => array(
					'name' => 'Institution Type'
				),
				'InstitutionSiteOwnership' => array(
					'name' => 'Institution Ownership'
				),
				'InstitutionSiteStatus' => array(
					'name' => 'Institution Status'
				),
				'InstitutionSite2' => array(
					'date_opened' => 'Date Opened',
					'date_closed' => 'Date Closed',
				),
				'Area' => array(
					'name' => 'Area'
				),
				'AreaAdministrative' => array(
					'name' => 'Area (Administrative)'
				),
				'InstitutionSite3' => array(
					'address' => 'Address',
					'postal_code' => 'Postal Code',
					'longitude' => 'Longitude',
					'latitude' => 'Latitude',
					'contact_person' => 'Contact Person',
					'telephone' => 'Telephone',
					'fax' => 'Fax',
					'email' => 'Email',
					'website' => 'Website'
				)
			),
			'fileName' => 'Report_Staff_List'
		)
	);
	
	public function beforeSave($options = array()) {
		$alias = $this->alias;
		
		$this->data[$alias]['FTE'] = $this->data[$alias]['FTE'] / 100;
		
		return parent::beforeSave($options);
	}

	public function getFields($options = array()) {
		$this->fields = parent::getFields($options);
		$this->fields['institution_site_id']['labelKey'] = 'InstitutionSite';
		
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		
		$this->fields['staff_id']['type'] = 'hidden';
		$this->fields['staff_type_id']['type'] = 'select';
		$this->fields['staff_type_id']['options'] = $this->StaffType->getList(array('listOnly'=>true));
		
		$this->setFieldOrder('institution_site_id', 1);
		$this->setFieldOrder('institution_site_position_id', 2);
		$this->setFieldOrder('start_date', 3);
		$this->setFieldOrder('staff_type_id', 5);
		return $this->fields;
	}
	
	public function index() {
		$this->Navigation->addCrumb('List of Staff');
		$params = $this->controller->params;

		$prefix = 'InstitutionSiteStaff.search.';

		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		$yearOptions = $AcademicPeriod->getAcademicPeriodListValues('id');

		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$conditions = array();

		if ($this->Session->check($prefix . 'conditions')) {
			$conditions = $this->Session->read($prefix . 'conditions');
		}
		$conditions['InstitutionSiteStaff.institution_site_id'] = $institutionSiteId;
		$conditions['Staff.id <>'] = '';

		$IdentityType = ClassRegistry::init('IdentityType');
		$defaultIdentity = $IdentityType->find('first', array(
			'contain' => array('FieldOption'),
			'conditions' => array('FieldOption.code' => $IdentityType->alias),
			'order' => array('IdentityType.default DESC')
		));
		$conditions['defaultIdentity'] = $defaultIdentity['IdentityType']['id'];

		if ($this->request->is('post')) {
			$searchField = Sanitize::escape(trim($this->request->data[$this->alias]['search']));
			$selectedAcademicPeriod = $this->request->data[$this->alias]['academic_period_id'];

			if (strlen($selectedAcademicPeriod) != '') {
				$yearObj = $AcademicPeriod->findById($selectedAcademicPeriod);
				$startDate = date('Y-m-d', strtotime($yearObj['AcademicPeriod']['start_date']));
				$endDate = date('Y-m-d', strtotime($yearObj['AcademicPeriod']['end_date']));

				$conditions['InstitutionSiteStaff.start_date <='] = $startDate;
				$conditions['OR'] = array(
					'InstitutionSiteStaff.end_date >=' => $endDate,
					'InstitutionSiteStaff.end_date IS NULL'
				);
				$this->Session->write($prefix . 'yearId', $selectedAcademicPeriod);
			} else {
				unset($conditions['InstitutionSiteStaff.start_date <=']);
				unset($conditions['OR']['InstitutionSiteStaff.end_date >=']);
				unset($conditions['OR'][0]);

				$this->Session->delete($prefix . 'yearId');
			}
		} else {
			if ($this->Session->check($prefix . 'yearId')) {
				$this->request->data[$this->alias]['academic_period_id'] = $this->Session->read($prefix . 'yearId');
			}
		}
		
		if (!empty($searchField)) {
			$search = '%' . $searchField . '%';
			$conditions['OR'] = array(
				'SecurityUser.openemis_no LIKE' => $search,
				'SecurityUser.first_name LIKE' => $search,
				'SecurityUser.middle_name LIKE' => $search,
				'SecurityUser.third_name LIKE' => $search,
				'SecurityUser.last_name LIKE' => $search,
				'Staff.preferred_name LIKE' => $search
			);
		} else {
			unset($conditions['OR']['SecurityUser.openemis_no LIKE']);
			unset($conditions['OR']['SecurityUser.first_name LIKE']);
			unset($conditions['OR']['SecurityUser.middle_name LIKE']);
			unset($conditions['OR']['SecurityUser.third_name LIKE']);
			unset($conditions['OR']['SecurityUser.last_name LIKE']);
			unset($conditions['OR']['Staff.preferred_name LIKE']);
		}
		
		if($this->Session->check('Staff.AdvancedSearch')){
			$params = $this->Session->read('Staff.AdvancedSearch');
			$conditions = $this->getAdvancedSearchConditionsWithSite($institutionSiteId, $params);
		}

		$this->Session->write($prefix . 'conditions', $conditions);
		$data = $this->controller->Search->search($this, $conditions);

		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$positionList = $this->InstitutionSitePosition->StaffPositionTitle->getList(array('listOnly'=>true));
		$defaultIdentity = $defaultIdentity['IdentityType'];
		$this->setVar(compact('data', 'yearOptions', 'positionList', 'defaultIdentity'));
	}
	
	public function add() {
		$this->Navigation->addCrumb('Add existing Staff');
		
		if ($this->Session->check('InstitutionSite.id')) {
			$institutionSiteId = $this->Session->read('InstitutionSite.id');
			$positionList = $this->InstitutionSitePosition->getInstitutionSitePositionList($institutionSiteId, true);
			
			$positionId = !empty($positionList) ? key($positionList) : 0;
			$startDate = date('d-m-Y');
			if ($this->request->is(array('post', 'put'))) {
				$data = $this->request->data;
				$submit = $data['submit'];
				if (!empty($data[$this->alias]['institution_site_position_id'])) {
					$positionId = $data[$this->alias]['institution_site_position_id'];
					$startDate = $data[$this->alias]['start_date'];
				}
				
				if ($submit == __('Save')) {
					$this->set($data[$this->alias]);
					
					if(isset($data['new'])){
						$this->validator()->remove('search');
						$this->validator()->remove('staff_id');
					}
					
					if ($this->validates()) {
						$data[$this->alias]['FTE'] = !empty($data[$this->alias]['FTE']) ? $data[$this->alias]['FTE'] : NULL;
						$data[$this->alias]['institution_site_id'] = $institutionSiteId;
						$selectedDate = date('Y-m-d', strtotime($startDate));
						$count = $this->find('count', array(
							'conditions' => array(
								$this->alias . '.staff_id' => $data[$this->alias]['staff_id'],
								$this->alias . '.institution_site_position_id' => $positionId, 
								$this->alias . '.start_date <=' => $selectedDate,
								'OR' => array(
									$this->alias . '.end_date >=' => $selectedDate,
									$this->alias . '.end_date IS NULL'
								)
							)
						));
						
						if ($count > 0) {
							$this->Message->alert('general.exists');
						} else {
							if(isset($data['new'])){
								$this->Session->write('InstitutionSiteStaff.addNew', $data[$this->alias]);
								return $this->redirect(array('controller' => 'Staff', 'action' => 'add'));
							}else{
								if ($this->save($data)) {
									$this->Message->alert('general.add.success');
									return $this->redirect(array('action' => get_class($this)));
								} else {
									$this->Message->alert('general.add.failed');
								}
							}
						}
					} else {
						$this->Message->alert('general.add.failed');
					}
				}
			}
			$this->fields['institution_site_id']['type'] = 'disabled';
			$this->fields['institution_site_id']['value'] = $this->Session->read('InstitutionSite.data.InstitutionSite.name');
			$this->fields['end_date']['visible'] = false;
			$this->fields['staff_id']['attr'] = array('autocomplete' => 'staff_id');
			$this->fields['staff_status_id']['type'] = 'hidden';
			$this->fields['staff_status_id']['value'] = $this->StaffStatus->getDefaultValue();
			$this->fields['institution_site_position_id']['type'] = 'select';
			$this->fields['institution_site_position_id']['options'] = $positionList;
			$this->fields['institution_site_position_id']['attr'] = array('onchange' => "$('#reload').click()");
			$this->fields['start_date']['attr']['dateOptions'] = array('changeDate' => "function(ev) { $('#reload').click(); }");
			$this->fields['FTE']['type'] = 'select';
			$this->fields['FTE']['options'] = $this->getFTEOptions($positionId, array('startDate' => $startDate));
			
			$this->setFieldOrder('institution_site_id', 0);
			$this->setFieldOrder('institution_site_position_id', 1);
			$this->setFieldOrder('start_date', 2);
			$this->setFieldOrder('FTE', 3);
			$this->setFieldOrder('staff_type_id', 4);
			
		} else {
			return $this->redirect(array('plugin' => false, 'controller' => 'Staff', 'action' => 'index'));
		}
	}
	
	public function autocomplete() {
		if ($this->request->is('ajax')) {
			$this->render = false;
			$params = $this->controller->params;
			$search = $params->query['term'];
			$list = $this->Staff->autocomplete($search);
			
			$data = array();
			foreach ($list as $obj) {
				$data[] = array(
					'label' => ModelHelper::getName($obj['SecurityUser'], array('openEmisId'=>true)),
					'value' => array('staff_id' => $obj['Staff']['id']) 
				);
			}
			return json_encode($data);
		}
	}

	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$identityConditions[] = 'StaffIdentity.staff_id = InstitutionSiteStaff.staff_id';
		if(isset($conditions['defaultIdentity'])&&strlen($conditions['defaultIdentity']>0)) {
			$identityConditions[] = 'StaffIdentity.identity_type_id = '.$conditions['defaultIdentity'];
		}

		unset($conditions['defaultIdentity']);

		if (isset($extra['sort']) && isset($extra['direction'])) {
			$order = array($extra['sort'] => $extra['direction']);
		}
		
		// change to manual joins because apparently paginate cannot handle deep association sorting
		$joins = array(
			array(
				'table' => 'staff',
				'alias' => 'Staff',
				'type' => 'inner',
				'conditions' => array('Staff.id = InstitutionSiteStaff.staff_id')
			),
			array(
				'table' => 'security_users',
				'alias' => 'SecurityUser',
				'type' => 'inner',
				'conditions' => array('SecurityUser.id = Staff.security_user_id')
			),
			array(
				'table' => 'staff_identities',
				'alias' => 'StaffIdentity',
				'type' => 'LEFT',
				'conditions' => $identityConditions,
			), 
			array(
				'table' => 'field_option_values',
				'alias' => 'StaffStatus',
				'type' => 'LEFT',
				'conditions' => array('StaffStatus.id = InstitutionSiteStaff.staff_status_id')
			),
			array(
				'table' => 'institution_site_positions',
				'alias' => 'InstitutionSitePosition',
				'type' => 'LEFT',
				'conditions' => array('InstitutionSitePosition.id = InstitutionSiteStaff.institution_site_position_id')
			)
		);

		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteStaff.*, Staff.*', 'SecurityUser.*', 'StaffIdentity.number', 'StaffStatus.name', 'InstitutionSitePosition.staff_position_title_id'),
			'joins' => $joins,
			'conditions' => $conditions,
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'order' => $order
		));
		
		$newData = array();
		if(!isset($conditions['InstitutionSiteStaff.start_year <='])) {
			foreach($data AS $record){
				$staffId = $record['Staff']['id'];
				if(isset($newData[$staffId])){
					$existingStartDate = $newData[$staffId]['InstitutionSiteStaff']['start_date'];
					$newStartDate = $record['InstitutionSiteStaff']['start_date'];
					if(date("Y-m-d",strtotime($newStartDate)) > date("Y-m-d",strtotime($existingStartDate))){
						$newData[$staffId] = $record;
					}
				}else{
					$newData[$staffId] = $record;
				}
			}
		} else{
			$newData = $data;
		}
		
		return $newData;
	}

	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		unset($conditions['defaultIdentity']);
		$count = $this->find('count', array('conditions' => $conditions));
		return $count;
	}

	// used by InstitutionSiteClass.edit
	public function getStaffOptions($institutionSiteId, $periodId) {
		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		$periodObj = $AcademicPeriod->findById($periodId);
		$periodStartDate = $AcademicPeriod->getDate($periodObj['AcademicPeriod'], 'start_date');
		$periodEndDate = $AcademicPeriod->getDate($periodObj['AcademicPeriod'], 'end_date');

		$list = $this->getStaffByInstitutionSite($institutionSiteId, $periodStartDate, $periodEndDate);
		$data = array();
		foreach ($list as $obj) {
			$staffObj = $obj['Staff'];
			$data[$staffObj['id']] = ModelHelper::getName($obj['SecurityUser'], array('openEmisId' => true));
		}
		return $data;
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
			'recursive' => -1,
			'fields' => array(
				'SecurityUser.first_name AS first_name', 'SecurityUser.middle_name AS middle_name', 'SecurityUser.third_name AS third_name', 'SecurityUser.last_name AS last_name',
				'InstitutionSite.name AS institution_site_name'
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

	public function getPositions($staffId, $institutionSiteId = 0) {
		$this->unbindModel(array('belongsTo' => array('InstitutionSitePosition')));

		$fields = array(
			'InstitutionSiteStaff.id', 'InstitutionSiteStaff.FTE',
			'InstitutionSiteStaff.start_date', 'InstitutionSiteStaff.end_date', 'InstitutionSiteStaff.staff_status_id', 'StaffType.name',
			'StaffStatus.name', 'InstitutionSitePosition.id', 'InstitutionSitePosition.position_no', 'StaffPositionTitle.name', 'StaffPositionGrade.name'
		);

		$joins = array(
			array(
				'table' => 'institution_site_positions',
				'alias' => 'InstitutionSitePosition',
				'conditions' => array('InstitutionSitePosition.id = InstitutionSiteStaff.institution_site_position_id')
			),
			array(
				'table' => 'field_option_values',
				'alias' => 'StaffPositionTitle',
				'type' => 'LEFT',
				'conditions' => array('StaffPositionTitle.id = InstitutionSitePosition.staff_position_title_id')
			),
			array(
				'table' => 'field_option_values',
				'alias' => 'StaffPositionGrade',
				'type' => 'LEFT',
				'conditions' => array('StaffPositionGrade.id = InstitutionSitePosition.staff_position_grade_id')
			)
		);
		$conditions = array('InstitutionSiteStaff.staff_id' => $staffId);

		if ($institutionSiteId == 0) {
			$fields[] = 'InstitutionSite.name as institution_site';
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

	public function getFTEOptions($positionId, $options = array()) {// $FTE_value = 0, $date = null, $includeSelfNum = false, $showAllFTE = false) {
		$options['showAllFTE'] = !empty($options['showAllFTE']) ? $options['showAllFTE'] : false;
		$options['includeSelfNum'] = !empty($options['includeSelfNum']) ? $options['includeSelfNum'] : false;
		$options['FTE_value'] = !empty($options['FTE_value']) ? $options['FTE_value'] : 0;
		$options['startDate'] = !empty($options['startDate']) ? date('Y-m-d', strtotime($options['startDate'])) : null;
		$options['endDate'] = !empty($options['endDate']) ? date('Y-m-d', strtotime($options['endDate'])) : null;
		$currentFTE = !empty($options['currentFTE']) ? $options['currentFTE'] : 0;

		if ($options['showAllFTE']) {
			foreach ($this->fteOptions as $obj) {
				$filterFTEOptions[$obj] = $obj;
			}
		} else {
			$conditions = array('AND' => array('institution_site_position_id' => $positionId));
			if (!empty($options['startDate'])) {
				$conditions['AND']['OR'] = array('end_date >= ' => $options['startDate'], 'end_date is null');
				//$conditions['AND'] = array_merge($conditions['AND'], array('start_date >= ' => $options['date']));
				//$conditions['AND'][] = array('start_date >= ' => $options['date']/*, 'OR' => array('end_date >= ' => $options['date'], 'end_date is null')*/);
			}
			if (!empty($options['endDate'])) {
				$conditions['AND'] = array_merge($conditions['AND'],array('start_date <= ' => $options['endDate']));
				//$conditions['AND'] = array('start_date <= ' => $options['endDate']);
				//$conditions['AND'] = array_merge($conditions['AND'], array('start_date >= ' => $options['date']));
				//$conditions['AND'][] = array('start_date >= ' => $options['date']/*, 'OR' => array('end_date >= ' => $options['date'], 'end_date is null')*/);
			}
			$data = $this->find('all', array(
				'recursive' => -1,
				'conditions' => $conditions,
				'fields' => array('COALESCE(SUM(FTE),0) as totalFTE', 'InstitutionSiteStaff.institution_site_position_id'),
				'group' => array('institution_site_position_id')
			));

			$totalFTE = empty($data[0][0]['totalFTE']) ? 0 : $data[0][0]['totalFTE'] * 100;
			$remainingFTE = 100 - intval($totalFTE);
			$remainingFTE = ($remainingFTE < 0) ? 0 : $remainingFTE;

			if ($options['includeSelfNum']) {
				$remainingFTE +=  $options['FTE_value'];
			}
			$highestFTE = (($remainingFTE > $options['FTE_value']) ? $remainingFTE : $options['FTE_value']);

			$filterFTEOptions = array();

			foreach ($this->fteOptions as $obj) {
				if ($highestFTE >= $obj) {
					$objLabel = number_format($obj / 100, 2);
					$filterFTEOptions[$obj] = $objLabel;
				}
			}
			
			if(!empty($currentFTE) && !in_array($currentFTE, $filterFTEOptions)){
				if($remainingFTE > 0){
					$newMaxFTE = $currentFTE + $remainingFTE;
				}else{
					$newMaxFTE = $currentFTE;
				}
				
				foreach ($this->fteOptions as $obj) {
					if ($obj <= $newMaxFTE) {
						$objLabel = number_format($obj / 100, 2);
						$filterFTEOptions[$obj] = $objLabel;
					}
				}
			}

		/*	if ($totalFTE > 0 && $options['includeSelfNum']) {
				$filterFTEOptions[$options['FTE_value']] = $options['FTE_value'];
			}*/
		}

		return $filterFTEOptions;
	}
	
	public function getInstitutionSiteStaffOptions($institutionSiteId, $startDate, $endDate){
		$staffData = $this->getStaffByInstitutionSite($institutionSiteId, $startDate, $endDate);
		
		$options = array();
		foreach($staffData as $row){
			$staff = $row['Staff'];
			$staffId = $staff['id'];
			$staffName = ModelHelper::getName($row['SecurityUser']);
			$options[$staffId] = $staffName;
		}
		
		return $options;
	}
	
	public function getStaffByInstitutionSite($institutionSiteId, $startDate, $endDate) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT Staff.id', 'Staff.security_user_id', 
				'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.middle_name',
				'SecurityUser.third_name', 'SecurityUser.last_name', 'SecurityUser.preferred_name'
			),
			// 'contain' => array('Staff'=>array('SecurityUser')),
			'joins' => array(

				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array(
						'InstitutionSiteStaff.staff_id = Staff.id'
					)
				),
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array(
						'Staff.security_user_id = SecurityUser.id'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteStaff.institution_site_id = ' . $institutionSiteId,
				'OR' => array(
					'OR' => array(
						array(
							'InstitutionSiteStaff.end_date IS NOT NULL',
							'InstitutionSiteStaff.start_date <= "' . $startDate . '"',
							'InstitutionSiteStaff.end_date >= "' . $startDate . '"'
						),
						array(
							'InstitutionSiteStaff.end_date IS NOT NULL',
							'InstitutionSiteStaff.start_date <= "' . $endDate . '"',
							'InstitutionSiteStaff.end_date >= "' . $endDate . '"'
						),
						array(
							'InstitutionSiteStaff.end_date IS NOT NULL',
							'InstitutionSiteStaff.start_date >= "' . $startDate . '"',
							'InstitutionSiteStaff.end_date <= "' . $endDate . '"'
						)
					),
					array(
						'InstitutionSiteStaff.end_date IS NULL',
						'InstitutionSiteStaff.start_date <= "' . $endDate . '"'
					)
				)
			)
		));
		return $data;
	}
	
	public function getStaffByAcademicPeriod($institutionSiteId, $academicPeriodId) {
		$yearObj = ClassRegistry::init('AcademicPeriod')->findById($academicPeriodId);
		$startDate = date('Y-m-d', strtotime($yearObj['AcademicPeriod']['start_date']));
		$endDate = date('Y-m-d', strtotime($yearObj['AcademicPeriod']['end_date']));
		
		$data = $this->getStaffByInstitutionSite($institutionSiteId, $startDate, $endDate);
		
		return $data;
	}
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->getCSVHeader($this->reportMapping[$index]['fields']);
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = $this->getCSVFields($this->reportMapping[$index]['fields']);
			$options['order'] = array('SecurityUser.first_name');
			$options['group'] = array('Staff.id');

			$options['joins'] = array(
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array(
						'InstitutionSiteStaff.staff_id = Staff.id',
						'InstitutionSiteStaff.institution_site_id' => $institutionSiteId
					)
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSiteStaff.institution_site_id = InstitutionSite.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite2',
					'type' => 'inner',
					'conditions' => array('InstitutionSite.id = InstitutionSite2.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite3',
					'type' => 'inner',
					'conditions' => array('InstitutionSite.id = InstitutionSite3.id')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'InstitutionSiteStatus',
					'conditions' => array('InstitutionSiteStatus.id = InstitutionSite.institution_site_status_id')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'InstitutionSiteType',
					'conditions' => array('InstitutionSiteType.id = InstitutionSite.institution_site_type_id')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'InstitutionSiteOwnership',
					'conditions' => array('InstitutionSiteOwnership.id = InstitutionSite.institution_site_ownership_id')
				),
				array(
					'table' => 'areas',
					'alias' => 'Area',
					'conditions' => array('InstitutionSite.area_id = Area.id')
				),
				array(
					'table' => 'area_administratives',
					'alias' => 'AreaAdministrative',
					'type' => 'left',
					'conditions' => array('InstitutionSite.area_administrative_id = AreaAdministrative.id')
				),
				array(
					'table' => 'staff_nationalities',
					'alias' => 'StaffNationality',
					'type' => 'left',
					'conditions' => array('InstitutionSiteStaff.staff_id = StaffNationality.staff_id')
				),
				array(
					'table' => 'staff_contacts',
					'alias' => 'StaffContact',
					'type' => 'left',
					'conditions' => array('InstitutionSiteStaff.staff_id = StaffContact.staff_id')
				),
				array(
					'table' => 'staff_identities',
					'alias' => 'StaffIdentity',
					'type' => 'left',
					'conditions' => array('InstitutionSiteStaff.staff_id = StaffIdentity.staff_id')
				),
				array(
					'table' => 'countries',
					'alias' => 'Country',
					'type' => 'left',
					'conditions' => array('Country.id = StaffNationality.country_id')
				),
				array(
					'table' => 'contact_types',
					'alias' => 'ContactType',
					'type' => 'left',
					'conditions' => array('ContactType.id = StaffContact.contact_type_id')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'IdentityType',
					'type' => 'left',
					'conditions' => array('IdentityType.id = StaffIdentity.identity_type_id')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'StaffStatus',
					'type' => 'left',
					'conditions' => array('InstitutionSiteStaff.staff_status_id = StaffStatus.id')
				)
			);

			$data = $this->find('all', $options);

			$siteCustomFieldModel = ClassRegistry::init('InstitutionSiteCustomField');


			$reportFields = $this->reportMapping[$index]['fields'];

			$StaffCustomFieldModel = ClassRegistry::init('StaffCustomField');
			$staffCustomFields = $StaffCustomFieldModel->find('all', array(
				'recursive' => -1,
				'fields' => array('StaffCustomField.name as FieldName'),
				'conditions' => array('StaffCustomField.visible' => 1, 'StaffCustomField.type != 1'),
				'order' => array('StaffCustomField.order')
					)
			);

			foreach ($staffCustomFields as $val) {
				if (!empty($val['StaffCustomField']['FieldName'])) {
					$reportFields['StaffCustomField']['Staff '.$val['StaffCustomField']['FieldName']] = '';
				}
			}

			$this->reportMapping[$index]['fields'] = $reportFields;

			$newData = array();


			$StaffModel = ClassRegistry::init('Staff');
			$staff = $StaffModel->find('list', array(
				'recursive' => -1,
				'fields' => array('Staff.id'),
				'joins' => array(
					array(
						'table' => 'institution_site_staff',
						'alias' => 'InstitutionSiteStaff',
						'conditions' => array('InstitutionSiteStaff.staff_id = Staff.id')
					)
				),
				'conditions' => array('InstitutionSiteStaff.institution_site_id = ' . $institutionSiteId),
				'order' => array('SecurityUser.first_name')
					)
			);


			$r = 0;
			foreach ($data AS $row) {
				$row['Staff']['gender'] = $this->formatGender($row['Staff']);
				$row['Staff']['date_of_birth'] = $this->formatDateByConfig($row['Staff']['date_of_birth']);


				$staffCustomFields = $StaffCustomFieldModel->find('all', array(
					'recursive' => -1,
					'fields' => array('StaffCustomField.name as FieldName', 'IFNULL(GROUP_CONCAT(StaffCustomFieldOption.value),StaffCustomValue.value) as FieldValue'),
					'joins' => array(
						array(
							'table' => 'staff_custom_values',
							'alias' => 'StaffCustomValue',
							'type' => 'left',
							'conditions' => array(
								'StaffCustomField.id = StaffCustomValue.staff_custom_field_id',
								'StaffCustomValue.staff_id' => array_slice($staff, $r, 1)
							)
						),
						array(
							'table' => 'staff_custom_field_options',
							'alias' => 'StaffCustomFieldOption',
							'type' => 'left',
							'conditions' => array(
								'StaffCustomField.id = StaffCustomFieldOption.staff_custom_field_id',
								'StaffCustomField.type' => array(3, 4),
								'StaffCustomValue.value = StaffCustomFieldOption.id'
							)
						),
					),
					'conditions' => array('StaffCustomField.visible' => 1, 'StaffCustomField.type !=1'),
					'order' => array('StaffCustomField.order'),
					'group' => array('StaffCustomField.id')
						)
				);


				foreach ($staffCustomFields as $val) {
					if (!empty($val['StaffCustomField']['FieldName'])) {
						$row['StaffCustomField'][$val['StaffCustomField']['FieldName']] = $val[0]['FieldValue'];
					}
				}

				$sortRow = array();
				foreach ($this->reportMapping[$index]['fields'] as $key => $value) {
					if (isset($row[$key])) {
						$sortRow[$key] = $row[$key];
					} else {
						$sortRow[0] = $row[0];
					}
				}
				$newData[] = $sortRow;
				$r++;
			}

			return $newData;
		}
	}
	
	public function reportsGetFileName($args){
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}

}

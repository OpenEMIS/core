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

class InstitutionSiteShift extends AppModel {

	public $actsAs = array('ControllerAction');
	public $belongsTo = array(
		'ModifiedUser' => array('foreignKey' => 'modified_user_id', 'className' => 'SecurityUser'),
		'CreatedUser' => array('foreignKey' => 'created_user_id', 'className' => 'SecurityUser'),
	);
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a shift name'
			)
		),
		'school_year_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a school year'
			)
		),
		'start_time' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a start time'
			)
		),
		'end_time' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a end time'
			)
		),
		'location_institution_site_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a location'
			)
		)
	);

	public function getAllShiftsByInstitutionSite($institutionSiteId) {
		$result = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteShift.*', 'InstitutionSite.*', 'SchoolYear.*'),
			'joins' => array(
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'type' => 'LEFT',
					'conditions' => array('InstitutionSiteShift.school_year_id = SchoolYear.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'type' => 'LEFT',
					'conditions' => array('InstitutionSiteShift.location_institution_site_id = InstitutionSite.id')
				)
			),
			'conditions' => array('InstitutionSiteShift.institution_site_id' => $institutionSiteId)
		));

		return $result;
	}
	
	public function getShiftOptions($institutionSiteId, $schoolYearId) {
		$result = $this->find('list', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteShift.id', 'InstitutionSiteShift.name'),
			'conditions' => array(
				'InstitutionSiteShift.institution_site_id' => $institutionSiteId,
				'InstitutionSiteShift.school_year_id' => $schoolYearId
			)
		));

		return $result;
	}

	public function getShiftById($shiftId) {
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteShift.*', 'InstitutionSite.*', 'SchoolYear.*', 'CreatedUser.*', 'ModifiedUser.*'),
			'joins' => array(
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'type' => 'LEFT',
					'conditions' => array('InstitutionSiteShift.school_year_id = SchoolYear.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'type' => 'LEFT',
					'conditions' => array('InstitutionSiteShift.location_institution_site_id = InstitutionSite.id')
				),
				array(
					'table' => 'security_users',
					'alias' => 'CreatedUser',
					'type' => 'LEFT',
					'conditions' => array(
						'InstitutionSiteShift.created_user_id = CreatedUser.id'
					)
				),
				array(
					'table' => 'security_users',
					'alias' => 'ModifiedUser',
					'type' => 'LEFT',
					'conditions' => array(
						'InstitutionSiteShift.modified_user_id = ModifiedUser.id'
					)
				)
			),
			'conditions' => array('InstitutionSiteShift.id' => $shiftId)
		));

		return $data;
	}

	public function shifts($controller, $params) {
		$controller->Navigation->addCrumb('Shifts');

		$data = $this->getAllShiftsByInstitutionSite($controller->institutionSiteId);
		
		if(empty($data)){
			$this->create();
			
			$SchoolYearModel = ClassRegistry::init('SchoolYear');
			//$currentYearId = $SchoolYearModel->getSchoolYearId(date('Y'));
			$yearOptions = $SchoolYearModel->getYearList();
			$schoolYearId = key($yearOptions);
			
			$settingStartTime = $controller->ConfigItem->getValue('start_time');
			$hoursPerDay = intval($controller->ConfigItem->getValue('hours_per_day'));
			if($hoursPerDay > 1){
				$endTimeStamp = strtotime('+' . $hoursPerDay . ' hours', strtotime($settingStartTime));
			}else{
				$endTimeStamp = strtotime('+' . $hoursPerDay . ' hour', strtotime($settingStartTime));
			}
			
			$endTime = date('g:i A', $endTimeStamp);
			
			$defaultShift = array();
			$defaultShift['InstitutionSiteShift'] = array(
				'name' => 'Default Shift',
				'school_year_id' => $schoolYearId,
				'start_time' => $settingStartTime,
				'end_time' => $endTime,
				'institution_site_id' => $controller->institutionSiteId,
				'location_institution_site_id' => $controller->institutionSiteId,
				'location_institution_site_name' => 'Institution Site Name'
				
			);

			$this->save($defaultShift);
			
			$data = $this->getAllShiftsByInstitutionSite($controller->institutionSiteId);
		}

		$controller->set('data', $data);
	}

	public function shiftsView($controller, $params) {
		$shiftId = $controller->params['pass'][0];
		$shiftObj = $this->getShiftById($shiftId);
		if (!empty($shiftObj)) {
			$controller->Session->write('shiftId', $shiftId);
			$controller->set('shiftObj', $shiftObj);
		} else {
			$controller->redirect(array('action' => 'shifts'));
		}
	}

	public function shiftsAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Shift');
		$institutionObj = $controller->InstitutionSite->getInstitutionSiteById($controller->institutionSiteId);

		if ($controller->request->is('post')) { // save
			$data = $controller->request->data;
			$data['InstitutionSiteShift']['institution_site_id'] = $controller->institutionSiteId;
			$this->create();

			if ($this->save($data, array('validate' => 'only'))) {
				if (empty($data['InstitutionSiteShift']['location_institution_site_id'])) {
					$controller->Utility->alert($controller->Utility->getMessage('SHIFT_WITHOUT_LOCATION'), array('type' => 'error', 'dismissOnClick' => true));
				} else {
					$testLocationId = $controller->InstitutionSite->getInstitutionSiteById($data['InstitutionSiteShift']['location_institution_site_id']);
					if (empty($testLocationId)) {
						$controller->Utility->alert($controller->Utility->getMessage('SHIFT_WITHOUT_LOCATION'), array('type' => 'error', 'dismissOnClick' => true));
					} else {
						$this->save($data, array('validate' => 'false'));
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
						$controller->redirect(array('action' => 'shifts'));
					}
				}
			}
		}

		$yearOptions = $controller->SchoolYear->getAvailableYears();

		$controller->set('yearOptions', $yearOptions);
		$controller->set('institutionSiteId', $institutionObj['InstitutionSite']['id']);
		$controller->set('institutionSiteName', $institutionObj['InstitutionSite']['name']);
	}

	public function shiftsEdit($controller, $params) {
		$shiftId = $controller->params['pass'][0];
		$shiftObj = $this->getShiftById($shiftId);
		if (empty($shiftObj)) {
			$controller->redirect(array('action' => 'shifts'));
		}

		$locationSiteObj = $controller->InstitutionSite->getInstitutionSiteById($shiftObj['InstitutionSiteShift']['location_institution_site_id']);

		if ($controller->request->is('get')) { // save
			$controller->Navigation->addCrumb('Edit Shift');

			$controller->request->data = $shiftObj;
		} else {
			$data = $controller->request->data;
			$data['InstitutionSiteShift']['institution_site_id'] = $controller->institutionSiteId;

			if ($this->save($data, array('validate' => 'only'))) {
				if (empty($data['InstitutionSiteShift']['location_institution_site_id'])) {
					$controller->Utility->alert($controller->Utility->getMessage('SHIFT_WITHOUT_LOCATION'), array('type' => 'error', 'dismissOnClick' => true));
				} else {
					$testLocationId = $controller->InstitutionSite->getInstitutionSiteById($data['InstitutionSiteShift']['location_institution_site_id']);
					if (empty($testLocationId)) {
						$controller->Utility->alert($controller->Utility->getMessage('SHIFT_WITHOUT_LOCATION'), array('type' => 'error', 'dismissOnClick' => true));
					} else {
						$this->save($data, array('validate' => 'false'));
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
						$controller->redirect(array('action' => 'shiftsView', $shiftId));
					}
				}
			}
		}
		$yearOptions = $controller->SchoolYear->getAvailableYears();

		$controller->set('yearOptions', $yearOptions);
		
		if(!empty($locationSiteObj)){
			$controller->set('locationSiteName', $locationSiteObj['InstitutionSite']['name']);
			$controller->set('locationSiteId', $locationSiteObj['InstitutionSite']['id']);
		}else{
			$controller->set('locationSiteName', '');
			$controller->set('locationSiteId', '');
		}

		$controller->set('shiftId', $shiftId);
	}

	public function shiftsDelete($controller, $params) {
		if ($controller->Session->check('shiftId')) {
			$shiftId = $controller->Session->read('shiftId');
			$shiftObj = $this->getShiftById($shiftId);
			$shiftName = $shiftObj['InstitutionSiteShift']['name'];

			$this->deleteAll(array('InstitutionSiteShift.id' => $shiftId));
			$controller->Utility->alert($shiftName . __(' have been deleted successfully.'));
			$controller->redirect(array('action' => 'shifts'));
		} else {
			$controller->redirect(array('action' => 'shifts'));
		}
	}

}

?>

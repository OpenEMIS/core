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

class StaffBehaviour extends StaffAppModel {

	public $actsAs = array('ControllerAction', 'Datepicker' => array('date_of_behaviour'));
	public $useTable = 'staff_behaviours';
	public $belongsTo = array(
		'Staff.Staff',
		'Staff.StaffBehaviourCategory',
		'InstitutionSite'
	);
	public $validate = array(
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a valid title'
			)
		)
	);

	public function getBehaviourData($staffId) {
		$list = $this->find('all', array(
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'staff_behaviour_categories',
					'alias' => 'StaffBehaviourCategory',
					'type' => 'INNER',
					'conditions' => array(
						'StaffBehaviourCategory.id = StaffBehaviour.staff_behaviour_category_id'
					)
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'type' => 'INNER',
					'conditions' => array(
						'InstitutionSite.id = StaffBehaviour.institution_site_id'
					)
				)
			),
			'fields' => array('StaffBehaviour.id', 'StaffBehaviour.title', 'StaffBehaviour.date_of_behaviour',
				'StaffBehaviourCategory.name', 'InstitutionSite.name', 'InstitutionSite.id'),
			'conditions' => array('StaffBehaviour.staff_id' => $staffId)));
		return $list;
	}

	public function beforeAction($controller, $action) {
		$controller->set('model', $this->alias);
	}

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'name', 'model' => 'InstitutionSite'),
				array('field' => 'name', 'model' => 'StaffBehaviourCategory', 'labelKey' => 'general.category'),
				array('field' => 'date_of_behaviour', 'type' => 'datepicker', 'labelKey' => 'general.date'),
				array('field' => 'title'),
				array('field' => 'description'),
				array('field' => 'action'),
			)
		);
		return $fields;
	}

	public function behaviour($controller, $params) {
		$controller->Navigation->addCrumb('List of Behaviour');
		$header = __('List of Behaviour');
		$data = $this->findAllByStaffId($controller->staffId);
		//$data = $this->getBehaviourData($controller->staffId);
		if (empty($data)) {
			$controller->Message->alert('general.noData');
			// $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
		}
		$test = $this->findByStaffId($controller->staffId);

		$controller->set(compact('data', 'header'));
	}

	public function behaviourView($controller, $params) {
		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		$controller->Navigation->addCrumb('Behaviour Details');
		$header = __('Behaviour Details');
		$this->unbindModel(array('belongsTo' => array('Staff')));
		$data = $this->findById($id); //('all', array('conditions' => array('StaffBehaviour.id' => $staffBehaviourId)));

		if (empty($data)) {
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action' => 'behaviour'));
		}

		$controller->Session->write('StaffBehaviourId', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'data', 'fields', 'id'));
		/* if (!empty($staffBehaviourObj)) {
		  $staffId = $staffBehaviourObj['StaffBehaviour']['staff_id'];
		  $Staff = ClassRegistry::init('Staff');
		  $data = $Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
		  $controller->Navigation->addCrumb('Behaviour Details');

		  $SchoolYear = ClassRegistry::init('SchoolYear');
		  $StaffBehaviourCategory = ClassRegistry::init('StaffBehaviourCategory');
		  $InstitutionSite = ClassRegistry::init('InstitutionSite');
		  $yearOptions = array();
		  $yearOptions = $SchoolYear->getYearList();
		  $categoryOptions = array();
		  $categoryOptions = $StaffBehaviourCategory->getCategory();

		  $institutionSiteOptions = $InstitutionSite->find('list', array('recursive' => -1));
		  $controller->set('institution_site_id', $staffBehaviourObj['StaffBehaviour']['institution_site_id']);
		  $controller->set('institutionSiteOptions', $institutionSiteOptions);
		  $controller->Session->write('StaffBehaviourId', $staffBehaviourId);
		  $controller->set('categoryOptions', $categoryOptions);
		  $controller->set('yearOptions', $yearOptions);
		  $controller->set('staffBehaviourObj', $staffBehaviourObj);
		  } else {
		  return $controller->redirect(array('action' => 'behaviour'));
		  } */
	}

	public function staffsBehaviour($controller, $params) {
		extract($controller->staffCustFieldYrInits());
		$controller->Navigation->addCrumb('List of Behaviour');

		$data = $this->getBehaviourData($id);
		if (empty($data)) {
			$controller->Utility->alert($controller->Utility->getMessage('TEACHER_NO_BEHAVIOUR_DATA'), array('type' => 'info'));
		}

		//$controller->set('id', $id);
		//$controller->set('data', $data);
		
		$controller->set(compact('data', 'id'));
	}

	public function staffsBehaviourAdd($controller, $params) {
		if ($controller->request->is('get')) {
			$staffId = $controller->params['pass'][0];
			$data = $controller->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
			$name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
			$controller->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
			$controller->Navigation->addCrumb('Add Behaviour');

			$yearOptions = array();
			$yearOptions = $controller->SchoolYear->getYearList();

			$categoryOptions = array();
			$categoryOptions = $controller->StaffBehaviourCategory->getCategory();
			$institutionSiteOptions = $controller->InstitutionSite->find('list', array('recursive' => -1, 'conditions' => array('id' => $controller->institutionSiteId)));
			//$controller->set('institution_site_id', $controller->institutionSiteId);
			//$controller->set('institutionSiteOptions', $institutionSiteOptions);
			//$controller->set('id', $staffId);
			//$controller->set('categoryOptions', $categoryOptions);
			//$controller->set('yearOptions', $yearOptions);
			
			$institutionSiteId = $controller->institutionSiteId;
			
			$controller->set(compact('institutionSiteId', 'institutionSiteOptions', 'staffId', 'categoryOptions', 'yearOptions'));
		} else {
			$staffBehaviourData = $controller->data['InstitutionSiteStaffBehaviour'];
			$staffBehaviourData['institution_site_id'] = $controller->institutionSiteId;

			$this->create();
			if (!$this->save($staffBehaviourData)) {
				
			} else {
				$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
			}

			$controller->redirect(array('action' => 'staffsBehaviour', $staffBehaviourData['staff_id']));
		}
	}

	public function staffsBehaviourView($controller, $params) {
		$staffBehaviourId = $controller->params['pass'][0];
		$staffBehaviourObj = $this->find('all', array('conditions' => array('StaffBehaviour.id' => $staffBehaviourId)));

		if (!empty($staffBehaviourObj)) {
			$staffId = $staffBehaviourObj[0]['StaffBehaviour']['staff_id'];
			$data = $controller->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
			$name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
			$controller->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
			$controller->Navigation->addCrumb('Behaviour Details');

			$yearOptions = array();
			$yearOptions = $controller->SchoolYear->getYearList();
			$categoryOptions = array();
			$categoryOptions = $controller->StaffBehaviourCategory->getCategory();
			$institutionSiteOptions = $controller->InstitutionSite->find('list', array('recursive' => -1, 'conditions' => array('id' => $controller->institutionSiteId)));
			
			//$controller->set('institution_site_id', $controller->institutionSiteId);
			//$controller->set('institutionSiteOptions', $institutionSiteOptions);
			$controller->Session->write('StaffBehaviourId', $staffBehaviourId);
			//$controller->set('categoryOptions', $categoryOptions);
			//$controller->set('yearOptions', $yearOptions);
			//$controller->set('staffBehaviourObj', $staffBehaviourObj);
			
			$institutionSiteId = $controller->institutionSiteId;
			
			$controller->set(compact('institutionSiteId', 'institutionSiteOptions', 'categoryOptions', 'yearOptions', 'staffBehaviourObj'));
		} else {
			//$controller->redirect(array('action' => 'classesList'));
		}
	}

	public function staffsBehaviourEdit($controller, $params) {
		if ($controller->request->is('get')) {
			$staffBehaviourId = $controller->params['pass'][0];
			$staffBehaviourObj = $this->find('all', array('conditions' => array('StaffBehaviour.id' => $staffBehaviourId)));

			if (!empty($staffBehaviourObj)) {
				$staffId = $staffBehaviourObj[0]['StaffBehaviour']['staff_id'];
				if ($staffBehaviourObj[0]['StaffBehaviour']['institution_site_id'] != $controller->institutionSiteId) {
					$controller->Utility->alert($controller->Utility->getMessage('SECURITY_NO_ACCESS'));
					$controller->redirect(array('action' => 'staffsBehaviourView', $staffBehaviourId));
				}
				$data = $controller->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
				$name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
				$controller->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
				$controller->Navigation->addCrumb('Edit Behaviour Details');

				$categoryOptions = array();
				$categoryOptions = $controller->StaffBehaviourCategory->getCategory();
				$institutionSiteOptions = $controller->InstitutionSite->find('list', array('recursive' => -1, 'conditions' => array('id' => $controller->institutionSiteId)));
				
				//$controller->set('institution_site_id', $controller->institutionSiteId);
				//$controller->set('institutionSiteOptions', $institutionSiteOptions);
				//$controller->set('categoryOptions', $categoryOptions);
				//$controller->set('staffBehaviourObj', $staffBehaviourObj);
				
				$institutionSiteId = $controller->institutionSiteId;
			
			$controller->set(compact('institutionSiteId', 'institutionSiteOptions', 'categoryOptions', 'staffBehaviourObj'));
			} else {
				//$controller->redirect(array('action' => 'studentsBehaviour'));
			}
		} else {
			$staffBehaviourData = $controller->data['InstitutionSiteStaffBehaviour'];
			$staffBehaviourData['institution_site_id'] = $controller->institutionSiteId;

			$this->create();
			if (!$this->save($staffBehaviourData)) {
				
			} else {
				$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
			}

			$controller->redirect(array('action' => 'staffsBehaviourView', $staffBehaviourData['id']));
		}
	}

	public function staffsBehaviourDelete($controller, $params) {
		if ($controller->Session->check('InstitutionSiteStaffId') && $controller->Session->check('StaffBehaviourId')) {
			$id = $controller->Session->read('StaffBehaviourId');
			$staffId = $controller->Session->read('InstitutionSiteStaffId');
			$name = $this->field('title', array('StaffBehaviour.id' => $id));
			$institution_site_id = $this->field('institution_site_id', array('StaffBehaviour.id' => $id));
			if ($institution_site_id != $controller->institutionSiteId) {
				$controller->Utility->alert($controller->Utility->getMessage('SECURITY_NO_ACCESS'));
				$controller->redirect(array('action' => 'staffsBehaviourView', $id));
			}
			$this->delete($id);
			$controller->Utility->alert($name . ' have been deleted successfully.');
			$controller->redirect(array('action' => 'staffsBehaviour', $staffId));
		}
	}

	public function staffsBehaviourCheckName($controller, $params) {
		$controller->render = false;
		$title = trim($controller->params->query['title']);

		if (strlen($title) == 0) {
			return $controller->Utility->getMessage('SITE_STUDENT_BEHAVIOUR_EMPTY_TITLE');
		}

		return 'true';
	}

}

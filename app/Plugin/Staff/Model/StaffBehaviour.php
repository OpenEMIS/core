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
		'Staff',
		'StaffBehaviourCategory',
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
	
	/*public function getBehaviourData($staffId){
		$list = $this->find('all',array(
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
                'fields' =>array('StaffBehaviour.id','StaffBehaviour.title','StaffBehaviour.date_of_behaviour',
								 'StaffBehaviourCategory.name', 'InstitutionSite.name', 'InstitutionSite.id'),
                'conditions'=>array('StaffBehaviour.staff_id' => $staffId)));
		return $list;
	}*/
	
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
        $data = $this->findById($id);//('all', array('conditions' => array('StaffBehaviour.id' => $staffBehaviourId)));

		if(empty($data)){
			$controller->Message->alert('general.noData');
            return $controller->redirect(array('action' => 'behaviour'));
		}
		
		$controller->Session->write('StaffBehaviourId', $id);
		$fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields', 'id'));
        /*if (!empty($staffBehaviourObj)) {
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
        }*/
    }
}

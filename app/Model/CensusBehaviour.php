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

class CensusBehaviour extends AppModel {
        public $actsAs = array(
                'ControllerAction'
	);
    
	public $belongsTo = array(
		'SchoolYear',
		'StudentBehaviourCategory',
		'InstitutionSite'
	);
	
	public function getCensusData($siteId, $yearId) {
		$StudentBehaviourCategory = ClassRegistry::init('Students.StudentBehaviourCategory');
		$StudentBehaviourCategory->formatResult = true;
		
		$data = $StudentBehaviourCategory->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'CensusBehaviour.id', 'CensusBehaviour.male', 'CensusBehaviour.female', 
				'CensusBehaviour.source', 'StudentBehaviourCategory.name', 'StudentBehaviourCategory.id AS student_behaviour_category_id'
			),
			'joins' => array(
				array(
					'table' => 'census_behaviours',
					'alias' => 'CensusBehaviour',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusBehaviour.institution_site_id = ' . $siteId,
						'CensusBehaviour.school_year_id = ' . $yearId,
						'CensusBehaviour.student_behaviour_category_id = StudentBehaviourCategory.id'
					)
				)
			),
			'conditions' => array('StudentBehaviourCategory.visible' => 1),
			'order' => array('StudentBehaviourCategory.order')
		));
		return $data;
	}
	
	public function saveCensusData($data, $institutionSiteId) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);
		
		foreach($data as $obj) {
			$obj['school_year_id'] = $yearId;
			$obj['institution_site_id'] = $institutionSiteId;
			if($obj['id'] == 0) {
				$this->create();
			}
			$save = $this->save(array('CensusBehaviour' => $obj));
		}
	}
        
        public function getYearsHaveData($institutionSiteId){
            $data = $this->find('all', array(
                    'recursive' => -1,
                    'fields' => array(
                        'SchoolYear.id',
                        'SchoolYear.name'
                    ),
                    'joins' => array(
                            array(
                                'table' => 'school_years',
                                'alias' => 'SchoolYear',
                                'conditions' => array(
                                    'CensusBehaviour.school_year_id = SchoolYear.id'
                                )
                            )
                    ),
                    'conditions' => array('CensusBehaviour.institution_site_id' => $institutionSiteId),
                    'group' => array('CensusBehaviour.school_year_id'),
                    'order' => array('SchoolYear.name DESC')
                )
            ); 
            
            return $data;
        }
        
        public function behaviour($controller, $params) {
        $controller->Navigation->addCrumb('Behaviour');

        $yearList = $controller->SchoolYear->getYearList();
        $selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
        $data = $controller->CensusBehaviour->getCensusData($controller->institutionSiteId, $selectedYear);

        $isEditable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
        
        $controller->set(compact('selectedYear', 'yearList', 'data', 'isEditable'));
    }

    public function behaviourEdit($controller, $params) {
        if ($controller->request->is('get')) {
            $controller->Navigation->addCrumb('Edit Behaviour');

            $yearList = $controller->SchoolYear->getAvailableYears();
            $selectedYear = $controller->getAvailableYearId($yearList);
            $data = $controller->CensusBehaviour->getCensusData($controller->institutionSiteId, $selectedYear);
            $editable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
            if (!$editable) {
                $controller->redirect(array('action' => 'behaviour', $selectedYear));
            } else {
                
                $controller->set(compact('selectedYear', 'yearList', 'data'));
            }
        } else {
            $data = $controller->data['CensusBehaviour'];
            $yearId = $data['school_year_id'];
            $controller->CensusBehaviour->saveCensusData($data, $controller->institutionSiteId);
            $controller->Utility->alert($controller->Utility->getMessage('CENSUS_UPDATED'));
            $controller->redirect(array('controller' => 'Census', 'action' => 'behaviour', $yearId));
        }
    }
}
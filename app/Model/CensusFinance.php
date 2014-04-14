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

class CensusFinance extends AppModel {
        public $actsAs = array(
                'ControllerAction'
	);
    
	public $belongsTo = array(
		'FinanceSource'=> array('foreignKey' => 'finance_source_id'),
		'FinanceCategory' => array('foreignKey' => 'finance_category_id'),
		'SchoolYear' => array('foreignKey' => 'school_year_id')
	);
        
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
                                    'CensusFinance.school_year_id = SchoolYear.id'
                                )
                            )
                    ),
                    'conditions' => array('CensusFinance.institution_site_id' => $institutionSiteId),
                    'group' => array('CensusFinance.school_year_id'),
                    'order' => array('SchoolYear.name DESC')
                )
            );
            
            return $data;
        }
        
        public function finances($controller, $params) {
        $controller->Navigation->addCrumb('Finances');

        if ($controller->request->is('post')) {
            $yearId = $controller->data['CensusFinance']['school_year_id'];
            $controller->request->data['CensusFinance']['institution_site_id'] = $controller->institutionSiteId;
            $controller->CensusFinance->save($controller->request->data['CensusFinance']);

            $controller->redirect(array('action' => 'finances', $yearId));
        }

        $yearList = $controller->SchoolYear->getYearList();
        $selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
        $data = $controller->CensusFinance->find('all', array('recursive' => 3, 'conditions' => array('CensusFinance.institution_site_id' => $controller->institutionSiteId, 'CensusFinance.school_year_id' => $selectedYear)));
        $newSort = array();
        //pr($data);
        foreach ($data as $k => $arrv) {
            //$newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][$arrv['FinanceCategory']['name']][] = $arrv;
            $newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][] = $arrv;
        }
        $natures = $controller->FinanceNature->find('list', array('recursive' => 2, 'conditions' => array('FinanceNature.visible' => 1)));
        $sources = $controller->FinanceSource->find('list', array('conditions' => array('FinanceSource.visible' => 1)));
        
        $isEditable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
        
        $controller->set(compact('data', 'selectedYear', 'yearList', 'natures', 'sources', 'isEditable'));
    }

    public function financesEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Finances');

        if ($controller->request->is('post')) {
            $data = $controller->data['CensusFinance'];
            $yearId = $data['school_year_id'];
            unset($data['school_year_id']);
            foreach ($data as &$val) {
                $val['institution_site_id'] = $controller->institutionSiteId;
                $val['school_year_id'] = $yearId;
            }
            //pr($controller->request->data);die;
            $controller->CensusFinance->saveMany($data);

            $controller->redirect(array('action' => 'finances', $yearId));
        }

        $yearList = $controller->SchoolYear->getAvailableYears();
        $selectedYear = $controller->getAvailableYearId($yearList);
        $editable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
        if (!$editable) {
            $controller->redirect(array('action' => 'finances', $selectedYear));
        } else {
            $data = $controller->CensusFinance->find('all', array('recursive' => 3, 'conditions' => array('CensusFinance.institution_site_id' => $controller->institutionSiteId, 'CensusFinance.school_year_id' => $selectedYear)));
            $newSort = array();
            //pr($data);
            foreach ($data as $k => $arrv) {
                //$newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][$arrv['FinanceCategory']['name']][] = $arrv;


                $arrv['CategoryTypes'] = $controller->getFinanceCatByFinanceType($arrv['FinanceCategory']['FinanceType']['id']);
                $newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][] = $arrv;
            }

            $natures = $controller->FinanceNature->find('list', array('recursive' => 2, 'conditions' => array('FinanceNature.visible' => 1)));
            $sources = $controller->FinanceSource->find('list', array('conditions' => array('FinanceSource.visible' => 1)));

            $controller->set(compact('data', 'selectedYear', 'yearList', 'natures', 'sources'));
        }
    }
}

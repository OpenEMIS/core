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

class CensusGraduate extends AppModel {
    public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
    
	public $belongsTo = array(
		'SchoolYear' => array('foreignKey' => 'school_year_id')
	);
	
	public function getCensusData($siteId, $yearId) {
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		$InstitutionSiteProgramme->formatResult = true;
		$list = $InstitutionSiteProgramme->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'EducationLevel.name AS education_level_name',
				'EducationCycle.name AS education_cycle_name',
				'EducationProgramme.id AS education_programme_id',
				'EducationProgramme.name AS education_programme_name',
				'EducationCertification.id AS education_certification_id',
				'EducationCertification.name AS education_certification_name',
				'InstitutionSiteProgramme.institution_site_id',
				'CensusGraduate.id',
				'CensusGraduate.male',
				'CensusGraduate.female',
				'CensusGraduate.source'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'education_certifications',
					'alias' => 'EducationCertification',
					'conditions' => array('EducationCertification.id = EducationProgramme.education_certification_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				),
				array(
					'table' => 'census_graduates',
					'alias' => 'CensusGraduate',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusGraduate.education_programme_id = InstitutionSiteProgramme.education_programme_id',
						'CensusGraduate.institution_site_id = InstitutionSiteProgramme.institution_site_id',
						'CensusGraduate.school_year_id = InstitutionSiteProgramme.school_year_id'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $siteId,
				'InstitutionSiteProgramme.school_year_id' => $yearId
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		$data = array();
		foreach($list as $obj) {
			if($obj['education_certification_id'] != 1) {
				$level = $obj['education_level_name'];
				if(!isset($data[$level])) {
					$data[$level] = array();
				}
				$obj['education_programme_name'] = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
				$obj['total'] = $obj['male'] + $obj['female'];
				$data[$level][] = $obj;
			}
		}
		return $data;
	}
	
	public function saveCensusData($data) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);
		//pr($data);die;
		foreach($data as $obj) {
			$obj['school_year_id'] = $yearId;
			if($obj['id'] == 0) {
				if($obj['male'] > 0 || $obj['female'] > 0) {
					$this->create();
					$this->save(array('CensusGraduate' => $obj));
				}
			} else {
				$this->save(array('CensusGraduate' => $obj));
			}
		}
	}
        
        public function graduates($controller, $params) {
        $controller->Navigation->addCrumb('Graduates');
        $yearList = $controller->SchoolYear->getYearList();
        $selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);

        $programmes = $controller->InstitutionSiteProgramme->getSiteProgrammes($controller->institutionSiteId, $selectedYear);
        $data = array();
        if (empty($programmes)) {
            $controller->Utility->alert($controller->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
        } else {
            $data = $controller->CensusGraduate->getCensusData($controller->institutionSiteId, $selectedYear);
            if (empty($data)) {
                $controller->Utility->alert($controller->Utility->getMessage('CENSUS_GRADUATE_NOT_REQUIRED'), array('type' => 'info'));
            }
        }
        
        $isEditable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
        
        $controller->set(compact('selectedYear', 'yearList', 'data', 'isEditable'));
    }

    public function graduatesEdit($controller, $params) {
        if ($controller->request->is('post')) {
            $data = $controller->data['CensusGraduate'];
            $yearId = $data['school_year_id'];
            $controller->CensusGraduate->saveCensusData($data);
            $controller->Utility->alert($controller->Utility->getMessage('CENSUS_UPDATED'));
            $controller->redirect(array('action' => 'graduates', $yearId));
        }
        $controller->Navigation->addCrumb('Edit Graduates');

        $yearList = $controller->SchoolYear->getAvailableYears();
        $selectedYear = $controller->getAvailableYearId($yearList);
        $programmes = $controller->InstitutionSiteProgramme->getSiteProgrammes($controller->institutionSiteId, $selectedYear);
        $data = array();
        $editable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
        if (!$editable) {
            $controller->redirect(array('action' => 'graduates', $selectedYear));
        } else {
            if (empty($programmes)) {
                $controller->Utility->alert($controller->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
            } else {
                $data = $controller->CensusGraduate->getCensusData($controller->institutionSiteId, $selectedYear);
                if (empty($data)) {
                    $controller->Utility->alert($controller->Utility->getMessage('CENSUS_GRADUATE_NOT_REQUIRED'), array('type' => 'info'));
                }
            }
        }
        
        $controller->set(compact('selectedYear', 'yearList', 'data'));
    }
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return array();
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$data = array();
			$header = array(__('Year'), __('Education Level'), __('Education Programme'), __('Certification'), __('Male'), __('Female'), __('Total'));

			$InstitutionSiteProgrammeModel = ClassRegistry::init('InstitutionSiteProgramme');
			$dataYears = $InstitutionSiteProgrammeModel->getYearsHaveProgrammes($institutionSiteId);

			foreach ($dataYears AS $rowYear) {
				$yearId = $rowYear['SchoolYear']['id'];
				$yearName = $rowYear['SchoolYear']['name'];

				$dataCensus = $this->getCensusData($institutionSiteId, $yearId);

				if (count($dataCensus) > 0) {
					foreach ($dataCensus AS $levelName => $dataByLevel) {
						$data[] = $header;
						foreach ($dataByLevel AS $rowCensus) {
							$programme = $rowCensus['education_programme_name'];
							$certificationName = $rowCensus['education_certification_name'];
							$male = empty($rowCensus['male']) ? 0 : $rowCensus['male'];
							$female = empty($rowCensus['female']) ? 0 : $rowCensus['female'];
							$total = $male + $female;

							$data[] = array(
								$yearName,
								$levelName,
								$programme,
								$certificationName,
								$male,
								$female,
								$total
							);
						}
						$data[] = array();
					}
				}
			}
			//pr($data);
			return $data;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return 'Report_Totals_Graduates';
	}
}

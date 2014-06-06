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

class CensusShift extends AppModel {
    public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
    
	public $belongsTo = array(
		'CensusClass'
	);
	
	public function getShiftId($institutionSiteId, $yearId) {
		$data = $this->find('list', array(
			'fields' => array('CensusShift.id'),
			'joins' => array(
				array(
					'table' => 'census_classes',
					'alias' => 'CensusClass',
					'conditions' => array(
						'CensusClass.id = CensusShift.census_class_id'
					)
				)
			),
			'conditions' => array('CensusClass.institution_site_id' => $institutionSiteId, 'CensusClass.school_year_id' => $yearId)
		));
		return $data;
	}
	
	public function mergeSingleGradeData(&$class, $data) {
		foreach($class as $key => &$obj) {
			$shift = array();
			$source = 0;
			$shift_pk = array();

			foreach($data as $value) {
				if($value['census_class_id'] == $obj['id']) {
					if(isset($value['shift_id'])){
						$shiftId = $value['shift_id'];
						$shiftValue = $value['value'];

						$shift['shift_' . $shiftId] = $shiftValue;
						$shift_pk['shift_pk_' . $shiftId] = $value['id'];
					}
					$source = $value['source'];
				}
				
				$obj = array_merge($obj, array_merge($shift, $shift_pk, array('shift_source' => $source)));
			}
		}
	}


	public function mergeMultiGradeData(&$class, $data) {
		foreach($class as $key => &$obj) {
			
			$shift = array();
			$source = 0;
			$shift_pk = array();
			foreach($data as $value) {
				if($value['census_class_id'] == $key) {
					if(isset($value['shift_id'])){
						$shiftId = $value['shift_id'];
						$shiftValue = $value['value'];

						$shift['shift_' . $shiftId] = $shiftValue;
						$shift_pk['shift_pk_' . $shiftId] = $value['id'];
					}
					$source = $value['source'];
				}
				
				$obj = array_merge($obj, array_merge($shift, $shift_pk, array('shift_source' => $source, 'id' => $key)));
			}
		}
	}

	
	public function getData($institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusShift.id',
				'CensusShift.census_class_id',
				'CensusShift.shift_id',
				'CensusShift.value',
				'CensusShift.source',
			),
			'joins' => array(
				array(
					'table' => 'census_classes',
					'alias' => 'CensusClass',
					'conditions' => array(
						'CensusClass.id = CensusShift.census_class_id'
					)
				)
			),
			'conditions' => array(
				'CensusClass.school_year_id' => $yearId,
				'CensusClass.institution_site_id' => $institutionSiteId
			)
		));
		
		return $data;
	}
        
        public function shifts($controller, $params) {
        $controller->Navigation->addCrumb('Shifts');

        $yearList = $controller->SchoolYear->getYearList();
        $selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
        $displayContent = true;

        $programmeGrades = $controller->InstitutionSiteProgramme->getProgrammeList($controller->institutionSiteId, $selectedYear);
        $singleGradeClasses = $controller->CensusShift->getData($controller->institutionSiteId, $selectedYear);
        $singleGradeData = $controller->CensusClass->getSingleGradeData($controller->institutionSiteId, $selectedYear);
        $multiGradeData = $controller->CensusClass->getMultiGradeData($controller->institutionSiteId, $selectedYear);


        if (empty($singleGradeData) && empty($multiGradeData)) {
            $controller->Utility->alert($controller->Utility->getMessage('CENSUS_NO_CLASS'), array('type' => 'warn', 'dismissOnClick' => false));
            $displayContent = false;
        } else {
            $controller->CensusShift->mergeSingleGradeData($singleGradeData, $singleGradeClasses);

            $controller->CensusShift->mergeMultiGradeData($multiGradeData, $singleGradeClasses);
            
            $controller->set(compact('singleGradeData', 'multiGradeData'));
        }

        $noOfShifts = $controller->ConfigItem->getValue('no_of_shifts');
        $isEditable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
        
        $controller->set(compact('noOfShifts', 'displayContent', 'selectedYear', 'yearList', 'isEditable'));
    }

    public function shiftsEdit($controller, $params) {

        if ($controller->request->is('post')) {
            if (!empty($controller->request->data)) {
                $data = $controller->data['CensusShift'];
                $yearId = $controller->data['CensusShift']['school_year_id'];
                unset($controller->request->data['CensusShift']['school_year_id']);

                $saveData = array();
                $errorMsg = array();
                $errorFlag = false;

                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $shiftTotal = 0;
                        $classTotal = 0;
                        foreach ($value as $key2 => $value2) {
                            if ($key2 != 'shift_class_total' && $key2 != 'shift_total') {
                                $shiftTotal += $value2;
                            }
                            $classTotal = $value['shift_class_total'];
                        }

                        if ($shiftTotal != $classTotal) {
                            $errorFlag = true;
                            break;
                        }
                    }
                }

                if (!$errorFlag) {
                    foreach ($data as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $key2 => $value2) {
                                if ($key2 != 'shift_class_total' && $key2 != 'shift_total') {
                                    if (isset($value['shift_pk_' . str_replace("shift_value_", "", $key2)])) {
                                        $saveData[] = array('CensusShift' => array('id' => $value['shift_pk_' . str_replace("shift_value_", "", $key2)], 'census_class_id' => $key, 'shift_id' => str_replace("shift_value_", "", $key2), 'value' => $value2, 'source' => '0'));
                                    } else {
                                        $saveData[] = array('CensusShift' => array('census_class_id' => $key, 'shift_id' => str_replace("shift_value_", "", $key2), 'value' => $value2, 'source' => '0'));
                                    }
                                }
                            }
                        }
                    }
                    $controller->CensusShift->saveAll($saveData);

                    $controller->Utility->alert($controller->Utility->getMessage('CENSUS_UPDATED'));
                    $controller->redirect(array('action' => 'shifts', $yearId));
                } else {
                    $controller->Utility->alert($controller->Utility->getMessage('CENSUS_SHIFT_CLASS_MISMATCH'), array('type' => 'warn', 'dismissOnClick' => false));
                    $controller->redirect(array('action' => 'shiftsEdit', $yearId));
                }
            }
        }

        $controller->Navigation->addCrumb('Edit Shifts');

        $yearList = $controller->SchoolYear->getAvailableYears();
        $selectedYear = $controller->getAvailableYearId($yearList);
        $editable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
        if (!$editable) {
            $controller->redirect(array('action' => 'shifts', $selectedYear));
        } else {
            $displayContent = true;
            $programmeGrades = $controller->InstitutionSiteProgramme->getProgrammeList($controller->institutionSiteId, $selectedYear);
            $singleGradeClasses = $controller->CensusShift->getData($controller->institutionSiteId, $selectedYear);
            $singleGradeData = $controller->CensusClass->getSingleGradeData($controller->institutionSiteId, $selectedYear);
            $multiGradeData = $controller->CensusClass->getMultiGradeData($controller->institutionSiteId, $selectedYear);

            if (empty($singleGradeData) && empty($multiGradeData)) {
                $controller->Utility->alert($controller->Utility->getMessage('CENSUS_NO_CLASS'), array('type' => 'warn', 'dismissOnClick' => false));
                $displayContent = false;
            } else {
                $controller->CensusShift->mergeSingleGradeData($singleGradeData, $singleGradeClasses);

                $controller->CensusShift->mergeMultiGradeData($multiGradeData, $singleGradeClasses);
                
                $controller->set(compact('singleGradeData', 'multiGradeData', '', '', '', '', '', '', ''));
            }

            $noOfShifts = $controller->ConfigItem->getValue('no_of_shifts');
            
            $controller->set(compact('noOfShifts', 'displayContent', 'selectedYear', 'yearList'));
        }
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
			$header = array(__('Year'), __('Class Type'), __('Programme'), __('Grade'), __('Classes'));
			
			$ConfigItemModel = ClassRegistry::init('ConfigItem');
			$no_of_shifts = $ConfigItemModel->getValue('no_of_shifts');
			
			for ($i = 1; $i <= intval($no_of_shifts); $i++) {
				$header[] = __('Shift') . $i;
			}

			$header[] = __('Total');

			$InstitutionSiteProgrammeModel = ClassRegistry::init('InstitutionSiteProgramme');
			$dataYears = $InstitutionSiteProgrammeModel->getYearsHaveProgrammes($institutionSiteId);

			foreach ($dataYears AS $rowYear) {
				$yearId = $rowYear['SchoolYear']['id'];
				$yearName = $rowYear['SchoolYear']['name'];

				$singleGradeClasses = $this->getData($institutionSiteId, $yearId);
				
				$CensusClassModel = ClassRegistry::init('CensusClass');
				$singleGradeData = $CensusClassModel->getSingleGradeData($institutionSiteId, $yearId);
				$multiGradeData = $CensusClassModel->getMultiGradeData($institutionSiteId, $yearId);

				$this->mergeSingleGradeData($singleGradeData, $singleGradeClasses);
				$this->mergeMultiGradeData($multiGradeData, $singleGradeClasses);

				// single grade classes data start
				if (count($singleGradeData) > 0) {
					$data[] = $header;
					$totalClasses = 0;
					foreach ($singleGradeData AS $rowSingleGrade) {
						$preDataRow = array(
							$yearName,
							__('Single Grade Classes Only'),
							$rowSingleGrade['education_programme_name'],
							$rowSingleGrade['education_grade_name'],
							$rowSingleGrade['classes']
						);

						$totalShifts = 0;
						for ($i = 1; $i <= intval($no_of_shifts); $i++) {
							$shift = 0;
							if (isset($rowSingleGrade['shift_' . $i])) {
								$shift = $rowSingleGrade['shift_' . $i];
								$totalShifts += $shift;
							}
							$preDataRow[] = $shift;
						}
						$preDataRow[] = $totalShifts;

						$data[] = $preDataRow;
						$totalClasses += $rowSingleGrade['classes'];
					}
					$data[] = array('', '', '', 'Total', $totalClasses);
					$data[] = array();
				}
				// single grade classes data end
				// multi grades classes data start
				if (count($multiGradeData) > 0) {
					$data[] = $header;
					$totalClasses = 0;
					foreach ($multiGradeData AS $rowMultiGrade) {
						$multiProgrammes = '';
						$multiProgrammeCount = 0;
						foreach ($rowMultiGrade['programmes'] AS $multiProgramme) {
							if ($multiProgrammeCount > 0) {
								$multiProgrammes .= "\n\r";
								$multiProgrammes .= $multiProgramme;
							} else {
								$multiProgrammes .= $multiProgramme;
							}
							$multiProgrammeCount++;
						}

						$multiGrades = '';
						$multiGradeCount = 0;
						foreach ($rowMultiGrade['grades'] AS $multiGrade) {
							if ($multiGradeCount > 0) {
								$multiGrades .= "\n\r";
								$multiGrades .= $multiGrade;
							} else {
								$multiGrades .= $multiGrade;
							}
							$multiGradeCount++;
						}

						$preDataRow = array(
							$yearName,
							__('Multi Grade Classes'),
							$multiProgrammes,
							$multiGrades,
							$rowMultiGrade['classes']
						);

						$totalShifts = 0;
						for ($i = 1; $i <= intval($no_of_shifts); $i++) {
							$shift = 0;
							if (isset($rowMultiGrade['shift_' . $i])) {
								$shift = $rowMultiGrade['shift_' . $i];
								$totalShifts += $shift;
							}
							$preDataRow[] = $shift;
						}
						$preDataRow[] = $totalShifts;

						$data[] = $preDataRow;
						$totalClasses += $rowMultiGrade['classes'];
					}
					$data[] = array('', '', '', __('Total'), $totalClasses);
					$data[] = array();
				}
				// multi grades classes data end
			}
			//pr($data);
			return $data;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return 'Report_Totals_Shifts';
	}
}
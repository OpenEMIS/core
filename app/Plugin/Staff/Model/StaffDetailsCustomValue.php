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

class StaffDetailsCustomValue extends StaffAppModel {
    public $actsAs = array(
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution'
                ),
                'Student' => array(
                    'identification_no' => 'Student OpenEMIS ID',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'preferred_name' => ''
                ),
                'StudentBehaviourCategory' => array(
                    'name' => 'Category'
                ),
                'StudentBehaviour' => array(
                    'date_of_behaviour' => 'Date',
                    'title' => 'Title',
                    'description' => 'Description',
                    'action' => 'Action'
                )
            ),
            'fileName' => 'Report_Student_Behaviour'
		)
	);
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$commonFields = array(
				'institution_site' => 'Institution',
				'openemis_id' => 'OpenEMIS ID',
				'first_name' => 'First Name',
				'middle_name' => 'Middle Name',
				'last_name' => 'Last Name',
				'preferred_name' => 'Preferred Name',
				'academic_period' => 'Academic Period'
			);

			$StudentDetailsCustomFieldModel = ClassRegistry::init('StudentDetailsCustomField');

			$customFields = $StudentDetailsCustomFieldModel->find('list', array(
				'fields' => array('id', 'name'),
				'conditions' => array(
					'visible' => 1,
					'type > ' => 1
				),
				'order' => array('order')
					)
			);

			foreach ($commonFields AS &$value) {
				$value = __($value);
			}

			$resultFields = $commonFields;

			foreach ($customFields AS $fieldId => $fieldName) {
				$resultFields[$fieldId] = $fieldName;
			}

			//$resultFields = array_merge($commonFields, $customFields);
			return $resultFields;
		}
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];
		$InstitutionSiteModel = ClassRegistry::init('InstitutionSite');
		$institutionSiteObj = $InstitutionSiteModel->find('first', array('conditions' => array('InstitutionSite.id' => $institutionSiteId)));

		if ($index == 1) {
			$header = $this->reportsGetHeader($args);
			$rowTpl = array();
			foreach ($header AS $key => $field) {
				$rowTpl[$key] = '';
			}
			//pr($rowTpl);
			$data = array();
			
			$StaffDetailsCustomValueModel = ClassRegistry::init('StaffDetailsCustomValue');
			
			$staffAcademicPeriods = $StaffDetailsCustomValueModel->find('all', array(
                'recursive' => -1,
                'fields' => array(
                    'StaffDetailsCustomValue.staff_id',
                    'Staff.identification_no',
                    'Staff.first_name',
                    'Staff.middle_name',
                    'Staff.last_name',
                    'Staff.preferred_name',
                    'StaffDetailsCustomValue.academic_period_id',
                    'AcademicPeriod.name'
                ),
                'joins' => array(
                    array(
                        'table' => 'staff',
                        'alias' => 'Staff',
                        'conditions' => array(
                            'StaffDetailsCustomValue.staff_id = Staff.id'
                        )
                    ),
                    array(
                        'table' => 'academic_periods',
                        'alias' => 'AcademicPeriod',
                        'conditions' => array(
                            'StaffDetailsCustomValue.academic_period_id = AcademicPeriod.id'
                        )
                    )
                ),
                'conditions' => array('StaffDetailsCustomValue.institution_site_id' => $institutionSiteId),
                'group' => array('StaffDetailsCustomValue.staff_id', 'StaffDetailsCustomValue.academic_period_id')
                    )
            );

            foreach ($staffAcademicPeriods AS $rowValue) {
                $fieldValues = $StaffDetailsCustomValueModel->find('all', array(
                    'recursive' => -1,
                    'fields' => array(
                        'StaffDetailsCustomField.id',
                        'StaffDetailsCustomField.name',
                        'StaffDetailsCustomField.type',
                        'StaffDetailsCustomValue.value',
                        'StaffDetailsCustomFieldOption.value'
                    ),
                    'joins' => array(
                        array(
                            'table' => 'staff_details_custom_fields',
                            'alias' => 'StaffDetailsCustomField',
                            'conditions' => array(
                                'StaffDetailsCustomValue.staff_details_custom_field_id = StaffDetailsCustomField.id'
                            )
                        ),
                        array(
                            'table' => 'staff_details_custom_field_options',
                            'alias' => 'StaffDetailsCustomFieldOption',
                            'type' => 'LEFT',
                            'conditions' => array(
                                'StaffDetailsCustomValue.value = StaffDetailsCustomFieldOption.id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'StaffDetailsCustomValue.institution_site_id' => $institutionSiteId,
                        'StaffDetailsCustomValue.staff_id' => $rowValue['StaffDetailsCustomValue']['staff_id'],
                        'StaffDetailsCustomValue.academic_period_id' => $rowValue['StaffDetailsCustomValue']['academic_period_id']
                    )
                        )
                );

                $row = $rowTpl;
                $row['institution_site'] = $institutionSiteObj['InstitutionSite']['name'];
                $row['openemis_id'] = $rowValue['Staff']['identification_no'];
                $row['first_name'] = $rowValue['Staff']['first_name'];
                $row['middle_name'] = $rowValue['Staff']['middle_name'];
                $row['last_name'] = $rowValue['Staff']['last_name'];
                $row['preferred_name'] = $rowValue['Staff']['preferred_name'];
                $row['academic_period'] = $rowValue['AcademicPeriod']['name'];

                foreach ($fieldValues AS $fieldValueRow) {
                    $fieldId = $fieldValueRow['StaffDetailsCustomField']['id'];
                    $fieldName = $fieldValueRow['StaffDetailsCustomField']['name'];
                    $fieldType = $fieldValueRow['StaffDetailsCustomField']['type'];

                    if ($fieldType == 3) {
                        $row[$fieldId] = $fieldValueRow['StaffDetailsCustomFieldOption']['value'];
                    } else if ($fieldType == 4) {
                        if (empty($row[$fieldId])) {
                            $row[$fieldId] = $fieldValueRow['StaffDetailsCustomFieldOption']['value'];
                        } else {
                            $row[$fieldId] .= ', ' . $fieldValueRow['StaffDetailsCustomFieldOption']['value'];
                        }
                    } else {
                        $row[$fieldId] = $fieldValueRow['StaffDetailsCustomValue']['value'];
                    }
                }
                //pr($row);
                $data[] = $row;
            }
		}
		
		return $data;
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		if($index == 1){
			return 'Report_Staff_Academic';
		}
	}

}

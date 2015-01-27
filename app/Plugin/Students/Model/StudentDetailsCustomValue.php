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

class StudentDetailsCustomValue extends StudentsAppModel {
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
                    'third_name' => '',
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
				'third_name' => 'Third Name',
				'last_name' => 'Last Name',
				'preferred_name' => 'Preferred Name',
				'academic_period' => 'Academic Period'
			);

			$StaffDetailsCustomFieldModel = ClassRegistry::init('StaffDetailsCustomField');

			$customFields = $StaffDetailsCustomFieldModel->find('list', array(
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
			
			$StudentDetailsCustomValueModel = ClassRegistry::init('StudentDetailsCustomValue');
			
			$studentsAcademicPeriods = $StudentDetailsCustomValueModel->find('all', array(
                'recursive' => -1,
                'fields' => array(
                    'StudentDetailsCustomValue.student_id',
                    'Student.identification_no',
                    'Student.first_name',
                    'Student.middle_name',
                    'Student.last_name',
                    'Student.preferred_name',
                    'StudentDetailsCustomValue.academic_period_id',
                    'AcademicPeriod.name'
                ),
                'joins' => array(
                    array(
                        'table' => 'students',
                        'alias' => 'Student',
                        'conditions' => array(
                            'StudentDetailsCustomValue.student_id = Student.id'
                        )
                    ),
                    array(
                        'table' => 'academic_periods',
                        'alias' => 'AcademicPeriod',
                        'conditions' => array(
                            'StudentDetailsCustomValue.academic_period_id = AcademicPeriod.id'
                        )
                    )
                ),
                'conditions' => array('StudentDetailsCustomValue.institution_site_id' => $institutionSiteId),
                'group' => array('StudentDetailsCustomValue.student_id', 'StudentDetailsCustomValue.academic_period_id')
                    )
            );

            foreach ($studentsAcademicPeriods AS $rowValue) {
                $fieldValues = $StudentDetailsCustomValueModel->find('all', array(
                    'recursive' => -1,
                    'fields' => array(
                        'StudentDetailsCustomField.id',
                        'StudentDetailsCustomField.name',
                        'StudentDetailsCustomField.type',
                        'StudentDetailsCustomValue.value',
                        'StudentDetailsCustomFieldOption.value'
                    ),
                    'joins' => array(
                        array(
                            'table' => 'student_details_custom_fields',
                            'alias' => 'StudentDetailsCustomField',
                            'conditions' => array(
                                'StudentDetailsCustomValue.student_details_custom_field_id = StudentDetailsCustomField.id'
                            )
                        ),
                        array(
                            'table' => 'student_details_custom_field_options',
                            'alias' => 'StudentDetailsCustomFieldOption',
                            'type' => 'LEFT',
                            'conditions' => array(
                                'StudentDetailsCustomValue.value = StudentDetailsCustomFieldOption.id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'StudentDetailsCustomValue.institution_site_id' => $institutionSiteId,
                        'StudentDetailsCustomValue.student_id' => $rowValue['StudentDetailsCustomValue']['student_id'],
                        'StudentDetailsCustomValue.academic_period_id' => $rowValue['StudentDetailsCustomValue']['academic_period_id']
                    )
                        )
                );

                $row = $rowTpl;
                $row['institution_site'] = $institutionSiteObj['InstitutionSite']['name'];
                $row['openemis_id'] = $rowValue['Student']['identification_no'];
                $row['first_name'] = $rowValue['Student']['first_name'];
                $row['middle_name'] = $rowValue['Student']['middle_name'];
                $row['last_name'] = $rowValue['Student']['last_name'];
                $row['preferred_name'] = $rowValue['Student']['preferred_name'];
                $row['academic_period'] = $rowValue['AcademicPeriod']['name'];

                foreach ($fieldValues AS $fieldValueRow) {
                    $fieldId = $fieldValueRow['StudentDetailsCustomField']['id'];
                    $fieldName = $fieldValueRow['StudentDetailsCustomField']['name'];
                    $fieldType = $fieldValueRow['StudentDetailsCustomField']['type'];

                    if ($fieldType == 3) {
                        $row[$fieldId] = $fieldValueRow['StudentDetailsCustomFieldOption']['value'];
                    } else if ($fieldType == 4) {
                        if (empty($row[$fieldId])) {
                            $row[$fieldId] = $fieldValueRow['StudentDetailsCustomFieldOption']['value'];
                        } else {
                            $row[$fieldId] .= ', ' . $fieldValueRow['StudentDetailsCustomFieldOption']['value'];
                        }
                    } else {
                        $row[$fieldId] = $fieldValueRow['StudentDetailsCustomValue']['value'];
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
			return 'Report_Student_Academic';
		}
	}

}

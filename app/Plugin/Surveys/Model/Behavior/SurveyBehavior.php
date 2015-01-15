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

class SurveyBehavior extends ModelBehavior {
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}

	/*/*not working
	public function beforeSave(Model $model, $options=array()) {	
		$modelValue = $this->settings[$model->alias]['customfields']['modelValue'];

		foreach ($model->data[$modelValue] as $key => $obj) {
			switch($obj[$modelValue]['type']) {
				case 2:
					$fieldName = 'text_value';
					break;
				case 3:
					$fieldName = 'int_value';
					break;
				case 4:
					$fieldName = 'int_value';
					break;
				case 5:
					$fieldName = 'textarea_value';
					break;
				case 6:
					$fieldName = 'int_value';
					break;
				default:
					$fieldName = 'text_value';
			}
			if(empty($obj[$modelValue][$fieldName])) {
				unset($model->data[$modelValue][$key]);
			}
		}

		return parent::beforeSave($model, $options);
	}
	*/

	public function getSurveyTemplatesByModule(Model $model) {
		$moduleName = $this->settings[$model->alias]['module'];
		$moduleId = ClassRegistry::init('Surveys.SurveyModule')->field('id', array('SurveyModule.name' => $moduleName));
		$todayDate = date("Y-m-d");
		$table = $model->useTable;

		$status = $this->settings[$model->alias]['status'];

		$model->SurveyTemplate->contain();
		
		// Find all templates by module
		$templates = $model->SurveyTemplate->findAllBySurveyModuleId($moduleId);

		$SurveyStatus = $model->SurveyTemplate->SurveyStatus;
		$SurveyStatus->contain();

		// for each template, find available survey statuses
		foreach ($templates as $i => $template) {
			$options = array();
			$templateId = $template['SurveyTemplate']['id'];
			$statuses = $SurveyStatus->find('list', array(
				'conditions' => array(
					'SurveyStatus.survey_template_id' => $templateId,
					'SurveyStatus.date_disabled >=' => $todayDate
				)
			));

			$fields = array('AcademicPeriod.id', 'AcademicPeriod.name', 'SurveyStatus.date_disabled');

			// get all available periods based on status and template
			$joins = array(
				array(
					'table' => 'survey_status_periods',
					'alias' => 'SurveyStatusPeriod',
					'conditions' => array(
						'SurveyStatusPeriod.academic_period_id = AcademicPeriod.id',
						'SurveyStatusPeriod.survey_status_id' => $statuses,
					)
				),
				array(
					'table' => 'survey_statuses',
					'alias' => 'SurveyStatus',
					'conditions' => array(
						'SurveyStatus.id = SurveyStatusPeriod.survey_status_id'
					)
				)
			);
	
			$existingPeriods = array();
			$existingPeriodsOptions = array(
				'fields' => array('AcademicPeriod.id'),
				'conditions' => $model->surveyGetConditions($model)
			);
			$existingPeriodsOptions['conditions'][$model->alias . '.survey_template_id'] = $templateId;
			
			$model->contain('AcademicPeriod');

			// if it's a new survey, exclude periods from draft or complete
			if ($status == 0) { // Survey New
				$existingPeriodsOptions['conditions'][$model->alias . '.status'] = array(1, 2);
			} else if ($status == 1 || $status == 2) { // Survey Draft / Completed
				$existingPeriodsOptions['conditions'][$model->alias . '.status'] = $status;
				$fields[] = $model->alias . '.id';
				$fields[] = $model->alias . '.modified';
				$fields[] = $model->alias . '.created';
				$joinConditions = $model->surveyGetConditions($model);
				$joinConditions[] = $model->alias . '.academic_period_id = AcademicPeriod.id';
				$joinConditions[] = $model->alias . '.survey_template_id = ' . $templateId;
				$joinConditions[] = $model->alias . '.status = ' . $status;

				// Joining the main table to get the modified and created date
				$joins[] = array(
					'table' => $model->useTable,
					'alias' => $model->alias,
					'conditions' => $joinConditions
				);
			}

			$existingPeriods = $model->find('list', $existingPeriodsOptions);
			$conditions = array();
			if (!empty($existingPeriods)) {
				if ($status == 0) {
					$conditions['NOT']['AcademicPeriod.id'] = $existingPeriods;
				} else if ($status == 1 || $status == 2) {
					$conditions['AcademicPeriod.id'] = $existingPeriods;
				}
			}

			$options['fields'] = $fields;
			$options['joins'] = $joins;
			$options['conditions'] = $conditions;
			$options['group'] = array('AcademicPeriod.id');
			$options['order'] = array('SurveyStatus.date_disabled');
			$periods = $SurveyStatus->AcademicPeriod->find('all', $options);
			$templates[$i]['AcademicPeriod'] = $periods;
		}
		
		return $templates;
	}

	public function surveyGetConditions(Model $model) {
		$conditions = array_key_exists('conditions', $this->settings[$model->alias]) ? $this->settings[$model->alias]['conditions'] : array();

		foreach ($conditions as $field => $attr) {
			$value = '';
			if (is_array($attr)) {
				if (array_key_exists('sessionKey', $attr)) {
					$value = CakeSession::read($attr['sessionKey']);
				}
			}
			if(!empty($value)) {
				$conditions[$model->alias.".".$field] = $value;
			}
			unset($conditions[$field]);
		}
		return $conditions;
	}

	public function getSurveyStatusPeriod(Model $model, $academicPeriodId, $surveyStatusId) {
		$model->SurveyStatusPeriod->contain(array('AcademicPeriod', 'SurveyStatus.SurveyTemplate', 'SurveyStatus.AcademicPeriodType'));
		$result = $model->SurveyStatusPeriod->find('first', array(
			'conditions' => array(
				'SurveyStatusPeriod.academic_period_id' => $academicPeriodId,
				'SurveyStatusPeriod.survey_status_id' => $surveyStatusId
			)
		));

		return $result;
	}

	public function getSurveyTemplate(Model $model, $academicPeriodId, $surveyStatusId) {
		$model->SurveyStatusPeriod->contain(array('SurveyStatus.SurveyTemplate'));
		$result = $model->SurveyStatusPeriod->find('first', array(
			'conditions' => array(
				'SurveyStatusPeriod.academic_period_id' => $academicPeriodId,
				'SurveyStatusPeriod.survey_status_id' => $surveyStatusId
			)
		));

		return $result['SurveyStatus']['SurveyTemplate'];
	}

	public function getFormatedSurveyData(Model $model, $id) {
		$model->SurveyTemplate->contain(array('SurveyQuestion', 'SurveyQuestion.SurveyQuestionChoice', 'SurveyQuestion.SurveyTableRow', 'SurveyQuestion.SurveyTableColumn'));
		$result = $model->SurveyTemplate->findById($id);

		$tmp = array();
		if($result) {
			foreach ($result['SurveyQuestion'] as $key => $value) {
				$tmp[$key]['SurveyQuestion'] = $value;
				$tmp[$key]['SurveyQuestionChoice'] = $value['SurveyQuestionChoice'];
				$tmp[$key]['SurveyTableRow'] = $value['SurveyTableRow'];
				$tmp[$key]['SurveyTableColumn'] = $value['SurveyTableColumn'];
				unset($tmp[$key]['SurveyQuestion']['SurveyQuestionChoice']);
				unset($tmp[$key]['SurveyQuestion']['SurveyTableRow']);
				unset($tmp[$key]['SurveyQuestion']['SurveyTableColumn']);
			}
		}

		return $tmp;
	}

	public function getFormatedSurveyDataValues(Model $model, $id) {
		$modelValue = $this->settings[$model->alias]['customfields']['modelValue'];
		$modelCell = $this->settings[$model->alias]['customfields']['modelCell'];

		$tmp = array();
		$model->contain(array($modelValue, $modelCell));
		$result = $model->findById($id);

		foreach ($result[$modelValue] as $key => $value) {
			$tmp[$value['survey_question_id']][] = $value;
		}

		foreach ($result[$modelCell] as $key => $value) {
			$tmp[$value['survey_question_id']][] = $value;
		}

		return $tmp;
	}

	public function prepareSubmitSurveyData(Model $model, $requestData) {
		$modelValue = $this->settings[$model->alias]['customfields']['modelValue'];
		$modelCell = $this->settings[$model->alias]['customfields']['modelCell'];

		$institutionSiteId = $model->Session->read('InstitutionSite.id');
		$surveyStatus = isset($requestData['postFinal']) ? 2 : 1;

		$result[$model->alias] = $requestData[$model->alias];
		$result[$model->alias]['institution_site_id'] = $institutionSiteId;
		$result[$model->alias]['status'] = $surveyStatus;

		$arrFields = array(
			'textbox' => 'text_value',
			'dropdown' => 'int_value',
			//'checkbox' => 'int_value',	//Separate out checbox to handle put back data if save failed
			'textarea' => 'textarea_value',
			'number' => 'int_value'
		);

		$index = 0;
		foreach ($arrFields as $fieldVal => $fieldName) {
			if (!isset($requestData[$modelValue][$fieldVal]))
                continue;

            foreach ($requestData[$modelValue][$fieldVal] as $key => $obj) {
            	$index = $key > $index ? $key : $index;
				$result[$modelValue][$key]['institution_site_id'] = $institutionSiteId;
				$result[$modelValue][$key]['survey_status'] = $surveyStatus;
            	$result[$modelValue][$key]['survey_question_id'] = $key;
            	$result[$modelValue][$key]['type'] = $obj['type'];
				$result[$modelValue][$key]['is_mandatory'] = $obj['is_mandatory'];
				$result[$modelValue][$key]['is_unique'] = $obj['is_unique'];
            	$result[$modelValue][$key][$fieldName] = $obj['value'];
        	}
		}

		if(isset($requestData[$modelValue]['checkbox'])) {
			$index += 1;
			foreach ($requestData[$modelValue]['checkbox'] as $key => $obj) {
				$checkboxCnt = 1;
            	foreach ($obj['value'] as $checkboxKey => $checkboxValue) {
            		$result[$modelValue][$index]['institution_site_id'] = $institutionSiteId;
            		$result[$modelValue][$index]['survey_status'] = $surveyStatus;
            		$result[$modelValue][$index]['survey_question_id'] = $key;
            		$result[$modelValue][$index]['answer_number'] = ++$checkboxCnt;
            		$result[$modelValue][$index]['type'] = $obj['type'];
            		$result[$modelValue][$index]['int_value'] = $checkboxValue;
            		$index++;
            	}
			}
		}

		if (isset($requestData[$modelCell])) {
			foreach ($requestData[$modelCell]['table'] as $key => $obj) {
				foreach ($obj as $key2 => $obj2) {
        			if($obj2['value']) {
        				$result[$modelCell][$index]['institution_site_id'] = $institutionSiteId;
        				$result[$modelCell][$index]['survey_status'] = $surveyStatus;
        				$result[$modelCell][$index]['survey_question_id'] = $obj2['survey_question_id'];
        				$result[$modelCell][$index]['survey_table_row_id'] = $obj2['survey_table_row_id'];
        				$result[$modelCell][$index]['survey_table_column_id'] = $obj2['survey_table_column_id'];
        				$result[$modelCell][$index]['value'] = $obj2['value'];
        				$index++;
        			}
        		}
			}
		}

		return $result;
	}

	public function prepareFormatedDataValues(Model $model, $result) {
		$modelValue = $this->settings[$model->alias]['customfields']['modelValue'];
		$modelCell = $this->settings[$model->alias]['customfields']['modelCell'];

		$tmp = array();

		if(isset($result[$modelValue])) {
			foreach ($result[$modelValue] as $key => $obj) {
				$surveyQuestionId = $obj['survey_question_id'];
				$tmp[$surveyQuestionId][] = $obj;
			}
		}

		if(isset($result[$modelCell])) {
			foreach ($result[$modelCell] as $key => $obj) {
				$surveyQuestionId = $obj['survey_question_id'];
				$tmp[$surveyQuestionId][] = $obj;
			}
		}

		return $tmp;
	}
}

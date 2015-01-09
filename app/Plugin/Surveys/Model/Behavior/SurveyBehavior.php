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
		//pr($this->settings);
	}

	public function getSurveyStatusByModule(Model $model) {
		$moduleName = $this->settings[$model->alias]['module'];
		$moduleId = ClassRegistry::init('Surveys.SurveyModule')->field('id', array('SurveyModule.name' => $moduleName));

		$model->SurveyStatus->contain(array('SurveyTemplate', 'AcademicPeriod'));
		$result = $model->SurveyStatus->find('all', array(
			'conditions' => array(
				'SurveyTemplate.survey_module_id' => $moduleId
			),
			'order' => array('SurveyStatus.date_disabled', 'SurveyStatus.date_enabled')
		));

		return $result;
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

	public function getSurveyDataByStatus(Model $model) {
		$surveyStatus = $this->settings[$model->alias]['status'];
		$conditions = $this->settings[$model->alias]['conditions'];
		
		$options = array();
		$options['conditions'] = array($model->alias.'.status' => $surveyStatus);
		foreach ($conditions as $field => $attr) {
			$value = '';
			if (is_array($attr)) {
				if (array_key_exists('sessionKey', $attr)) {
					$value = CakeSession::read($attr['sessionKey']);
				}
			}
			$options['conditions'][$field] = $value;
		}
		$options['order'] = array('SurveyStatus.date_disabled', 'SurveyStatus.date_enabled');

		$model->contain(array('SurveyTemplate', 'SurveyStatus', 'SurveyStatusPeriod.AcademicPeriod'));
		$result = $model->find('all', $options);

		return $result;
	}

	public function getSurveyById(Model $model, $id) {
		$model->contain(array('SurveyStatus', 'SurveyStatus.SurveyTemplate', 'SurveyStatus.AcademicPeriodType', 'SurveyStatusPeriod', 'SurveyStatusPeriod.AcademicPeriod', 'ModifiedUser', 'CreatedUser'));
		$result = $model->findById($id);

		return $result;
	}

	public function getSurveyTemplateBySurveyId(Model $model, $id) {
		$model->contain(array('SurveyTemplate'));
		$result = $model->findById($id);

		return $result['SurveyTemplate'];
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

	public function prepareSurveyData(Model $model, $requestData) {
		$modelValue = $this->settings[$model->alias]['customfields']['modelValue'];
		$modelCell = $this->settings[$model->alias]['customfields']['modelCell'];

		$institutionSiteId = $model->Session->read('InstitutionSite.id');

		$result[$model->alias] = $requestData[$model->alias];
		$result[$model->alias]['institution_site_id'] = $institutionSiteId;
		$result[$model->alias]['status'] = isset($requestData['postFinal']) ? 2 : 1;

		$arrFields = array('textbox', 'dropdown', 'checkbox', 'textarea', 'number');

		$i = 0;
		foreach ($arrFields as $fieldVal) {
			if (!isset($requestData[$modelValue][$fieldVal]))
                continue;

            foreach ($requestData[$modelValue][$fieldVal] as $key => $val) {
            	if ($fieldVal == "checkbox") {
                	$j = 0;
                	foreach ($val['value'] as $key2 => $val2) {
                		$result[$modelValue][$i]['institution_site_id'] = $institutionSiteId;
                		$result[$modelValue][$i]['survey_question_id'] = $key;
                		$result[$modelValue][$i]['answer_number'] = ++$j;
                		$result[$modelValue][$i]['type'] = $val['type'];
                		$result[$modelValue][$i]['is_mandatory'] = isset($val2['is_mandatory']) ? $val2['is_mandatory'] : 0;
	                	$result[$modelValue][$i]['is_unique'] = isset($val2['is_unique']) ? $val2['is_unique'] : 0;
                		$result[$modelValue][$i]['text_value'] = $val2;
                		$i++;
                	}
                } else {
                	$result[$modelValue][$i]['institution_site_id'] = $institutionSiteId;
                	$result[$modelValue][$i]['survey_question_id'] = $key;
                	$result[$modelValue][$i]['answer_number'] = 1;
                	$result[$modelValue][$i]['type'] = $val['type'];
                	$result[$modelValue][$i]['is_mandatory'] = isset($val['is_mandatory']) ? $val['is_mandatory'] : 0;
                	$result[$modelValue][$i]['is_unique'] = isset($val['is_unique']) ? $val['is_unique'] : 0;

                	switch($fieldVal) {
                		case "number" :
                			$result[$modelValue][$i]['int_value'] = $val['value'];
                			break;
                		case "textarea" :
							$result[$modelValue][$i]['textarea_value'] = $val['value'];
                			break;
                		default:
                			$result[$modelValue][$i]['text_value'] = $val['value'];
                	}
                	$i++;
                }
        	}
		}

		$k = 0;
		if (isset($requestData[$modelCell])) {
			foreach ($requestData[$modelCell]['table'] as $key => $val) {
				foreach ($val as $key2 => $val2) {
        			if($val2['value']) {
        				$result[$modelCell][$k]['institution_site_id'] = $institutionSiteId;
        				$result[$modelCell][$k]['survey_question_id'] = $val2['survey_question_id'];
        				$result[$modelCell][$k]['survey_table_row_id'] = $val2['survey_table_row_id'];
        				$result[$modelCell][$k]['survey_table_column_id'] = $val2['survey_table_column_id'];
        				$result[$modelCell][$k]['value'] = $val2['value'];
        				$k++;
        			}
        		}
			}
		}

		//pr($result);die;
		return $result;
	}
}

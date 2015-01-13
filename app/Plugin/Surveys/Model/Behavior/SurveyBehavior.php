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

	public function getSurveyStatusByModule(Model $model) {
		$moduleName = $this->settings[$model->alias]['module'];
		$moduleId = ClassRegistry::init('Surveys.SurveyModule')->field('id', array('SurveyModule.name' => $moduleName));
		$todayDate = date("Y-m-d");
		$table = $model->useTable;
		$institutionSiteId = $model->Session->read('InstitutionSite.id');

		$conditionsSubQuery['`SurveyTemplate`.`survey_module_id`'] = $moduleId;

		$db = $model->SurveyTemplate->getDataSource();
		$subQuery = $db->buildStatement(
		    array(
		        'fields'     => array('`SurveyTemplate`.`id`'),
		        'table'      => $db->fullTableName($model->SurveyTemplate),
		        'alias'      => 'SurveyTemplate',
		        'limit'      => null,
		        'offset'     => null,
		        'joins'      => array(),
		        'conditions' => $conditionsSubQuery,
		        'order'      => null,
		        'group'      => null
		    ),
		    $model->SurveyTemplate
		);
		$subQuery = '`SurveyStatus`.`survey_template_id` IN (' . $subQuery . ') ';
		$subQueryExpression = $db->expression($subQuery);

		$conditions[] = $subQueryExpression;
		$conditions[] = "SurveyStatus.date_disabled >= '$todayDate'";
		$conditions[] = "NOT EXISTS (SELECT 1 FROM $table 
							WHERE $table.academic_period_id = SurveyStatusPeriod.academic_period_id
							AND $table.survey_status_id = SurveyStatus.id
							AND $table.survey_template_id = SurveyStatus.survey_template_id
							AND $table.institution_site_id = " . $institutionSiteId . ")";

		$options = array();
		$options['conditions'] = $conditions;

		$result = $model->SurveyStatusPeriod->find('all', $options);

		return $model->prepareResultSurveyData($result);
	}

	public function prepareResultSurveyData(Model $model, $result) {
		$tmp = array();

		foreach ($result as $key => $obj) {
			$academicPeriodId = $obj['SurveyStatusPeriod']['academic_period_id'];
			$surveyStatusId = $obj['SurveyStatusPeriod']['survey_status_id'];
			$surveyTemplateId = $obj['SurveyStatus']['survey_template_id'];

			$obj['AcademicPeriod']['SurveyStatusPeriod'] = $obj['SurveyStatusPeriod'];
			$obj['AcademicPeriod']['SurveyStatus'] = $obj['SurveyStatus'];
			if(isset($obj[$model->alias])) {
				$obj['AcademicPeriod'][$model->alias] = $obj[$model->alias];
			}

			if(array_key_exists($surveyTemplateId, $tmp)) {
				$academicPeriodArr = $tmp[$surveyTemplateId]['AcademicPeriod'];
				if(!array_key_exists($academicPeriodId, $academicPeriodArr)) {
					$tmp[$surveyTemplateId]['AcademicPeriod'][$academicPeriodId] = $obj['AcademicPeriod'];
				}
			} else {
				$model->SurveyTemplate->recursive = -1;
				$templateData = $model->SurveyTemplate->findById($surveyTemplateId);
				$tmp[$surveyTemplateId]['SurveyTemplate'] = $templateData['SurveyTemplate'];
				$tmp[$surveyTemplateId]['AcademicPeriod'][$academicPeriodId] = $obj['AcademicPeriod'];
			}
		}

		foreach ($tmp as $key => $row) {
			uasort($tmp[$key]['AcademicPeriod'], array($model, 'sortAcademicPeriod'));
		}
		uasort($tmp, array($model, 'sortSurveyTemplate'));

		return $tmp;
	}

	public function sortSurveyTemplate(Model $model, $a, $b) {
		if ($a['SurveyTemplate']['name'] == $b['SurveyTemplate']['name']) {
	        return 0;
	    }
	    return ($a['SurveyTemplate']['name'] < $b['SurveyTemplate']['name']) ? -1 : 1;
	}

	public function sortAcademicPeriod(Model $model, $a, $b) {
		$aDateDisabled = strtotime($a['SurveyStatus']['date_disabled']);
		$bDateDisabled = strtotime($b['SurveyStatus']['date_disabled']);

		if ($aDateDisabled == $bDateDisabled) {
	        return 0;
	    }
	    return ($aDateDisabled < $bDateDisabled) ? -1 : 1;
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
			if(!empty($value)) {
				$options['conditions'][$model->alias.".".$field] = $value;
			}
		}
		$options['order'] = array('SurveyStatus.date_disabled', 'SurveyStatus.date_enabled');

		$model->contain(array('SurveyStatus', 'AcademicPeriod', 'SurveyStatusPeriod'));
		$result = $model->find('all', $options);

		return $model->prepareResultSurveyData($result);
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

	public function prepareSubmitSurveyData(Model $model, $requestData) {
		$modelValue = $this->settings[$model->alias]['customfields']['modelValue'];
		$modelCell = $this->settings[$model->alias]['customfields']['modelCell'];

		$institutionSiteId = $model->Session->read('InstitutionSite.id');
		$surveyStatus = isset($requestData['postFinal']) ? 2 : 1;

		$result[$model->alias] = $requestData[$model->alias];
		$result[$model->alias]['institution_site_id'] = $institutionSiteId;
		$result[$model->alias]['status'] = $surveyStatus;

		$arrFields = array('textbox', 'dropdown', 'textarea', 'number');

		$i = 0;
		foreach ($arrFields as $fieldVal) {
			if (!isset($requestData[$modelValue][$fieldVal]))
                continue;

            foreach ($requestData[$modelValue][$fieldVal] as $key => $val) {
            	$i = $key > $i ? $key : $i;
				$result[$modelValue][$key]['institution_site_id'] = $institutionSiteId;
				$result[$modelValue][$key]['survey_status'] = $surveyStatus;
            	$result[$modelValue][$key]['survey_question_id'] = $key;
            	$result[$modelValue][$key]['answer_number'] = 1;
            	$result[$modelValue][$key]['type'] = $val['type'];
            	$result[$modelValue][$key]['is_mandatory'] = isset($val['is_mandatory']) ? $val['is_mandatory'] : 0;
            	$result[$modelValue][$key]['is_unique'] = isset($val['is_unique']) ? $val['is_unique'] : 0;

            	switch($fieldVal) {
            		case "number" :
            			$result[$modelValue][$key]['int_value'] = $val['value'];
            			break;
            		case "textarea" :
						$result[$modelValue][$key]['textarea_value'] = $val['value'];
            			break;
            		default:
            			$result[$modelValue][$key]['text_value'] = $val['value'];
            	}
        	}
		}

		if(isset($requestData[$modelValue]['checkbox'])) {
			$i += 1;
			foreach ($requestData[$modelValue]['checkbox'] as $key => $val) {
				$j = 0;
            	foreach ($val['value'] as $key2 => $val2) {
            		$result[$modelValue][$i]['institution_site_id'] = $institutionSiteId;
            		$result[$modelValue][$i]['survey_status'] = $surveyStatus;
            		$result[$modelValue][$i]['survey_question_id'] = $key;
            		$result[$modelValue][$i]['answer_number'] = ++$j;
            		$result[$modelValue][$i]['type'] = $val['type'];
            		$result[$modelValue][$i]['is_mandatory'] = isset($val2['is_mandatory']) ? $val2['is_mandatory'] : 0;
                	$result[$modelValue][$i]['is_unique'] = isset($val2['is_unique']) ? $val2['is_unique'] : 0;
            		$result[$modelValue][$i]['text_value'] = $val2;
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
        				$result[$modelCell][$k]['survey_status'] = $surveyStatus;
        				$result[$modelCell][$k]['survey_question_id'] = $val2['survey_question_id'];
        				$result[$modelCell][$k]['survey_table_row_id'] = $val2['survey_table_row_id'];
        				$result[$modelCell][$k]['survey_table_column_id'] = $val2['survey_table_column_id'];
        				$result[$modelCell][$k]['value'] = $val2['value'];
        				$k++;
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

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

class SurveyStatus extends SurveysAppModel {
	public $belongsTo = array(
		'Surveys.SurveyTemplate',
		'AcademicPeriodLevel',
		'ModifiedUser' => array(
			'fields' => array('ModifiedUser.first_name', 'ModifiedUser.last_name'),
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'fields' => array('CreatedUser.first_name', 'CreatedUser.last_name'),
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);

	public $hasAndBelongsToMany = array(
		'AcademicPeriod' => array(
			'className' => 'AcademicPeriod',
			'joinTable' => 'survey_status_periods',
			'associationForeignKey' => 'academic_period_id',
			'fields' => array('AcademicPeriod.id', 'AcademicPeriod.name', 'AcademicPeriod.order'),
			'order' => array('AcademicPeriod.order')
		)
	);

	public $actsAs = array(
		'DatePicker' => array('date_enabled', 'date_disabled')
	);

	public $validate = array(
		'date_enabled' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select the Date Enabled'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select the Date Enabled'
			)
		),
		'date_disabled' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select the Date Disabled'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select the Date Disabled'
			),
			'ruleCompare' => array(
				'rule' => 'compareDates',
				'message' => 'Date Disabled cannot be earlier than Date Enabled'
			)
		)
	);

	public function compareDates() {
		if(!empty($this->data[$this->alias]['date_disabled'])) {
			$startDate = $this->data[$this->alias]['date_enabled'];
			$startTimestamp = strtotime($startDate);
			$endDate = $this->data[$this->alias]['date_disabled'];
			$endTimestamp = strtotime($endDate);
			return $endTimestamp >= $startTimestamp;
		}
		return true;
	}

	public function getSurveyStatusByModule($selectedModule, $selectedStatus) {
		$todayDate = date('Y-m-d');
		$todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));

		$this->SurveyTemplate->contain();
		$templates = $this->SurveyTemplate->find('list', array(
			'conditions' => array(
				'SurveyTemplate.survey_module_id' => $selectedModule
			)
		));

		$options = array();
		$options['conditions'][] = array(
			'SurveyStatus.survey_template_id' => array_keys($templates),
		);

		if($selectedStatus == 1) {
			$options['conditions'][] = array(
				'SurveyStatus.date_disabled >=' => $todayTimestamp
			);
		} else {
			$options['conditions'][] = array(
				'SurveyStatus.date_disabled <' => $todayTimestamp
			);
		}

		$options['order'] = array(
			'SurveyTemplate.name',
			'SurveyStatus.date_enabled'
		);

		$this->contain('SurveyTemplate');
		$result = $this->find('all', $options);

		return $result;
	}
}

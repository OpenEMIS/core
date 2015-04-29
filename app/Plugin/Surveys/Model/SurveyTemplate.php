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

class SurveyTemplate extends SurveysAppModel {
	public $belongsTo = array(
		'Surveys.SurveyModule',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('ModifiedUser.first_name', 'ModifiedUser.last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('CreatedUser.first_name', 'CreatedUser.last_name'),
			'foreignKey' => 'created_user_id'
		)
	);

	public $hasMany = array(
		'SurveyQuestion' => array(
			'className' => 'Surveys.SurveyQuestion',
			'dependent' => true
		),
		'SurveyStatus' => array(
			'className' => 'Surveys.SurveyStatus',
			'dependent' => true
		)
	);

	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			),
			'unique' => array(
	            'rule' => array('checkUnique', array('name', 'survey_module_id'), false),
	            'message' => 'This name is already exists in the system'
	        )
		),
		'survey_module_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please select a module'
			)
		)
	);

	public function getTemplateListByModule($surveyModuleId) {
		$list = $this->find('list', array(
				'conditions' => array(
					'SurveyTemplate.survey_module_id' => $surveyModuleId
				),
				'order' => array(
					'SurveyTemplate.name'
				)
			)
		);

		return $list;
	}
}

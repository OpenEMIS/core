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

class SurveyAnswerBehavior extends ModelBehavior {
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}

	public function beforeValidate(Model $model, $options=array()) {
		$modelValue = $this->settings[$model->alias]['customfields']['modelValue'];
		$model->validator()->remove('text_value');
		$model->validator()->remove('textarea_value');
		$model->validator()->remove('int_value');

		switch($model->data[$modelValue]['type']) {
			case 2:
				$fieldName = 'text_value';
				break;
			case 5:
				$fieldName = 'textarea_value';
				break;
			case 6:
				$fieldName = 'int_value';
				break;
		}

		if($model->data[$modelValue]['is_mandatory'] == 1) {
			$model->validator()->add($fieldName, 'required', array(
			    'rule' => 'notEmpty',
			    'required' => true,
			    'message' => 'Please enter a value'
			));
		}

		if($model->data[$modelValue]['is_unique'] == 1) {
			$model->validator()->add($fieldName, 'unique', array(
			    'rule' => array('checkUnique', array('institution_site_id', 'survey_question_id', $fieldName), false),
			    'message' => 'Please enter a unique value'
			));
		}

	}
}

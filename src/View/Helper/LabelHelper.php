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

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Inflector;

class LabelHelper extends Helper {
	public $messages = [
		'general' => [
			'add' => 'Add',
			'edit' => 'Edit',
			'delete' => 'Delete',
			'order' => 'Order',
			'visible' => 'Visible',
			'reorder' => 'Reorder',
			'back' => 'Back',
			'list' => 'List',
			'save' => 'Save',
			'cancel' => 'Cancel',
			'option' => 'Option',
			'description' => 'Description',
			'value' => 'Value',
			'modified' => 'Modified on',
			'created' => 'Created on',
			'type' => 'Type',
			'title' => 'Title',
			'activate' => 'Activate',
			'inactivate' => 'Inactivate',
			'name' => 'Name',
			'date' => 'Date',
			'attachments' => 'Attachments',
			'status' => 'Status',
			'yes' => 'Yes',
			'no' => 'No',
			'general' => 'General',
			'label' => 'Label',
			'default' => 'Default',
			'modified_by' => 'Modified by',
			'created_by' => 'Created by',
			'modified_user_id' => 'Modified by',
			'created_user_id' => 'Created by',
			'enabled' => 'Enabled',
			'disabled' => 'Disabled',
			'category' => 'Category',
			'year' => 'Year',
			'details' => 'Details',
			'search' => 'Search',
			'clear' => 'Clear',
			'school_year' => 'School Year',
			'location' => 'Location',
			'grade' => 'Grade',
			'history' => 'History',
			'profile_image' => 'Profile Image',
			'openemisId' =>'OpenEMIS ID',
			'code' => 'Code',
			'action' => 'Action',
			'level' => 'Level',
			'class' => 'Class',
			'comment' => 'Comment',
			'view_details' => 'View Details',
			'compile' => 'Compile',
			'next' => 'Next',
			'previous' => 'Previous',
			'reset' => 'Reset',
			'preview' => 'Preview',
			'international_code' => 'International Code',
			'national_code' => 'National Code',
			'current' => 'Current',
			'past' => 'Past',
			'options' => 'Options',
			'noOptions' => 'There are no options.',
			'noData' => 'No Data',
			'amount' => 'Amount',
			'total' => 'Total',
			'fee' => 'Fee',
			'model' => 'Model',
			'section' => 'Section',
			'gender' => 'Gender',
			'date_of_birth' => 'Date Of Birth'
		],
		'date' => [
			'start' => 'Start Date',
			'end' => 'End Date',
			'from' => 'From',
			'to' => 'To'
		],
		'gender' => [
			'm' => 'Male',
			'f' => 'Female'
		],
		'password'=> [
			'oldPassword' => 'Current Password',
			'retypePassword' => 'Retype New Password',
		],
		'fileUpload' => [
			'single' => '*File size should not be larger than 2MB.',
			'multi' => '*Maximum 5 files are permitted on single upload. Each file size should not be larger than 2MB.',
		],
		'InstitutionSiteStaff' => [
			'staff_status_id' => 'Status',
			'staff_type_id' => 'Type',
			'institution_site_position_id' => 'Position',
			'total_fte' => 'Total FTE',
			'fte' => 'FTE'
		],
		'InstitutionSitePositions' => [
			'title' => 'Positions',
			'position_no' => 'Position Name',
			'status' => 'Status',
			'type' => 'Type',
			'staff_position_title_id' => 'Title',
			'staff_position_grade_id' => 'Grade'
		],
		'InstitutionSiteSections' => [
			'security_user_id' => 'Home Room Teacher',
			'single_grade_field' => 'Sections',
			'multi_grade_field' => 'Education Grades',
		],
		'InstitutionSiteShifts' => [
			'location_institution_site_id' => 'Location'
		],
		'CustomGroups' => [
			'custom_modules' => 'Module'
		],
		'CustomFields' => [
			'field_type' => 'Type',
			'is_mandatory' => 'Mandatory',
			'is_unique' => 'Unique'
		],
		'CustomFieldOptions' => [
			'is_default' => 'Default'
		],
		'SurveyTemplates' => [
			'survey_module_id' => 'Module'
		],
		'SurveyQuestions' => [
			'survey_template_id' => 'Survey Template'
		],
		'SurveyStatuses' => [
			'survey_template_id' => 'Survey Template'
		],
		'RubricSections' => [
			'rubric_template_id' => 'Rubric Template'
		],
		'RubricCriterias' => [
			'rubric_section_id' => 'Rubric Section',
			'criterias' => 'Criterias'
		],
		'RubricTemplateOptions' => [
			'rubric_template_id' => 'Rubric Template',
			'weighting' => 'Weighting'
		],
		'Workflows' => [
			'workflow_model_id' => 'Form'
		],
		'WorkflowActions' => [
			'next_step' => 'Next Step',
			'comment_required' => 'Comment Required'
		]
	];

	public function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
	
	public function get($code) {
		$index = explode('.', $code);
		$message = $this->messages;
		foreach($index as $i) {
			if(isset($message[$i])) {
				$message = $message[$i];
			} else {
				$message = false;
				break;
			}
		}
		return !is_bool($message) ? __($message) : $message;
	}
	
	public function getLabel($model, $key, $attr) {
		$labelKey = $model;
		if (array_key_exists('labelKey', $attr)) {
			$labelKey = $attr['labelKey'];
		}
		$code = $labelKey .'.'. $key;
		$label = $this->get($code);
		
		if($label === false) {
			$label = __(Inflector::humanize($key));

			if ($this->endsWith($label, ' Id')) {
				$label = str_replace(' Id', '', $label);
			}
		}
		return $label;
	}
}

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

App::uses('AppHelper', 'View/Helper');

class LabelHelper extends AppHelper {
	public $messages = array(
		'general' => array(
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
			'language' => array(
				'eng' => 'English',
				'chi' => 'Chinese',
				'rus' => 'Russian',
				'spa' => 'Spanish',
				'fre' => 'French',
				'ara' => 'Arabic',
			),
			'compile' => 'Compile',
			'next' => 'Next',
			'previous' => 'Previous',
			'reset' => 'Reset'
		),
		'gender' => array(
			'm' => 'Male',
			'f' => 'Female'
		),
		'password'=> array(
			'oldPassword' => 'Current Password',
			'retypePassword' => 'Retype New Password',
		),
		'fileUpload' => array(
			'single' => '*File size should not be larger than 2MB.',
			'multi' => '*Maximum 5 files are permitted on single upload. Each file size should not be larger than 2MB.',
		),
		'wizard' => array(
			'previous' => 'Previous',
			'next' => 'Next',
			'finish' => 'Finish',
			'skip' => 'Skip',
			'addmore' => 'Add More'
		),
		'Area' => array(
			'select' => '-- Select Area --'
		),
		'AreaLevel' => array(
			'name' => 'Area Level'
		),
		'AreaEducationLevel' => array(
			'name' => 'Area Level'
		),
		'EducationSystem' => array(
			'name' => 'Education System'
		),
		'EducationLevel' => array(
			'education_level_isced_id' => 'ISCED'
		),
		'EducationCycle' => array(
			'admission_age' => 'Admission Age',
			'education_level_id' => 'Education Level'
		),
		'EducationProgramme' => array(
			'education_cycle_id' => 'Education Cycle',
			'education_field_of_study_id' => 'Field of Study',
			'education_certification_id' => 'Certification'
		),
		'EducationProgramme' => array(
			'name' => 'Education Programme'
		),
		'EducationGrade' => array(
			'name' => 'Education Grade'
		),
		'EducationSubject' => array(
			'name' => 'Subject',
			'code' => 'Subject Code'
		),
		'EducationGradeSubject' => array(
			'hours_required' => 'Hours Required'
		),
		'EducationFieldOfStudy' => array(
			'education_programme_orientation_id' => 'Programme Orientation'
		),
		'InstitutionSite' => array(
			'institution_site_provider_id' => 'Provider',
			'institution_site_sector_id' => 'Sector',
			'institution_site_type_id' => 'Type',
			'institution_site_ownership_id' => 'Ownership',
			'institution_site_gender_id' => 'Gender',
			'institution_site_status_id' => 'Status',
			'institution_site_locality_id' => 'Locality',
			'name' => 'Institution',
			'programme' => 'Programme',
			'id_name' => 'ID / Name'
		),
		'InstitutionSiteClass' => array(
			'no_of_seats' => 'Seats',
			'no_of_shifts' => 'Shifts',
			'shift' => 'Shift',
			'seats' => 'Seats'
		),
		'InstitutionSiteCustomField' => array(
			'type' => 'Field Type',
			'institution_site_type_id' => 'Institution Type'
		),
		'InstitutionSiteCustomFieldOption' => array(
			'institution_site_custom_field_id' => 'Custom Field'
		),
		'CensusCustomField' => array(
			'type' => 'Field Type',
			'institution_site_type_id' => 'Institution Type'
		),
		'CensusCustomFieldOption' => array(
			'census_custom_field_id' => 'Custom Field'
		),
		'BankBranch' => array(
			'bank_id' => 'Bank',
			'name' => 'Branch'
		),
		'InfrastructureMaterial' => array(
			'infrastructure_category_id' => 'Category'
		),
		'InfrastructureStatus' => array(
			'infrastructure_category_id' => 'Category'
		),
		'FinanceType' => array(
			'finance_nature_id' => 'Nature'
		),
		'ContactType' => array(
			'contact_option_id' => 'Contact Option'
		),
		'SchoolYear' => array(
			'name' => 'School Year'
		),
		'Country' => array(
			'name' => 'Country'
		),
		'HealthRelationships' => array(
			'name' => 'Relationship'
		),
		'HealthCondition' => array(
			'name' => 'Condition'
		),
		'HealthImmunization' => array(
			'name' => 'Immunization'
		),
		'HealthMedication' => array(
			'start_date' => 'Commenced Date',
			'end_date' => 'Ended Date',
		),
		'Config' => array(
			'name' => 'System Configurations',
			'host'=>'LDAP Server',
			'port'=>'Port',
			'version'=>'Version',
			'base_dn'=>'Base DN',
			'file' => 'File',
			'file_type' => 'File Type',
			'default' => 'Default',
			'uploaded_on' => 'Uploaded On'
		),
		'Identities' => array(
			'issue_location' => 'Issuer'
		),
		'QualificationLevel' => array(
			'name' => 'Level'
		),
		'QualificationInstitution' => array(
			'name' => 'Institution'
		),
		'QualificationSpecialisation' => array(
			'name' => 'Major/Specialisation'
		),
		'StaffQualification' => array(
			'qualification_institution_country' => 'Institution Country',
			'gpa' => 'Grade/Score',
			'file_name' => 'Attachment'
		),
		'Database' => array(
			'backup' => 'Backup',
			'restore' => 'Restore'
		),
		'DataProcessing' => array(
			'process' => 'Processes',
			'export' => 'Export',
			'generate' => 'Generate',
			'custom_indicators' => 'Custom Indicators'
		),
		'StaffLeave' => array(
			'date_from' => 'First Day',
			'date_to' => 'Last Day',
		),
		'StaffTraining' => array(
			'course_title' => 'Course Title',
			'description' => 'Course Description',
			'code' => 'Course Code',
			'objective' => 'Course Goal / Objectives',
			'provider' => 'Provider',
			'credit_hours' => 'Credits',
			'completed' => 'Completed',
			'requirement' => 'Training Requirement',
			'inactivate' => 'Inactivate',
			'need_category' => 'Need Category',
			'need_type' => 'Need Type'
		),
		'StaffTrainingSelfStudy' => array(
			'achievement_type' => 'Achievement Type'
		),
		'Shift' => array(
			'name' => 'Shift Name'
		),
		'Position' => array(
			'teaching' => 'Teaching',
			'number' => 'Number'
		),
		'InstitutionSiteStudentAbsence' => array(
			'reason' => 'Reason',
			'first_date_absent' => 'First Date Absent',
			'full_day_absent' => 'Full Day Absent',
			'last_date_absent' => 'Last Date Absent',
			'start_time_absent' => 'Start Time Absent',
			'end_time_absent' => 'End Time Absent'
		),
		'InstitutionSiteStaffAbsence' => array(
			'reason' => 'Reason',
			'first_date_absent' => 'First Date Absent',
			'full_day_absent' => 'Full Day Absent',
			'last_date_absent' => 'Last Date Absent',
			'start_time_absent' => 'Start Time Absent',
			'end_time_absent' => 'End Time Absent'
		),
		'Quality' => array(
			'add_section_header' => 'Add Section Header',
			'header' => 'Header',
			'section_header' => 'Section Header',
			'view_rubric' => 'View Rubric'
		),
		'ReportInHtml' => array(
			'no_data' => 'There is no data to be displayed.',
			'failed_open_file' => 'Error. Failed to open file.'
		),
		'StudentCustomFieldOption' => array(
			'student_custom_field_id' => 'Custom Field'
		),
		'StaffCustomFieldOption' => array(
			'staff_custom_field_id' => 'Custom Field'
		),
		'SecurityRole' => array(
			'systemDefined' => 'System Defined Roles',
			'userDefined' => 'User Defined Roles',
			'notEditable' => 'Not Editable',
			'permissions' => 'Permissions',
			'name' => 'Role',
			'security_group_id' => 'Group'
		),
		'datawarehouse' => array(
			'indicator' => 'Indicator',
			'unit' => 'Unit',
			'dimension' => 'Dimension',
			'time' => 'Time',
			'area' => 'Area',
			'source' => 'Source',
			'review' => 'Review'
		)
	);
	
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
	
	public function getLabel($model, $obj) {
		$field = $obj['field'];
		$code = $model . '.' . $obj['field'];
		
		if(isset($obj['labelKey'])) {
			$code = $obj['labelKey'];
		} else if($field==='modified' || $field==='created') {
			$code = 'general.'.$obj['field'];
		}
		
		$label = $this->get($code);
		if($label===false) {
			$label = Inflector::humanize($obj['field']);
		}
		return $label;
	}
}

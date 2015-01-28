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
			'export' => 'Export',
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
			'event' => 'Event',
			'system' => 'System',
			'method' => 'Method',
			'section' => 'Section',
			'sections' => 'Sections',
			'code' => 'Code',
			'type' => 'Type',
			'size' => 'Size',
			'classes' => 'Classes',
			'academic_period' => 'Academic Period',
			'subject' => 'Subject',
			'teacher' => 'Teacher',
			'student' => 'Student',
			'date_of_birth' => 'Date of Birth',
			'sex' => 'Sex',
			'male_students' => 'Male Students',
			'female_students' => 'Female Students'
		),
		'date' => array(
			'start' => 'Start Date',
			'end' => 'End Date',
			'from' => 'From',
			'to' => 'To'
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
			'name' => 'Area',
			'area_level_id' => 'Area Level',
			'select' => '-- Select Area --'
		),
		'AreaLevel' => array(
			'name' => 'Area Level'
		),
		'AreaEducation' => array(
			'area_education_level_id' => 'Area Education Level'
		),
		'AreaEducationLevel' => array(
			'name' => 'Area Level'
		),
		'EducationSystem' => array(
			'title' => 'Education Systems',
			'name' => 'Education System'
		),
		'EducationLevel' => array(
			'title' => 'Education Levels',
			'education_level_isced_id' => 'ISCED',
			'education_system_id' => 'Education System'
		),
		'EducationCycle' => array(
			'title' => 'Education Cycles',
			'admission_age' => 'Admission Age',
			'education_level_id' => 'Education Level'
		),
		'EducationProgramme' => array(
			'title' => 'Education Programmes',
			'name' => 'Education Programme',
			'education_cycle_id' => 'Education Cycle',
			'education_field_of_study_id' => 'Field of Study',
			'education_certification_id' => 'Certification'
		),
		'EducationGrade' => array(
			'title' => 'Education Grades',
			'name' => 'Education Grade',
			'education_programme_id' => 'Education Programme'
		),
		'EducationSubject' => array(
			'title' => 'Education Subjects',
			'name' => 'Subject',
			'code' => 'Subject Code'
		),
		'EducationGradeSubject' => array(
			'title' => 'Education Grades - Subjects',
			'hours_required' => 'Hours Required'
		),
		'EducationCertification' => array(
			'title' => 'Education Certifications'
		),
		'EducationFieldOfStudy' => array(
			'title' => 'Field of Study',
			'education_programme_orientation_id' => 'Programme Orientation'
		),
		'EducationProgrammeOrientation' => array(
			'title' => 'Programme Orientations'
		),
		'Institution' => array(
			'name' => 'Institution'
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
			'institution_site_id' => 'Institution',
			'programme' => 'Programme',
			'id_name' => 'ID / Name'
		),
		'InstitutionSiteProgramme' => array(
			'title' => 'Programmes'
		),
		'InstitutionSiteClass' => array(
			'no_of_seats' => 'Seats',
			'no_of_shifts' => 'Shifts',
			'shift' => 'Shift',
			'seats' => 'Seats',
			'name' => 'Class Name'
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
		'FinanceCategory' => array(
			'finance_type_id' => 'Type'
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
		'HealthRelationship' => array(
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
			'issue_location' => 'Issuer',
			'identity' => 'Identity'
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
			'custom_indicators' => 'Custom Indicators',
			'processing' => 'Processing'
		),
		'Student' => array(
			'identification_no' => 'OpenEMIS ID',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'add_existing' => 'Add existing Student'
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
			'title' => 'Positions',
			'name' => 'Position',
			'teaching' => 'Teaching',
			'number' => 'Number',
			'institution_site_position_id' => 'Position'
		),
		'InstitutionSiteStudent' => array(
			'student_status_id' => 'Status',
			'education_programme_id' => 'Programme'
		),
		'InstitutionSiteStaff' => array(
			'staff_status_id' => 'Status',
			'staff_type_id' => 'Type',
			'institution_site_position_id' => 'Position'
		),
		'InstitutionSitePosition' => array(
			'title' => 'Positions',
			'position_no' => 'Position No',
			'status' => 'Status',
			'type' => 'Type',
			'staff_position_title_id' => 'Title',
			'staff_position_grade_id' => 'Grade'
		),
		'InstitutionSiteStudentAbsence' => array(
			'title' => 'Absence - Students',
			'academic_period_id' => 'Academic Period',
			'institution_site_class_id' => 'Class',
			'institution_site_section_id' => 'Section',
			'student_id' => 'Student',
			'absence_type' => 'Type',
			'student_absence_reason_id' => 'Reason',
			'select_section' => 'Select Section'
		),
		'InstitutionSiteStaffAbsence' => array(
			'title' => 'Absence - Staff',
			'reason' => 'Reason',
			'first_date_absent' => 'First Date Absent',
			'full_day_absent' => 'Full Day Absent',
			'last_date_absent' => 'Last Date Absent',
			'start_time_absent' => 'Start Time Absent',
			'end_time_absent' => 'End Time Absent',
			'academic_period_id' => 'Academic Period',
			'staff_id' => 'Staff',
			'absence_type' => 'Type',
			'staff_absence_reason_id' => 'Reason'
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
		'SecurityGroup' => array(
			'SecurityGroupArea' => 'Areas',
			'SecurityGroupInstitutionSite' => 'Institutions',
			'SecurityGroupUser' => 'Users'
		),
		'SecurityRole' => array(
			'systemDefined' => 'System Defined Roles',
			'userDefined' => 'User Defined Roles',
			'notEditable' => 'Not Editable',
			'permissions' => 'Permissions',
			'name' => 'Role',
			'security_group_id' => 'Group'
		),
		'SecurityUser' => array(
			'name' => 'User'
		),
		
		'InstitutionSiteFee' => array(
			'academic_period_id' => 'Academic Period',
			'education_grade_id' => 'Grade',
			'InstitutionSiteFeeType' => 'Fee Types'
		),
		
		'StudentFee' => array(
			'title' => 'Fees',
			'programme' => 'Programme',
			'grade' => 'Grade',
			'fees' => 'Fees',
			'paid' => 'Paid',
			'outstanding' => 'Outstanding',
			'no_student' => 'No Student associated in the selected Education Grade and Academic Period.',
			'no_payment' => 'No Payment Records.',
			'no_fees' => 'No Fee Records.',
			'created' => 'Created'
		),
		'CensusGrid' => array(
			'x_title' => 'Table Header',
			'x_categories' => 'Columns',
			'y_categories' => 'Rows',
			'update_preview' => 'Update Preview'
		),
		'Translation' => array(
			'eng' => 'English',
			'chi' => 'Chinese',
			'rus' => 'Russian',
			'spa' => 'Spanish',
			'fre' => 'French',
			'ara' => 'Arabic'
		),
		'Autocomplete' => array(
			'no_result' => 'No records matched.'
		),
		'Datawarehouse' => array(
			'indicator' => 'Indicator',
			'unit' => 'Unit',
			'module' => 'Module',
			'function' => 'Function',
			'dimensions' => 'Dimensions',
			'classification' => 'Classification'
		),
		'Alert' => array(
			'title' => 'Alerts',
			'threshold' => 'Threshold',
			'roles' => 'Destination Roles',
			'start_date' => 'Start Date',
			'end_date' => 'End Date',
			'interval' => 'Interval'
		),
		'StudentBehaviour' => array(
			'title' => 'Behaviour - Students',
			'name' => 'Title',
			'student_behaviour_category_id' => 'Category',
			'date_of_behaviour' => 'Date',
			'time_of_behaviour' => 'Time'
		),
		'StaffBehaviour' => array(
			'title' => 'Behaviour - Staff',
			'staff_behaviour_category_id' => 'Category',
			'date_of_behaviour' => 'Date',
			'time_of_behaviour' => 'Time'
		),
		'Infrastructure' => array(
			'parent_level' => 'Parent Category'
		),
		'InstitutionSiteInfrastructure' => array(
			'infrastructure_type_id' => 'Type',
			'year_acquired' => 'Year Acquired',
			'year_disposed' => 'Year Disposed',
			'infrastructure_ownership_id' => 'Ownership',
			'infrastructure_condition_id' => 'Condition'
		),
		'InstitutionSiteSurveyNew' => array(
			'title' => 'New'
		),
		'InstitutionSiteSurveyDraft' => array(
			'title' => 'Draft'
		),
		'InstitutionSiteSurveyCompleted' => array(
			'title' => 'Completed'
		),
		'AcademicPeriod' => array(
			'name' => 'Academic Period',
			'academic_period_level_id' => 'Academic Period Level',
			'select' => '-- Select Academic Period --'
		),
		'AcademicPeriodLevel' => array(
			'name' => 'Academic Period Level'
		),
		'StaffPositionTitle' => array(
			'name' => 'Position Title'
		),
		'StaffPositionGrade' => array(
			'name' => 'Position Grade'
		),
		'InstitutionSiteSection' => array(
			'staff_id' => 'Home Room Teacher'
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
	
	public function getLabel2($model, $key, $attr) {
		$labelKey = $model;
		if (array_key_exists('labelKey', $attr)) {
			$labelKey = $attr['labelKey'];
		}
		$code = $labelKey .'.'. $key;
		$label = $this->get($code);
		
		if($label === false) {
			$label = __(Inflector::humanize($key));
		}
		return $label;
	}
	
	public function getLabel($model, $obj) {
		$field = $obj['field'];
		$code = $model . '.' . $obj['field'];
		
		if(isset($obj['label'])){
			if(is_array($obj['label'])){
				$code = $obj['label'][0];
				$arr = array_splice($obj['label'], 1);
				$label = $this->get($code);
				$label = vsprintf($label, $arr);
			}else{
				$label = Inflector::humanize($obj['label']);
			}
			return $label;
		}
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

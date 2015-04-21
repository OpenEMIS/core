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
			'reject' => 'Reject',
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
			'grades' => 'Grades',
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
			'female_students' => 'Female Students',
			'levels' => 'Levels',
			'types' => 'Types',
			'custom_fields' => 'Custom Fields',
			'import' => 'Import',
			'proceed' => 'Proceed',
			'template' => 'Template',
			'InstitutionSite' => 'Institution',
			'download_template' => 'Download Template',
			'format_supported' => 'Format Supported',
			'no_records' => 'There are no records.',
			'editable' => 'Editable'
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
		'Activity' => array(
			'field' => 'Field',
			'old_value' => 'Old value',
			'new_value' => 'New value',
			'created_user_id' => 'Modified by',
			'created' => 'Modified on'
		),
		'Area' => array(
			'name' => 'Area',
			'area_level_id' => 'Area Level (Education)',
			'select' => '-- Select Area --'
		),
		'AreaLevel' => array(
			'name' => 'Area Level (Education)'
		),
		'AreaAdministrative' => array(
			'area_administrative_level_id' => 'Area Level (Administrative)'
		),
		'AreaAdministrativeLevel' => array(
			'name' => 'Area Level (Administrative)'
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
			'education_programme_id' => 'Education Programme',
			'add_subject' => 'Add Subject'
		),
		'EducationSubject' => array(
			'title' => 'Education Subjects',
			'name' => 'Subject Name',
			'code' => 'Subject Code',
			'number_of_subjects' => 'Number Of Subjects',
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
			'module' => 'Institution',
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
			'id_name' => 'ID / Name',
			'alternative_name' => 'Alternative Name',
			'postal_code' => 'Postal Code',
			'contact_person' => 'Contact Person',
			'date_opened' => 'Date Opened',
			'year_opened' => 'Year Opened',
			'date_closed' => 'Date Closed',
			'year_closed' => 'Year Closed',
			'area_id' => 'Area',
			'area_administrative_id' => 'Area Administrative'
		),
		'InstitutionSiteProgramme' => array(
			'title' => 'Programmes',
			'education_level_id' => 'Education Levels',
			'education_programme_id' => 'Education Programmes'
		),
		'InstitutionSiteClass' => array(
			'name' => 'Class Name',
			'no_of_seats' => 'No of Seats',
			'seats' => 'Seats',
			'education_subject_id' => 'Education Subject',
			'academic_period_id' => 'Academic Period',
			'add_staff' => 'Add Staff',
			'add_student' => 'Add Student',
			'add_all_student' => 'Add All Students'
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
			'module' => 'Student',
			'openemis_no' => 'OpenEMIS ID',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'add_existing' => 'Add existing Student',
			'third_name' => 'Third Name',
			'date_of_birth' => 'Birth Date',
			'middle_name' => 'Middle Name',
			'gender_id' => 'Gender',
			'address_area_id' => 'Address Area',
			'birthplace_area_id' => 'Birth Place Area'
		),
		'Staff' => array(
			'module' => 'Staff',
			'gender_id' => 'Gender',
			'address_area_id' => 'Address Area',
			'birthplace_area_id' => 'Birth Place Area'
		),
		'StaffLeave' => array(
			'title' => 'Leave',
			'staff_leave_type_id' => 'Staff Leave Type',
			'leave_status_id' => 'Leave Status',
			'date_from' => 'First Day',
			'date_to' => 'Last Day'
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
			'academic_period_id' => 'Academic Period',
			'education_programme_id' => 'Programme',
			'education_grade_id' => 'Grade',
			'institution_site_section_id' => 'Section',
			'student_category_id' => 'Category',
			'select_grade' => 'Select Grade',
			'select_section' => 'Select Section'
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
			'SecurityGroupArea' => 'Areas (Education)',
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
			'title' => 'User',
			'name' => 'User',
			'SecurityGroupUser' => 'Groups',
			'UserContact' => 'Contacts',
			'username' => 'User Name', 
			'openemis_no' => 'Openemis ID', 
			'first_name' => 'First Name', 
			'middle_name' => 'Middle Name', 
			'third_name' => 'Third Name', 
			'last_name' => 'Last Name', 
			'preferred_name' => 'Preferred Name', 
			'address' => 'Address', 
			'postal_code' => 'Postal Code', 
			'address_area_id' => 'Address Area', 
			'birthplace_area_id' => 'Birthplace Area', 
			'gender_id' => 'Gender', 
			'date_of_birth' => 'Date Of Birth', 
			'date_of_death' => 'Date Of Death', 
			'status' => 'Status'
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
			'no_result' => 'No existing record.',
			'has_result' => 'No existing record?'
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
			'name' => 'Section Name',
			'staff_id' => 'Home Room Teacher',
			'all_grades_select' => 'All Grades',
			'single_grade' => 'Single Grade',
			'multi_grades' => 'Multi Grades',
			'education_grade_id' => 'Education Grade',
			'institution_site_shift_id' => 'Shift',
			'academic_period_id' => 'Academic Period',
			'add_student' => 'Add Student'
		),
		'ReportProgress' => array(
			'error' => 'Please contact the administrator for assistance.'
		),
		'StudentContact' => array(
			'title' => 'Contacts',
			'contact_type_id' => 'Description',
			'contact_option_id' => 'Type'
		),
		'StaffContact' => array(
			'title' => 'Contacts',
			'contact_type_id' => 'Description',
			'contact_option_id' => 'Type'
		),
		'StudentIdentity' => array(
			'title' => 'Identities',
			'number' => 'Number',
			'issue_date' => 'Issue Date',
			'expiry_date' => 'Expiry Date',
			'issue_location' => 'Issuer',
			'identity_type_id' => 'Identity Type'
		),
		'StaffIdentity' => array(
			'title' => 'Identities',
			'number' => 'Number',
			'issue_date' => 'Issue Date',
			'expiry_date' => 'Expiry Date',
			'issue_location' => 'Issuer',
			'identity_type_id' => 'Identity Type'
		),
		'StudentNationality' => array(
			'title' => 'Nationalities',
			'comments' => 'Comments',
			'country_id' => 'Country'
		),
		'StaffNationality' => array(
			'title' => 'Nationalities',
			'comments' => 'Comments',
			'country_id' => 'Country'
		),
		'StudentLanguage' => array(
			'title' => 'Languages',
			'evaluation_date' => 'Evaluation Date',
			'language_id' => 'Language',
			'listening' => 'Listening',
			'speaking' => 'Speaking',
			'reading' => 'Reading',
			'writing' => 'Writing'
		),
		'StaffLanguage' => array(
			'title' => 'Languages',
			'evaluation_date' => 'Evaluation Date',
			'language_id' => 'Language',
			'listening' => 'Listening',
			'speaking' => 'Speaking',
			'reading' => 'Reading',
			'writing' => 'Writing'
		),
		'StudentComment' => array(
			'title' => 'Title',
			'comment_date' => 'Date',
			'comment' => 'Comment'
		),
		'StaffComment' => array(
			'title' => 'Title',
			'comment_date' => 'Date',
			'comment' => 'Comment'
		),
		'StudentSpecialNeed' => array(
			'title' => 'Special Needs',
			'special_need_type_id' => 'Type',
			'special_need_date' => 'Date',
			'comment' => 'Comment',
		),
		'StaffSpecialNeed' => array(
			'title' => 'Special Needs',
			'special_need_type_id' => 'Type',
			'special_need_date' => 'Date',
			'comment' => 'Comment',
		),
		'StudentAward' => array(
			'title' => 'Awards',
			'issue_date' => 'Issue Date',
			'award' => 'Name',
			'issuer' => 'Issuer',
			'comment' => 'Comment',
		),
		'StaffAward' => array(
			'title' => 'Awards',
			'issue_date' => 'Issue Date',
			'award' => 'Name',
			'issuer' => 'Issuer',
			'comment' => 'Comment',
		),
		'StaffSalary' => array(
			'title' => 'Salary',
			'additions' => 'Additions',
			'deductions' => 'Deductions',
			'gross' => 'Gross',
			'net' => 'Net',
			'salary_date' => 'Date'
		),
		'SecurityUserLogin' => array(
			'title' => 'User',
			'password' => 'Current Password',
			'newPassword' => 'New Password',
			'retypeNewPassword' => 'Retype New Password'
		),
		'FieldOption' => array(
			'module' => 'Module',
			'records' => 'No of records',
			'apply' => 'Apply To'
		),
		'WorkflowStep' => array(
			'workflow_id' => 'Workflow',
			'security_roles' => 'Security Roles',
			'actions' => 'Actions',
			'next_step' => 'Next Step',
			'select_step' => 'Select Step'
		),
		'WfWorkflow' => array(
			'workflow_model_id' => 'Form',
		),
		'WfWorkflowStep' => array(
			'workflow_id' => 'Workflow'
		),
		'StudentStatus' => array(
			'name' => 'Status'
		),
		'StudentCategory' => array(
			'name' => 'Category'
		),
		'Notice' => array(
			'title' => 'Notices',	
			'created' => 'Date'
		),
		'Import' => array(
			'saving_failed' => 'Failed to save to database',
			'invalid_code' => 'Invalid Code',
			'validation_failed' => 'Validation Failed.',
			'total_rows' => 'Total Rows',
			'rows_imported' => 'Rows Imported',
			'rows_updated' => 'Rows Updated',
			'rows_failed' => 'Rows Failed',
			'recommended_max_records' => 'Recommended Maximum Records'
		),
		'RubricTemplate' => array(
			'name' => 'Rubric Template',
			'weighting_type' => 'Weighting Type',
			'pass_mark' => 'Pass Mark',
			'options' => 'Options'
		),
		'RubricTemplateGrade' => array(
			'select_grade' => 'Select Grade'
		),
		'RubricTemplateOption' => array(
			'rubric_template_id' => 'Rubric Template',
			'weighting' => 'Weighting',
			'color' => 'Color'
		),
		'RubricSection' => array(
			'name' => 'Rubric Section',
			'rubric_template_id' => 'Rubric Template',
			'no_of_criterias' => 'No of Criterias'
		),
		'RubricCriteria' => array(
			'rubric_section_id' => 'Section',
			'type' => 'Type',
			'criterias' => 'Options'
		),
		'QualityStatus' => array(
			'rubric_template_id' => 'Rubric Template',
			'academic_period_level_id' => 'Academic Period Level',
			'security_roles' => 'Security Roles',
			'education_programmes' => 'Education Programmes',
			'date_enabled' => 'Date Enabled',
			'date_disabled' => 'Date Disabled'
		),
		'InstitutionSiteQualityVisit' => array(
			'academic_period_id' => 'Academic Period',
			'education_grade_id' => 'Grade',
			'institution_site_section_id' => 'Section',
			'institution_site_class_id' => 'Class',
			'staff_id' => 'Staff',
			'quality_visit_type_id' => 'Visit Type'
		),
		'InstitutionSiteQualityRubric' => array(
			'status' => 'Status',
			'rubric_template_id' => 'Rubric Template',
			'academic_period_id' => 'Academic Period',
			'education_programme_id' => 'Programme',
			'education_grade_id' => 'Grade',
			'institution_site_section_id' => 'Section',
			'institution_site_class_id' => 'Class',
			'staff_id' => 'Staff',
			'rubric_sections' => 'Rubric Sections'
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

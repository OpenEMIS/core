<?php
namespace App\Model\Traits;

trait MessagesTrait {
	public $messages = [
		'Attachments' => [
			'date_on_file' => 'Date On File',
		],
		'Assessments' => [
			'noGrades' => 'No Available Grades',
			'noGradingTypes' => 'You need to configure Grading Types first.'
		],
		'CustomGroups' => [
			'custom_modules' => 'Module'
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
		'general' => [
			'notExists' => 'The record does not exist.',
			'notEditable' => 'This record is not editable',
			'exists' => 'The record is exists in the system.',
			'noData' => 'There are no records.',
			'select' => [
				'noOptions' => 'No configured options'
			],
			'error' => 'An unexpected error has been encounted. Please contact the administrator for assistance.',
			'add' => [
				'success' => 'The record has been added successfully.',
				'failed' => 'The record is not added due to errors encountered.',
				'label' => 'Add',
			],
			'edit' => [
				'success' => 'The record has been updated successfully.',
				'failed' => 'The record is not updated due to errors encountered.',
				'label' => 'Edit',
			],
			'delete' => [
				'success' => 'The record has been deleted successfully.',
				'failed' => 'The record is not deleted due to errors encountered.',
				'label' => 'Delete',
			],
			'view' => [
				'label' => 'View',
			],
			'duplicate' => [
				'success' => 'The record has been duplicated successfully.',
				'failed' => 'The record is not duplicated due to errors encountered.',
			],
			'invalidDate' => 'You have entered an invalid date.',
			'invalidUrl' => 'You have entered an invalid url.',
			'notSelected' => 'No Record has been selected/saved.',
			'order' => 'Order',
			'visible' => 'Visible',
			'name' => 'Name',
			'description' => 'Description',
			'default' => 'Default',
			'reject' => 'Reject',
			'noSections' => 'No Sections',
			'noClasses' => 'No Classes',
			'noStaff' => 'No Staff',
			'type' => 'Type',
			'amount' => 'Amount'
		],
		'fileUpload' => [
			'single' => '*File size should not be larger than 2MB.',
			'multi' => '*Maximum 5 files are permitted on single upload. Each file size should not be larger than 2MB.',
		],
		'InfrastructureTypes' => [
			'infrastructure_level_id' => 'Level Name'
		],
		'InfrastructureCustomFields' => [
			'infrastructure_level_id' => 'Level Name'
		],
		'Institutions' => [
			'date_opened' => 'Date Opened',
			'date_closed' => 'Date Closed',
		],
		'InstitutionSiteStaff' => [
			'start_date' => 'Start Date',
			'fte' => 'FTE',
			'total_fte' => 'Total FTE',
		],
		'InstitutionSitePositions' => [
			'current_staff_list' => 'Current Staff List',
			'past_staff_list' => 'Past Staff List',
		],
		'InstitutionSiteProgrammes' => [
			'noEducationLevels' => 'There are no available Education Level.',
			'noEducationProgrammes' => 'There are no available Education Programme.',
			'noEducationGrades' => 'There are no available Education Grade.',
			'start_date' => 'Start Date',
			'end_date' => 'End Date',
			'education_grade' => 'Education Grades'
		],
		'InstitutionSiteShifts' => [
			'start_time' => 'Start Time',
			'end_time' => 'End Time',
		],
		'InstitutionSiteSections' => [
			'noSections' => 'No Sections',
			'students' => 'Students',
			'education_programme' => 'Education Programme',
			'education_grade' => 'Education Grade',
			'security_user_id' => 'Home Room Teacher',
			'section' => 'Section',
			'single_grade_field' => 'Single Grade Sections',
			'multi_grade_field' => 'Multi-Grades Section',
			
			'emptyName' => 'Section name should not be empty',
			'emptySecurityUserId' => 'Home Room Teacher should not be empty',
			'emptyNameSecurityUserId' => 'Section name and Home Roome Teacher should not be empty',
			'emptySecurityUserIdName' => 'Section name and Home Roome Teacher should not be empty'
		],
		'InstitutionSiteClasses' => [
			'noSections' => 'No Sections',
			'noClasses' => 'No Classes',
			'classes' => 'Classes',
			'education_subject' => 'Subject',
			'class' => 'Class',
			'teacher' => 'Teacher',
			'students' => 'Students',
			'teachers' => 'Teachers',
		],
		'InstitutionSiteFees' => [
			'fee_types' => 'Fee Types',
			'noProgrammeGradeFees' => 'No Programme Grade Fees',
		],
		// 'InstitutionSiteStaffAbsences' => [
		// 	'first_date_absent' => 'First Day Of Absence',
		// 	'last_date_absent' => 'Last Day Of Absence'
		// ],
		'InstitutionAssessments' => [
			'reject' => [
				'success' => 'The record has been rejected successfully.',
				'failed' => 'The record is not rejected due to errors encountered.'
			],
		],
		'InstitutionAssessmentResults' => [
			'noSubjects' => 'There are no available Subjects.',
			'noSections' => 'There are no available Sections.',
			'noClasses' => 'There are no available Classes.',
		],
		'InstitutionSurveys' => [
			'reject' => [
				'success' => 'The record has been rejected successfully.',
				'failed' => 'The record is not rejected due to errors encountered.'
			],
		],
		'password'=> [
			'oldPassword' => 'Current Password',
			'retypePassword' => 'Retype New Password',
		],
		'EducationGrades' => [
			'add_subject' => 'Add Subject'
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
		'security' => [
			'login' => [
				'fail' => 'You have entered an invalid username or password.'
			],
			'noAccess' => 'You do not have access to this location.'
		],
		'SecurityRoles' => [
			'userRoles' => 'User Roles',
			'systemRoles' => 'System Roles'
		],
		'StudentAttendances' => [
			'noSections' => 'There are no available Sections.',
		],
		'StaffAttendances' => [
			'noStaff' => 'There are no available Staff.',
		],
		'StaffBehaviours' => [
			'date_of_behaviour' => 'Date',
			'time_of_behaviour' => 'Time',
		],
		'SystemGroups' => [
			'tabTitle' => 'System Groups'
		],
		'SystemRoles' => [
			'tabTitle' => 'System Roles'
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
		'time' => [
			'start' => 'Start Time',
			'end' => 'End Time',
			'from' => 'From',
			'to' => 'To'
		],
		'Users' => [
			'photo_content' => 'Photo Image',
			'start_date' => 'Start Date',
			'openemis_no' => 'OpenEMIS ID',
			'name' => 'Name',
			'gender' => 'Gender',
			'date_of_birth' => 'Date Of Birth',
			'student_category' => 'Category',
			'status' => 'Status',
			'select_student' => 'Select Student',
			'add_student' => 'Add Student',
			'select_staff' => 'Select Staff',
			'add_staff' => 'Add Staff',
			'select_teacher' => 'Select Teacher',
			'add_teacher' => 'Add Teacher'
		],
		'UserGroups' => [
			'tabTitle' => 'User Groups'
		],
		'Workflows' => [
			'workflow_model_id' => 'Form'
		],
		'WorkflowActions' => [
			'next_step' => 'Next Step',
			'comment_required' => 'Comment Required'
		],
		'InstitutionQualityVisits' => [
			'noPeriods' => 'No Available Periods',
			'noSections' => 'No Available Sections',
			'noClasses' => 'No Available Classes',
			'noStaff' => 'No Available Staff'
		],


		// Validation Messages
		'Institution' => [
			'Institutions' => [
				'noActiveInstitution' => 'There is no active institution',
				'noSubjectsInSection' => 'There is no subject in the selected section',
				'noSubjectSelected' => 'There is no subject selected',
				'noProgrammes' => 'There is no programme set for this institution',
				'noSections' => 'There is no section under the selected academic period',
				'date_closed' => [
					'ruleCompareDateReverse' => 'Date Closed should not be earlier than Date Opened'
				]
			],
			
			'InstitutionSiteSections' => [
				'noGrade' => 'There is no grade selected',
				'emptyName' => 'Section name should not be empty',
				'emptySecurityUserId' => 'Home Room Teacher should not be empty',
				'emptyNameSecurityUserId' => 'Section name and Home Room Teacher should not be empty',
			],

			'InstitutionSiteProgrammes' => [
				'education_programme_id' => [
					'unique' => 'This Education Programme already exists in the system'
				],
				'noGrade' => 'There is no grade selected',
				'end_date' => [
					'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
				]
			],
			'InstitutionSiteShifts' => [
				'start_time' => [
					'ruleCompareDate' => 'Start Time should not be later than End Time'
				],
				'end_time' => [
					'ruleCompareDateReverse' => 'End Time should not be earlier than Start Time'
				]
			],
			'InstitutionSiteStudentAbsences' => [
				'end_date' => [
					'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
				]
			],
			'InstitutionSiteStudents' => [
				'academicPeriod' => 'You need to configure Academic Periods first.',
				'educationProgrammeId' => 'You need to configure Education Programmes first.',
				'institutionSiteGrades' => 'You need to configure Institution Grades first.',
				'sections' => 'You need to configure Sections first.',
				'studentStatusId' => 'You need to configure Student Statuses first.',
			],
			'InstitutionSiteStaff' => [
				'institutionSitePositionId' => 'You need to configure Institution Site Positions first.',
				'securityRoleId' => 'You need to configure Security Roles first.',
				'FTE' => 'There are no available FTE for this position.',
				'staffTypeId' => 'You need to configure Staff Types first.'
			]
		],
		'User' => [
			'Users' => [
				'first_name' => [
					'ruleNotBlank' => 'Please enter a valid First Name',
					'ruleCheckIfStringGotNoNumber' => 'Please enter a valid First Name'
				],
				'middle_name' => [
					'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Middle Name'
				],
				'third_name' => [
					'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Third Name'
				],
				'last_name' => [
					'ruleNotBlank' => 'Please enter a valid Last Name',
					'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Last Name'
				],
				'preferred_name' => [
					'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Preferred Name'
				],
				'openemis_no' => [
					'ruleNotBlank' => 'Please enter a valid OpenEMIS ID',
					'ruleUnique' => 'Please enter a unique OpenEMIS ID'
				],
				'gender_id' => [
					'ruleNotBlank' => 'Please select a Gender'
				],
				'address' => [
					'ruleNotBlank' => 'Please enter a valid Address'
				],
				'date_of_birth' => [
					'ruleNotBlank' => 'Please select a Date of Birth',
					'ruleCompare' => 'Please select a Date of Birth',
					'ruleCompare' => 'Date of Birth cannot be future date'
				],
				'username' => [
					'ruleNotBlank' => 'Please enter a valid username',
					'ruleNoSpaces' => 'Only alphabets and numbers are allowed',
					'ruleUnique' => 'This username is already in use.'
				],
				'password' => [
					'ruleChangePassword' => 'Incorrect password.',
					'ruleCheckUsernameExists' => 'Please enter a valid password',
					'ruleMinLength' => 'Password must be at least 6 characters'
				],
				'retype_password' => [
					'ruleChangePassword' => 'Please confirm your new password',
					'ruleCompare' => 'Both passwords do not match'
				],
				'newPassword' => [
					'ruleChangePassword' => 'Please enter your new password',
					'ruleMinLength' => 'Password must be at least 6 characters'
				],
				'retypeNewPassword' => [
					'ruleChangePassword' => 'Please confirm your new password',
					'ruleCompare' => 'Both passwords do not match'
				],
				 'photo_content' => [
				 	'ruleCheckSelectedFileAsImage' => 'Please upload image format files. Eg. jpg, png, gif.',
				]
			],
			'Accounts' => [
				'username' => [
					'ruleNotBlank' => 'Please enter a valid username',
					'ruleNoSpaces' => 'Only alphabets and numbers are allowed',
					'ruleUnique' => 'This username is already in use.'
				],
				'password' => [
					'ruleChangePassword' => 'Incorrect password.',
					'ruleCheckUsernameExists' => 'Please enter a valid password',
					'ruleMinLength' => 'Password must be at least 6 characters'
				],
				'retype_password' => [
					'ruleChangePassword' => 'Please confirm your new password',
					'ruleCompare' => 'Both passwords do not match'
				],
				'newPassword' => [
					'ruleChangePassword' => 'Please enter your new password',
					'ruleMinLength' => 'Password must be at least 6 characters'
				],
				'retypeNewPassword' => [
					'ruleChangePassword' => 'Please confirm your new password',
					'ruleCompare' => 'Both passwords do not match'
				]
			],
			'Contacts' => [
				'contact_type_id' => [
					'ruleNotBlank' => 'Please enter a Contact Type'
				],
				'value' => [
					'ruleNotBlank' => 'Please enter a valid value',
					'ruleValidateNumeric' => 'Please enter a valid Numeric value',
					'ruleValidateEmail' => 'Please enter a valid Email',
					'ruleValidateEmergency' => 'Please enter a valid Value',
				],
				'preferred' => [
					'ruleValidatePreferred' => 'Please select a preferred contact type'
				],
			],
			'Identities' => [
				'identity_type_id' => [
					'ruleNotBlank' => 'Please select a Type'
				],
				'number' => [
					'ruleNotBlank' => 'Please enter a valid Number'
				],
				'issue_location' => [
					'ruleNotBlank' => 'Please enter a valid Issue Location'
				],
				'issue_date' => [
					'comparison' => 'Issue Date Should be Earlier Than Expiry Date'
				],
				'expiry_date' => [
					'ruleNotBlank' => 'Expiry Date Is Required'
				]
			],
			'Languages' => [
				'language_id' => [
					'ruleNotBlank' => 'Please select a Language'
				],
				'listening' => [
					'ruleNotBlank' => 'Please enter a number between 0 and 5'
				],
				'speaking' => [
					'ruleNotBlank' => 'Please enter a number between 0 and 5'
				],
				'reading' => [
					'ruleNotBlank' => 'Please enter a number between 0 and 5'
				],
				'writing' => [
					'ruleNotBlank' => 'Please enter a number between 0 and 5'
				],
			],
			'Comments' => [
				'title' => [
					'ruleNotBlank' => 'Please enter a valid Title'
				],
				'comment' => [
					'ruleNotBlank' => 'Please enter a valid Comment'
				],
			],
			'SpecialNeeds' => [
				'special_need_type_id' => [
					'ruleNotBlank' => 'Please select a valid Special Need Type.'
				]
			],
			'Awards' => [
				'award' => [
					'ruleNotBlank' => 'Please enter a valid Award.'
				],
				'issuer' => [
					'ruleNotBlank' => 'Please enter a valid Issuer.'
				]
			],
			'Attachments' => [
				'date_on_file' => 'Date On File',
				'name' => [
					'ruleNotBlank' => 'Please enter a File name'
				]
			],
			'BankAccounts' => [
				'account_name' => [
					'ruleNotBlank' => 'Please enter an Account name'
				],
				'account_number' => [
					'ruleNotBlank' => 'Please enter an Account number'
				],
				'bank_id' => [
					'ruleNotBlank' => 'Please select a Bank'
				],
				'bank_branch_id' => [
					'ruleNotBlank' => 'Please select a Bank Branch'
				]
			],
		],
		'Student' => [
			'Extracurriculars' => [
				'name' => [
					'ruleNotBlank' => 'Please enter a valid Title.'
				],
				'hours' => [
					'ruleNotBlank' => 'Please enter a valid Hours.'
				],
				'start_date' => [
					'ruleCompareDate' => 'Start Date cannot be later than End Date',
				]
			],
		],
		'Staff' => [
			'date_of_birth' => 'Date Of Birth',
			'photo_content' => 'Profile Image',
			'Qualifications' => [
				'qualification_title' => [
					'required' => 'Please enter a valid Qualification Title'
				],
				'graduate_year' => [
					'required' => 'Please enter a valid Graduate Year'
				],
				'qualification_level_id' => [
					'required' => 'Please enter a valid Qualification Level'
				],
				'qualification_specialisation_id' => [
					'required' => 'Please enter a valid Major/Specialisation'
				],
				'qualification_institution_name' => [
					'validHiddenId' => 'Please enter a valid Institution'
				]
			],
			'Leaves' => [
				'date_from' => [
					'ruleCompareDate' => 'Date From cannot be later than Date To',
					'ruleNoOverlap' => 'Leave have been selected for this date. Please choose a different date'
				],
				'number_of_days' => [
					'ruleNotBlank' => 'Please enter the number of days'
				]
			],
			'Extracurriculars' => [
				'name' => [
					'ruleNotBlank' => 'Please enter a valid Title.'
				],
				'hours' => [
					'ruleNotBlank' => 'Please enter a valid Hours.'
				],
				'start_date' => [
					'ruleCompareDate' => 'Start Date cannot be later than End Date',
				]
			],
			'Employments' => [
				'employment_type_id' => [
					'ruleNotBlank' => 'Please select a Type'
				],
				'employment_date' => [
					'ruleNotBlank' => 'Please enter a valid Date'
				]
			],
			'Salaries' => [
				'salary_date' => [
					'ruleNotBlank' => 'Please select a Salary Date'
				],
				'gross_salary' => [
					'ruleNotBlank' => 'Please enter a valid Gross Salary'
				],
				'net_salary' => [
					'ruleNotBlank' => 'Please enter a valid Net Salary'
				]
			],
			'Memberships' => [
				'membership' => [
					'ruleNotBlank' => 'Please enter a valid Membership.'
				],
				'issue_date' => [
			        'ruleCompareDate' => 'Issue Date cannot be later than Expiry Date',
			    ]
			],
			'Licenses' => [
				'license_type_id' => [
					'ruleNotBlank' => 'Please select a valid License Type.'
				],
				'issuer' => [
					'ruleNotBlank' => 'Please enter a valid Issuer.'
				],
				'license_number' => [
					'ruleNotBlank' => 'Please enter a valid License Number.'
				],
				'issue_date' => [
					'ruleCompareDate' => 'Issue Date cannot be later than Expiry Date',
				]
			],
		],
		'AcademicPeriod' => [
			'AcademicPeriods' => [
				'end_date' => [
					'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
				]
			]
		],
	];

	public function getMessage($code) {
		$index = explode('.', $code);

		$message = $this->messages;
		foreach ($index as $i) {
			if (isset($message[$i])) {
				$message = $message[$i];
			} else {
				$message = '[Message Not Found]';
				break;
			}
		}
		return !is_array($message) ? __($message) : $message;
	}
}

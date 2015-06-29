<?php
namespace App\Model\Traits;

trait MessagesTrait {
	public $messages = [
		'general' => [
			'notExists' => 'The record does not exist.',
			'notEditable' => 'This record is not editable',
			'exists' => 'The record is exists in the system.',
			'noData' => 'There are no records.',
			'error' => 'An unexpected error has been encounted. Please contact the administrator for assistance.',
			'add' => [
				'success' => 'The record has been added successfully.',
				'failed' => 'The record is not added due to errors encountered.'
			],
			'edit' => [
				'success' => 'The record has been updated successfully.',
				'failed' => 'The record is not updated due to errors encountered.'
			],
			'delete' => [
				'success' => 'The record has been deleted successfully.',
				'failed' => 'The record is not deleted due to errors encountered.',
			],
			'duplicate' => [
				'success' => 'The record has been duplicated successfully.',
				'failed' => 'The record is not duplicated due to errors encountered.',
			],
			'invalidDate' => 'You have entered an invalid date.',
			'invalidUrl' => 'You have entered an invalid url.',
			'notSelected' => 'No Record has been selected/saved.'
		],
		'security' => [
			'login' => [
				'fail' => 'You have entered an invalid username or password.'
			],
			'noAccess' => 'You do not have access to this location.'
		],
		'InstitutionSiteProgrammes' => [
			'noEducationLevels' => 'There are no available Education Level.',
			'noEducationProgrammes' => 'There are no available Education Programme.',
			'noEducationGrades' => 'There are no available Education Grade.',
		],
		'InstitutionSiteSections' => [
			'noSections' => 'No Sections',
			'students' => 'Students',
			'education_programme' => 'Education Programme',
			'education_grade' => 'Education Grade',
			'security_user_id' => 'Home Room Teacher',
			'section' => 'Section',
			'single_grade_field' => 'Cingle Grade'
		],
		'InstitutionSiteClasses' => [
			'noSections' => 'No Sections',
			'noClasses' => 'No Classes'
		],
		'InstitutionSiteFees' => [
			'noProgrammeGradeFees' => 'No Programme Grade Fees',
		],
		'StudentAttendance' => [
			'noSections' => 'No Sections'
		],
		'UserGroups' => [
			'tabTitle' => 'User Groups'
		],
		'SystemGroups' => [
			'tabTitle' => 'System Groups'
		],
		'UserRoles' => [
			'tabTitle' => 'User Roles'
		],
		'SystemRoles' => [
			'tabTitle' => 'System Roles'
		],
		'Users' => [
			'openemis_no' => 'OpenEMIS ID',
			'name' => 'Name',
			'gender' => 'Gender',
			'date_of_birth' => 'Date Of Birth',
			'student_category' => 'Category',
			'select_student' => 'Select Students',
			'add_student' => 'Add Students',
			'select_staff' => 'Select Staff',
			'add_staff' => 'Add Staff'
		],
		
		// Validation Messages
		'Institution' => [
			'Institutions' => [
				'noProgrammes' => 'There is no available Programme set for this Institution',
				'noSections' => 'There is no available Section under the selected Academic Period',
				'date_closed' => [
					'ruleCompareDateReverse' => 'Date Closed should not be earlier than Date Opened'
				]
			],
			'InstitutionSiteProgrammes' => [
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
					'ruleNotBlank' => 'Please enter a valid value'
				],
				'preferred' => [
					'comparison' => 'Please select a preferred for the selected contact type'
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

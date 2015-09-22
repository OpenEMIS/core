<?php
namespace App\Model\Traits;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;

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
			'noRecords' => 'No Record',
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
			'deleteTransfer' =>[
				'restrictDelete' => 'The transfer cannot be performed as there is no options to transfer to.'
			],
			'view' => [
				'label' => 'View',
			],
			'duplicate' => [
				'success' => 'The record has been duplicated successfully.',
				'failed' => 'The record is not duplicated due to errors encountered.',
			],
			'academicPeriod' => [
				'notEditable' => 'The chosen academic period is not editable',
			],
			'invalidTime' => 'You have entered an invalid time.',
			'invalidDate' => 'You have entered an invalid date.',
			'invalidUrl' => 'You have entered an invalid url.',
			'notSelected' => 'No Record has been selected / saved.',
			'order' => 'Order',
			'visible' => 'Visible',
			'name' => 'Name',
			'description' => 'Description',
			'default' => 'Default',
			'reject' => 'Reject',
			'noSections' => 'No Classes',
			'noClasses' => 'No Subjects',
			'noStaff' => 'No Staff',
			'type' => 'Type',
			'amount' => 'Amount',
			'total' => 'Total'
		],
		'fileUpload' => [
			'single' => '*File size should not be larger than 2MB.',
			'multi' => '*Maximum 5 files are permitted on single upload. Each file size should not be larger than 2MB.',
		],
		'InfrastructureTypes' => [
			'noLevels' => 'No Available Levels',
			'infrastructure_level_id' => 'Level Name'
		],
		'InfrastructureCustomFields' => [
			'infrastructure_level_id' => 'Level Name'
		],
		'Institutions' => [
			'date_opened' => 'Date Opened',
			'date_closed' => 'Date Closed',
			'noSections' => 'No Available Classes'
		],
		'InstitutionSiteStaff' => [
			'title' => 'Staff',
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
		'InstitutionGrades' => [
			'noEducationLevels' => 'There are no available Education Level.',
			'noEducationProgrammes' => 'There are no available Education Programme.',
			'noEducationGrades' => 'There are no available Education Grade.',
			'noGradeSelected' => 'No Education Grade was selected.',
			'failedSavingGrades' => 'Failed to save grades',
			'start_date' => 'Start Date',
			'end_date' => 'End Date',
			'education_grade' => 'Education Grades'
		],
		'InstitutionSiteShifts' => [
			'start_time' => 'Start Time',
			'end_time' => 'End Time',
		],
		'InstitutionSiteSections' => [
			'noSections' => 'No Classes',
			'students' => 'Students',
			'education_programme' => 'Education Programme',
			'education_grade' => 'Education Grade',
			// 'security_user_id' => 'Home Room Teacher',
			'section' => 'Class',
			'single_grade_field' => 'Single Grade Classes',
			'multi_grade_field' => 'Class Grades',
			
			'emptyName' => 'Class name should not be empty',
			'emptySecurityUserId' => 'Home Room Teacher should not be empty',
			'emptyNameSecurityUserId' => 'Class name and Home Room Teacher should not be empty',
			'emptySecurityUserIdName' => 'Class name and Home Room Teacher should not be empty',

		],
		'InstitutionSiteClasses' => [
			'noGrades' => 'No Grades Assigned',
			'noSections' => 'No Classes',
			'noClasses' => 'No Subjects',
			'subjects' => 'Subjects',
			'education_subject' => 'Subject',
			'class' => 'Subject',
			'teacher' => 'Teacher',
			'students' => 'Students',
			'teachers' => 'Teachers',
		],
		'InstitutionSiteFees' => [
			'fee_types' => 'Fee Types',
			'noProgrammeGradeFees' => 'No Programme Grade Fees',
		],
		'Students' => [
			'noGrades' => 'No Grades',
			'noStudents' => 'No Student found'
		],
		'StudentFees' => [
			'totalAmountExceeded' => 'Total Amount Exceeded Outstanding Amount',
		],
		// 'InstitutionSiteStaffAbsences' => [
		// 	'first_date_absent' => 'First Day Of Absence',
		// 	'last_date_absent' => 'Last Day Of Absence'
		// ],
		'InstitutionAssessments' => [
			'save' => [
				'draft' => 'Assessment record has been saved to draft successfully.',
				'final' => 'Assessment record has been submitted successfully.',
				'failed' => 'The record is not saved due to errors encountered.',
			],
			'reject' => [
				'success' => 'The record has been rejected successfully.',
				'failed' => 'The record is not rejected due to errors encountered.'
			],
		],
		'InstitutionAssessmentResults' => [
			'noSubjects' => 'There are no available Subjects.',
			'noSections' => 'There are no available Classes.',
			'noClasses' => 'There are no available Subjects.',
		],
		'InstitutionSurveys' => [
			'save' => [
				'draft' => 'Survey record has been saved to draft successfully.',
				'final' => 'Survey record has been submitted successfully.'
			],
			'reject' => [
				'success' => 'The record has been rejected successfully.',
				'failed' => 'The record is not rejected due to errors encountered.'
			],
			'section' => 'Class',
			'noAccess' => 'You do not have access to this Class.'
		],
		'InstitutionRubricAnswers' => [
			'rubric_template' => 'Rubric Template',
			'rubric_section_id' => 'Section',
			'noSection' => 'There is no rubric section selected',
			'save' => [
				'draft' => 'Rubric record has been saved to draft successfully.',
				'final' => 'Rubric record has been submitted successfully.',
				'failed' => 'This rubric record is not submitted due to criteria answers is not complete.'
			],
			'reject' => [
				'success' => 'The record has been rejected successfully.',
				'failed' => 'The record is not rejected due to errors encountered.'
			]
		],
		'StudentSurveys' => [
			'noSurveys' => 'No Surveys',
			'save' => [
				'draft' => 'Survey record has been saved to draft successfully.',
				'final' => 'Survey record has been submitted successfully.'
			]
		],
		'password'=> [
			'oldPassword' => 'Current Password',
			'retypePassword' => 'Retype New Password',
		],
		'EducationGrades' => [
			'add_subject' => 'Add Subject'
		],
		'RubricCriterias' => [
			//'rubric_section_id' => 'Rubric Section',
			'criterias' => 'Criterias'
		],
		'RubricTemplateOptions' => [
			'weighting' => 'Weighting'
		],
		'security' => [
			'login' => [
				'fail' => 'You have entered an invalid username or password.',
				'inactive' => 'Your account has been disabled.'
			],
			'noAccess' => 'You do not have access to this location.'
		],
		'SecurityRoles' => [
			'userRoles' => 'User Roles',
			'systemRoles' => 'System Roles'
		],
		'StudentAttendances' => [
			'noSections' => 'No Available Classes'
		],
		'InstitutionSiteStudentAbsences' => [
			'noSections' => 'No Available Classes',
			'noStudents' => 'No Available Students'
		],
		'StaffAttendances' => [
			'noStaff' => 'No Available Staff'
		],
		'StaffAbsences' => [
			'noStaff' => 'No Available Staff'
		],
		'StaffBehaviours' => [
			'date_of_behaviour' => 'Date',
			'time_of_behaviour' => 'Time'
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
		'SurveyForms' => [
			'add_question' => 'Add Question',
			'add_to_section' => 'Add to Section',
			'notSupport' => 'Not supported in this form.'
		],
		'time' => [
			'start' => 'Start Time',
			'end' => 'End Time',
			'from' => 'From',
			'to' => 'To'
		],
		'Users' => [
			'student_category' => 'Category',
			'status' => 'Status',
			'select_student' => 'Select Student',
			'select_student_empty' => 'No Other Student Available',
			'add_all_student' => 'Add All Students',
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
			'noSections' => 'No Available Classes',
			'noClasses' => 'No Available Subjects',
			'noStaff' => 'No Available Staff'
		],
		'StudentBehaviours' => [
			'noClasses' => 'No Classes',
			'noStudents' => 'No Students'
		],
		'TransferRequests' => [
			'request' => 'Transfer request has been submitted successfully.',
			'enrolled' => 'This student has already been enrolled in an institution.',
			'hasDropoutApplication' => 'There is a pending dropout application for this student at the moment, please reject the dropout application before making another request.'
		],
		'TransferApprovals' => [
			'exists' => 'Student is already exists in the new school',
			'approve' => 'Transfer request has been approved successfully.',
			'reject' => 'Transfer request has been rejected successfully.'
		],
		'StudentPromotion' => [
			'noGrades' => 'No Available Grades',
			'noStudents' => 'No Available Students',
			'noPeriods' => 'You need to configure Academic Periods for Promotion / Graduation.',
			'noData' => 'There are no available Students for Promotion / Graduation.',
			'current_period' => 'Current Academic Period',
			'next_period' => 'Next Academic Period',
			'success' => 'Students have been promoted.',
			'noNextGrade' => 'Next grade in the Education Structure is not available in this Institution.'
		],
		'StudentTransfer' => [
			'noGrades' => 'No Available Grades',
			'noStudents' => 'No Available Students',
			'noInstitutions' => 'No Available Institutions',
			'noData' => 'There are no available Students for Transfer.',
			'success' => 'Students have been transferred.'
		],
		'EducationProgrammes' => [
			'add_next_programme' => 'Add Next Programme'
		],
		'StudentAdmission' => [
			'exists' => 'Student exists in the school',
			'existsInRecord' => 'Student has already been added to admission list',
			'approve' => 'Student admission has been approved successfully.',
			'reject' => 'Student admission has been rejected successfully.'
		],
		'DropoutRequests' => [
			'request' => 'Dropout request hsa been submitted successfully.',
		],
		'StudentDropout' => [
			'exists' => 'Student has already dropped out from the school.',
			'approve' => 'Dropout request has been approved successfully.',
			'reject' => 'Dropout request has been rejected successfully.',
			'hasTransferApplication' => 'There is a pending transfer application for this student at the moment, please remove the transfer application before making another request.'
		],

		// Validation Messages
		'Institution' => [
			'Institutions' => [
				'noActiveInstitution' => 'There is no active institution',
				'noSubjectsInSection' => 'There are no subjects in the assigned grade',
				'noSubjectSelected' => 'There is no subject selected',
				'noProgrammes' => 'There is no programme set for this institution',
				'noSections' => 'There is no class under the selected academic period',
				'date_closed' => [
					'ruleCompareDateReverse' => 'Date Closed should not be earlier than Date Opened'
				],
				'email' => [
					'ruleValidEmail' => 'Please enter a valid Email'
				],
				'longitude' => [
					'ruleLongitude' => 'Please enter a valid Longitude'
				],
				'latitude' => [
					'ruleLatitude' => 'Please enter a valid Latitude'
				],
				'code' => [
					'ruleUnique' => 'Please enter a unique code'
				]
			],
			
			'InstitutionSiteSections' => [
				'noGrade' => 'There is no grade selected',
				'emptyName' => 'Class name should not be empty',
				'emptySecurityUserId' => 'Home Room Teacher should not be empty',
				'emptyNameSecurityUserId' => 'Class name and Home Room Teacher should not be empty',
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
			'InstitutionGrades' => [
				'end_date' => [
					'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
				],
				'start_date' => [
					'ruleCompareWithInstitutionDateOpened' => 'Start Date should not be earlier than Institution Date Opened'
				]
			],
			'Absences' => [
				'start_date' => [
					'ruleNoOverlappingAbsenceDate' => 'Absence is already added for this date and time.',
					'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
				],
				'end_date' => [
					'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
				],
			],
			// 'InstitutionSiteStudentAbsences' => [
			// 	'end_date' => [
			// 		'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
			// 	]
			// ],
			'InstitutionSiteStudents' => [
				'academicPeriod' => 'You need to configure Academic Periods first.',
				'educationProgrammeId' => 'You need to configure Education Programmes first.',
				'institutionSiteGrades' => 'You need to configure Institution Grades first.',
				'sections' => 'You need to configure Classes first.',
				'studentStatusId' => 'You need to configure Student Statuses first.',
			],
			'InstitutionSiteStaff' => [
				'institutionSitePositionId' => 'You need to configure Institution Site Positions first.',
				'securityRoleId' => 'You need to configure Security Roles first.',
				'FTE' => 'There are no available FTE for this position.',
				'staffTypeId' => 'You need to configure Staff Types first.'
			],
			'StudentGuardians' => [
				'guardianRelationId' => 'You need to configure Guardian Relations first.',
				'guardianEducationLevel' => 'You need to configure Guardian Education Level first.'

			],
			'StaffPositions' => [
				'end_date' => [
					'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
				]
			],
			'TransferRequests' => [
				'end_date' => [
					'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
				]
			],
			'Students' => [
				'student_name' => [
					'ruleInstitutionStudentId' => 'Student has already been added.',
					'ruleCheckAdmissionAgeWithEducationCycle' => 'This student does not fall within the allowed age range for this grade.',
					'ruleStudentEnrolledInOthers' => 'Student has already been enrolled in another Institution.'
				]
			],
			'Staff' => [
				'staff_name' => [
					'ruleInstitutionStaffId' => 'Staff has already been added.'
				],
				'FTE' => [
					'ruleCheckFTE' => 'No available FTE.'
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
			'Guardians' => [
				'guardian_id' => [
					'ruleStudentGuardianId' => 'This guardian has already added.'
				]
			]
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
				],
				'current' => [
					'ruleValidateNeeded' => 'Academic Period needs to be set as current'
				]
			]
		],
		'Localization' => [
			'Translations' => [
				'en' => [
					'ruleUnique' => 'This translation is already exists'
				]
			]
		],
		'Translations' => [
			'success' => 'The language has been successfully compiled.',
			'failed' => 'The language has not been compiled due to errors encountered.',
		],
		'Security' => [
			'Users' => [
				'username' => [
					'ruleUnique' => 'This username is already in use'
				]
			]
		],
		'Labels' => [
			'code' => [
				'ruleUnique' => 'This code already exists in the system'
			]
		]
	];

	public function getMessage($code) {
		$index = explode('.', $code);

		$message = $this->messages;
		foreach ($index as $i) {
			if (isset($message[$i])) {
				$message = $message[$i];
			} else {
				//check whether label exists in cache
				$Labels = TableRegistry::get('Labels');
				$message = Cache::read($code, $Labels->getDefaultConfig());
				if($message === false) {
					$message = '[Message Not Found]';
					break;
				}
			}
		}
		return !is_array($message) ? __($message) : $message;
	}
}

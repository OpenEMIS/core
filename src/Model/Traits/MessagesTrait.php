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
			]
		],
		'InstitutionSiteProgrammes' => [
			'noEducationLevels' => 'There are no available Education Level.',
			'noEducationProgrammes' => 'There are no available Education Programme.',
			'noEducationGrades' => 'There are no available Education Grade.',
		],
		
		// Validation Messages
		'Institutions' => [
			'noProgrammes' => 'There is no available Programme set for this Institution.',
			'noSections' => 'There is no available Section under the selected Academic Period.',
			'date_closed' => [
				'ruleCompareDateReverse' => 'Date Closed should not be earlier than Date Opened'
			]
		],

		'Users' => [
			'first_name' => [
				'ruleRequired' => 'Please enter a valid First Name',
				'ruleCheckIfStringGotNoNumber' => 'Please enter a valid First Name'
			],
			'middle_name' => [
				'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Middle Name'
			],
			'third_name' => [
				'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Third Name'
			],
			'last_name' => [
				'ruleRequired' => 'Please enter a valid Last Name',
				'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Last Name'
			],
			'preferred_name' => [
				'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Preferred Name'
			],
			'openemis_no' => [
				'ruleRequired' => 'Please enter a valid OpenEMIS ID',
				'ruleUnique' => 'Please enter a unique OpenEMIS ID'
			],
			'gender_id' => [
				'ruleRequired' => 'Please select a Gender'
			],
			'address' => [
				'ruleRequired' => 'Please enter a valid Address'
			],
			'date_of_birth' => [
				'ruleRequired' => 'Please select a Date of Birth',
				'ruleCompare' => 'Please select a Date of Birth',
				'ruleCompare' => 'Date of Birth cannot be future date'
			],
			'username' => [
				'ruleRequired' => 'Please enter a valid username',
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
		]
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

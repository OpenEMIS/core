<?php
namespace App\Model\Traits;

trait OptionsTrait {
	public function getSelectOptions($code) {
		$options = [
			'general' => [
				'active' => [1 => __('Active'), 0 => __('Inactive')],
				'yesno' => [1 => __('Yes'), 0 => __('No')],
			],
			'Staff' => [
				'position_types' => [1 => __('Teaching'), 0 => __('Non-Teaching')]
			],
			'Position' => [
				'types' => ['FULL_TIME' => __('Full-Time'), 'PART_TIME' => __('Part-Time')]
			],
			'Assessments' => [
				'status' => [0 => __('New'), 1 => __('Draft'), 2 => __('Completed')],
				'types' => [1 => __('Non-Official'), 2 => __('Official')]
			],
			'AssessmentItems' => [
				'mark_types' => ['MARKS' => __('Marks'), 'GRADES' => __('Grades')]
			],
			'AssessmentGradingTypes' => [
				'result_type' => ['MARKS' => __('Marks'), 'GRADES' => __('Grades')]
			],
			'Surveys' => [
				'status' => [0 => __('New'), 1 => __('Draft'), 2 => __('Completed')]
			],
			'Rubrics' => [
				'types' => [1 => __('Section Break'), 2 => __('Criteria')],
				'status' => [0 => __('New'), 1 => __('Draft'), 2 => __('Completed')]
			],
			'TrainingSessions' => [
				'trainer_types' => ['INTERNAL' => __('Internal'), 'EXTERNAL' => __('External')]
			],
			'TrainingNeeds' => [
				'types' => ['CATALOGUE' => __('Course Catalogue'), 'NEED' => __('Need Category')]
			],
			'Health' => [
				'blood_types' => [
					'O+' => 'O+', 'O-' => 'O-',
					'A+' => 'A+', 'A-' => 'A-',
					'B+' => 'B+', 'B-' => 'B-',
					'AB+' => 'AB+', 'AB-' => 'AB-'
				]
			],
			'StaffPositionProfiles' => [
				'FTE' => [
					'0.25' => '25%', 
					'0.5' => '50%', 
					'0.75' => '75%', 
					'1' => '100%'
				],
			],
		];

		$index = explode('.', $code);
		foreach ($index as $i) {
			if (isset($options[$i])) {
				$options = $options[$i];
			} else {
				$options = false;
				break;
			}
		}
		return $options;
	}
}

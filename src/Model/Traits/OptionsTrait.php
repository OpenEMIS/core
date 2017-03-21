<?php
namespace App\Model\Traits;

trait OptionsTrait {
	public function getSelectOptions($code) {
		$options = [
			'general' => [
				'active' => [1 => __('Active'), 0 => __('Inactive')],
				'yesno' => [1 => __('Yes'), 0 => __('No')],
			],
			'Authentication' => [
				'yesno' => [0 => __('No'), 1 => __('Yes')]
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
				'result_type' => ['MARKS' => __('Marks'), 'GRADES' => __('Grades'), 'DURATION' => ('Duration')]
			],
			'ExaminationGradingTypes' => [
				'result_type' => ['MARKS' => __('Marks'), 'GRADES' => __('Grades')]
			],
            'CompetencyGradingTypes' => [
                'result_type' => ['STATUS' => __('Status'), 'MARKS' => __('Marks'), 'GRADES' => __('Grades')]
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
			'StaffTrainingNeeds' => [
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
			'RoomTypes' => [
				'classifications' => [0 => __('Non-Classroom'), 1 => __('Classroom')]
			],
			'InstitutionRooms' => [
				'change_types' => [1 => __('Update Details'), 2 => __('End of Usage'), 3 => __('Change in Room Type')]
			],
			'Shifts' => [
				'types' => [
		        	1 => __('Single Shift Owner'),
					2 => __('Single Shift Occupier'),
					3 => __('Multiple Shift Owner'),
					4 => __('Multiple Shift Occupier')
        		]
        	],
        	'ExaminationCentres' => [
        		'create_as' => [
        			'existing' => __('Existing Institution'),
        			'new' => __('New Examination Centre')
        		]
        	],
        	'WorkflowSteps' => [
        		'category' => [
	    			1 => __('To Do'),
	    			2 => __('In Progress'),
	    			3 => __('Done')
	    		]
        	],
        	'AlertLogs' => [
        		'feature_grouping' => [
        			'general' => __('General'),
        			'workflow' => __('Workflow')
        		]
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

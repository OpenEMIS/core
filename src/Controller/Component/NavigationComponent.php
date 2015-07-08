<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;

class NavigationComponent extends Component {
	public $controller;
	public $action;
	public $breadcrumbs = [];

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];
	}

	public function addCrumb($title, $options=array()) {
		$item = array(
			'title' => __($title),
			'link' => ['url' => $options],
			'selected' => sizeof($options)==0
		);
		$this->breadcrumbs[] = $item;
	}

	public function substituteCrumb($oldTitle, $title, $options=array()) {
		foreach ($this->breadcrumbs as $key=>$value) {
			if ($value['title'] == __($oldTitle)) {
				unset($this->breadcrumbs[$key]);
				break;
			}
		}
		$this->addCrumb($title, $options);
	}

	public function beforeRender(Event $event) {
		$this->controller->set('_breadcrumbs', $this->breadcrumbs);

		$controller = $this->controller;
		$action = $this->action;

		$id = false;
		if ($this->controller->activeObj) {
			$id = $this->controller->activeObj->id;
		}

		$navigations = [
			'collapse' => false,
			'items' => [
				'Institutions' => [
				
					'collapse' => true,
					'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']
				],
				'Students' => [
					'collapse' => true,
					'url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']
				],
				'Staff' => [
					'collapse' => true,
					'url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']
				],
				// 'Guardians' => [
				// 	'collapse' => true,
				// 	'url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']
				// ],
				'Reports' => [
					'collapse' => true,
					'url' => ['plugin' => false, 'controller' => 'Reports', 'action' => 'index']
				],
				'Administration' => [
					'collapse' => true,
					'items' => [
						'System Setup' => [
							'collapse' => true,
							'items' => [
								'Administrative Boundaries' => [
									'collapse' => true,
									'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'Areas'],
									'selected' => ['Levels', 'AdministrativeLevels', 'Administratives']
								],
								'Academic Periods' => [
									'collapse' => true,
									'url' => ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => 'Periods'],
									'selected' => ['Levels']
								],
								'Education Structure' => [
									'collapse' => true,
									'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Systems'],
									'selected' => ['Levels', 'Cycles', 'Programmes', 'Grades', 'Setup']
								],
								'Infrastructure' => [
									'collapse' => true,
									'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Fields'],
									'selected' => ['Levels', 'Types']
								],
								'Assessments' => [
									'collapse' => true,
									'url' => ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => 'index'],
									'selected' => ['index', 'add', 'view', 'edit', 'GradingTypes', 'GradingOptions', 'Status']
								],
								'Field Options' => [
									'collapse' => true,
									'url' => ['plugin' => 'FieldOption', 'controller' => 'FieldOptions', 'action' => 'index'],
									'selected' => ['index', 'view', 'edit']
								],
								// 'Translations' => [
								// 	'collapse' => true,
								// 	'url' => ['plugin' => false, 'controller' => 'Translations', 'action' => 'index']
								// ],
								'Custom Field' => [
									'collapse' => true,
									'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Fields'],
									'selected' => ['Pages']
								],
								'System Configurations' => [
									'collapse' => true,
									'url' => ['plugin' => false, 'controller' => 'Configurations', 'action' => 'index']
								],
								'Notices' => [
									'collapse' => true,
									'url' => ['plugin' => false, 'controller' => 'Notices', 'action' => 'index']
								]
							]
						],
						'Security' => [
							'collapse' => true,
							'items' => [
								'Users' => [
									'collapse' => true,
									'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'Users']
								],
								'Groups' => [
									'collapse' => true,
									'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'UserGroups'],
									'selected' => ['UserGroups', 'SystemGroups']
								],
								'Roles' => [
									'collapse' => true,
									'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'Roles']
								]
							]
						],
						'Survey' => [
							'collapse' => true,
							'url' => ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => 'Questions'],
							'selected' => ['Questions', 'Forms', 'Status']
						],
						'Rubric' => [
							'collapse' => true,
							'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Templates'],
							'selected' => ['Sections', 'Criterias', 'Options', 'Status']
						],
						'Workflow' => [
							'collapse' => true,
							'url' => ['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'Workflows'],
							'selected' => ['Steps']
						]
					]
				]
			]
		];

		if ($controller->name == 'Institutions' && $action != 'index') {
			$navigations['items']['Institutions']['items'] = [
				'General' => [
					'collapse' => true,
					'items' => [
						'Dashboard' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $id]],
						'Overview' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id], 'selected' => ['edit']],
						'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Attachments']],
						'History' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'History']]
					]
				],
				'Details' => [
					'collapse' => true,
					'items' => [
						'Positions' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Positions']],
						'Programmes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Programmes']],
						'Shifts' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Shifts']],
						'Sections' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Sections']],
						'Classes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes']],
						'Infrastructures' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Infrastructures']]
					]
				],

				'Staff' => [
					'collapse' => true,
					'items' => [
						'List' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Staff']],
						'Behaviour' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffBehaviours']],
						'Attendance' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAbsences']]
					]
				],

				'Students' => [
					'collapse' => true,
					'items' => [
						'List' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students']],
						'Behaviour' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentBehaviours']],
						'Attendance' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAttendance'], 'selected' => ['StudentAttendance', 'StudentAbsences']],
						'Results' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Assessments'], ['selected' => ['Assessments', 'StudentResults']]]
					]
				],

				'Finance' => [
					'collapse' => true,
					'items' => [
						'Bank Accounts' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'BankAccounts']],
						'Fees' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Fees']],
						'Student Fees' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentFees']]
					]
				],

				'Survey' => [
					'collapse' => true,
					'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys']
				],

				'Quality' => [
					'collapse' => true,
					'items' => [
						'Rubrics' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Rubrics']],
						'Visits' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Visits']]
					]
				]
			];
		} else if ($controller->name == 'Students' && $action != 'index') {
			$navigations['items']['Students']['items'] = [
				'General' => [
					'collapse' => true,
					'items' => [
						'Overview' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'view', $id]],
						'Contacts' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Contacts']],
						'Identities' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Identities']],
						'Languages' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Languages']],
						'Comments' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Comments']],
						'Special Needs' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'SpecialNeeds']],
						'Awards' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Awards']],
						'Attachments' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Attachments']],
						'History' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'History']]
					]
				],
				'Details' => [
					'collapse' => true,
					'items' => [
						// 'Guardians' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Guardians']],
						'Programmes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Programmes']],
						'Sections' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Sections']],
						'Classes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Classes']],
						'Absences' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Absences']],
						'Behaviours' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Behaviours']],
						'Results' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Results']],
						'Extracurriculars' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Extracurriculars']]
					]
				],
				'Finance' => [
					'collapse' => true,
					'items' => [
						'Bank Accounts' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'BankAccounts']],
						'Fees' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'StudentFees']],
					]
				],
				// 'Health' => [
				// 	'collapse' => true,
				// 	'items' => [
				// 		'<placeholder>' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'BankAccounts']]
				// 	]
				// ]
			];
		} else if ($controller->name == 'Staff' && $action != 'index') {
			$navigations['items']['Staff']['items'] = [
				'General' => [
					'collapse' => true,
					'items' => [
						'Overview' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'view', $id]],
						'Contacts' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Contacts']],
						'Identities' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Identities']],
						'Languages' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Languages']],
						'Comments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Comments']],
						'Special Needs' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'SpecialNeeds']],
						'Awards' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Awards']],
						'Attachments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Attachments']],
						'History' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'History']]
					]
				],
				'Details' => [
					'collapse' => true,
					'items' => [
						'Qualifications' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Qualifications']],
						// 'Training' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Training']],
						'Positions' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Positions']],
						'Sections' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Sections']],
						'Classes' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Classes']],
						'Absences' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Absences']],
						'Leave' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Leaves']],
						'Behaviours' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Behaviours']],
						'Extracurriculars' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Extracurriculars']],
						'Employments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Employments']],
						'Salaries' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Salaries']],
						'Memberships' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Memberships']],
						'Licenses' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Licenses']],
					]
				],
				'Finance' => [
					'collapse' => true,
					'items' => [
						'Bank Accounts' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'BankAccounts']],
					]
				]
			];
		} else if ($controller->name == 'Guardian' && $action != 'index') {
			$navigations['items']['Guardian']['items'] = [
				'General' => [
					'collapse' => true,
					'items' => [
						'Overview' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardian', 'action' => 'view', $id]],
						'Contacts' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardian', 'action' => 'Contacts']],
						'Identities' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardian', 'action' => 'Identities']],
						'Languages' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardian', 'action' => 'Languages']],
						'Comments' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardian', 'action' => 'Comments']],
						// 'Special Needs' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'SpecialNeeds']],
						// 'Awards' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Awards']],
						// 'Attachments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Attachments']],
						// 'History' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'History']]
					]
				],
			];
		} else {
			
		}
		$controller->set('_navigations', $navigations);
	}
}

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

	public function beforeRender(Event $event) {
		$this->controller->set('_breadcrumbs', $this->breadcrumbs);

		$controller = $this->controller;
		$action = $this->action;

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
									'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'Areas']
								],
								'Academic Periods' => [
									'collapse' => true,
									'url' => ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => 'Periods']
								],
								'Education Structure' => [
									'collapse' => true,
									'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Systems']
								],
								'Infrastructure' => [
									'collapse' => true,
									'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Levels']
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
									'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Fields']
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
									'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'UserGroups']
								],
								'Roles' => [
									'collapse' => true,
									'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'UserRoles']
								]
							]
						],
						'Survey' => [
							'collapse' => true,
							'items' => [
								'Questions' => [
									'collapse' => true,
									'url' => ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => 'Questions']
								]
							]
						],
						'Rubric' => [
							'collapse' => true,
							'items' => [
								'Rubrics' => [
									'collapse' => true,
									'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Templates']
								]
							]
						],
						'Workflow' => [
							'collapse' => true,
							'items' => [
								'Workflows' => [
									'collapse' => true,
									'url' => ['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'index']
								],
								'Steps' => [
									'collapse' => true,
									'url' => ['plugin' => 'Workflow', 'controller' => 'WorkflowSteps', 'action' => 'index']
								]
							]
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
						'Overview' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view'], 'selected' => ['edit']],
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
						'Results' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentResults']]
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
						'Rubrics' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => '#']],
						'Visits' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => '#']]
					]
				]
			];
		} else if ($controller->name == 'Students' && $action != 'index') {
			$navigations['items']['Students']['items'] = [
				'General' => [
					'collapse' => true,
					'items' => [
						'Overview' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'view']],
						'Contacts' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Contacts']],
						'Identities' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Identities']],
						'Languages' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Languages']],
						'Comments' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Comments']],
						'Special Needs' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'SpecialNeeds']],
						'Awards' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Awards']],
						'Attachments' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Attachments']],
						'History' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'History']]
						// 'More' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'additional']]
					]
				],
				'Details' => [
					'collapse' => true,
					'items' => [
						// 'Guardians' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']],
						'Programmes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Programmes']],
						'Sections' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Sections']],
						'Classes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Classes']],
						'Absences' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Absences']],
						'Behaviours' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Behaviours']],
						'Results' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Results']],
						'Extracurriculars' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Extracurriculars']]
// <a href="/core/Students/guardians" >Guardians
// <a href="/core/Students/Programme" >Programmes
// <a href="/core/Students/StudentSection" >Sections
// <a href="/core/Students/classes" >Classes
// <a href="/core/Students/Absence" >Absence
// <a href="/core/Students/StudentBehaviour" >Behaviour
// <a href="/core/Students/assessments" >Results
// <a href="/core/Students/extracurricular" >Extracurricular
					]
				],
				'Finance' => [
					'collapse' => true,
					'items' => [
						'Bank Accounts' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'BankAccounts']],
						'Fees' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'StudentFees']],
// <a href="/core/Students/bankAccounts" >Bank Accounts
// <a href="/core/Students/StudentFee" >Fees
					]
				],
				// 'Health' => [
				// 	'collapse' => true,
				// 	'items' => [
				// 		'<placeholder>' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'BankAccounts']]
				// 	]
				// ]
			];
			// $navigations['items']['Institutions']['items'] = [
			// 	'Overview' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id]],
			// 	'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'attachments']],
			// 	'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'attachments']],
			// 	'Positions' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'positions']],
			// 	'Programmes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'programmes']],
			// 	'Shifts' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'shifts']],
			// 	'Sections' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'sections']],
			// 	'Classes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'classes']],
			// 	'Infrastructures' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'infrastructures']]
			// ];
		} else if ($controller->name == 'Staff' && $action != 'index') {
			$navigations['items']['Staff']['items'] = [
			'General' => [
								'collapse' => true,
								'items' => [
									'Overview' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'view']],
									'Contacts' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Contacts']],
									'Identities' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Identities']],
									'Languages' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Languages']],
									'Comments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Comments']],
									'Special Needs' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'SpecialNeeds']],
									'Awards' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Awards']],
									'Attachments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Attachments']],
									'History' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'History']]
									// 'More' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'additional']]
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
				],
				// 'Health' => [
				// 	'collapse' => true,
				// 	'items' => [
				// 		'<placeholder>' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'BankAccounts']]
				// 	]
				// ],
				// 'Training' => [
				// 	'collapse' => true,
				// 	'items' => [
				// 		'<placeholder>' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'BankAccounts']]
				// 	]
				// ]


// 							'Details' => [
// 								'collapse' => true,
// 								// 'items' => [
// 								// 	'Guardians' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Guardians']],
// 								// 	'Programmes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Programmes']],
// 								// 	'Sections' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Sections']],
// 								// 	'Classes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Classes']],
// 								// 	'Absence' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Absence']],
// 								// 	'Behaviour' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Behaviour']],
// 								// 	'Results' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Results']],
// 								// 	'Extracurricular' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Extracurricular']]
// // <a href="/core/Students/guardians" >Guardians
// // <a href="/core/Students/Programme" >Programmes
// // <a href="/core/Students/StudentSection" >Sections
// // <a href="/core/Students/classes" >Classes
// // <a href="/core/Students/Absence" >Absence
// // <a href="/core/Students/StudentBehaviour" >Behaviour
// // <a href="/core/Students/assessments" >Results
// // <a href="/core/Students/extracurricular" >Extracurricular
// 								// ]
// 							],
// 							'Finance' => [
// 								'collapse' => true,
// 								// 'items' => [
// 								// 	'Bank Accounts' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'BankAccounts']],
// 								// 	'Fees' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'StudentFee']],
// // <a href="/core/Students/bankAccounts" >Bank Accounts
// // <a href="/core/Students/StudentFee" >Fees
// 								// ]
// 							]
			];
		} else {
			/*
			$navigations = [
				'collapse' => false,
				'items' => [
					'Institutions' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Institutions', 'action' => 'index'],
						'items' => [
							'Overview' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id]],
							'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'attachments']],
							'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'attachments']],
							'Positions' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'positions']],
							'Programmes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'programmes']],
							'Shifts' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'shifts']],
							'Sections' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'sections']],
							'Classes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'classes']],
							'Infrastructures' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'infrastructures']]
						]
					],
					'Students' => [
						'collapse' => true,
						'items' => [
							'General' => [
								'collapse' => true,
								'items' => [
									'Overview' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'view']],
									'Contacts' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Contacts']],
									'Identities' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Identities']],
									'Languages' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Languages']],
									'Comments' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Comments']],
									'Special Needs' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'SpecialNeeds']],
									'Awards' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Awards']],
									'Attachments' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Attachments']],
									'More' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'additional']]
								]
							],
							'Details' => [
								'collapse' => true,
								'items' => [
									'Guardians' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']],
									'Programmes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Programmes']],
									'Sections' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Sections']],
									'Classes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Classes']],
									'Absence' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Absence']],
									'Behaviour' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Behaviour']],
									'Results' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Results']],
									'Extracurricular' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Extracurricular']]
// <a href="/core/Students/guardians" >Guardians
// <a href="/core/Students/Programme" >Programmes
// <a href="/core/Students/StudentSection" >Sections
// <a href="/core/Students/classes" >Classes
// <a href="/core/Students/Absence" >Absence
// <a href="/core/Students/StudentBehaviour" >Behaviour
// <a href="/core/Students/assessments" >Results
// <a href="/core/Students/extracurricular" >Extracurricular
								]
							],
							'Finance' => [
								'collapse' => true,
								'items' => [
									'Bank Accounts' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'BankAccounts']],
									'Fees' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'StudentFee']],
// <a href="/core/Students/bankAccounts" >Bank Accounts
// <a href="/core/Students/StudentFee" >Fees
								]
							]
						]
					],
					'Staff' => [
						'collapse' => true,
						'items' => [
							'General' => [
								'collapse' => true,
								'items' => [
									'Overview' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'view']],
									'Contacts' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Contacts']],
									'Identities' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Identities']],
									'Languages' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Languages']],
									'Comments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Comments']],
									'Special Needs' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'SpecialNeeds']],
									'Awards' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Awards']],
									'Attachments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Attachments']],
									'More' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'additional']]
								]
							],
// 							'Details' => [
// 								'collapse' => true,
// 								// 'items' => [
// 								// 	'Guardians' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Guardians']],
// 								// 	'Programmes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Programmes']],
// 								// 	'Sections' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Sections']],
// 								// 	'Classes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Classes']],
// 								// 	'Absence' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Absence']],
// 								// 	'Behaviour' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Behaviour']],
// 								// 	'Results' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Results']],
// 								// 	'Extracurricular' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Extracurricular']]
// // <a href="/core/Students/guardians" >Guardians
// // <a href="/core/Students/Programme" >Programmes
// // <a href="/core/Students/StudentSection" >Sections
// // <a href="/core/Students/classes" >Classes
// // <a href="/core/Students/Absence" >Absence
// // <a href="/core/Students/StudentBehaviour" >Behaviour
// // <a href="/core/Students/assessments" >Results
// // <a href="/core/Students/extracurricular" >Extracurricular
// 								// ]
// 							],
// 							'Finance' => [
// 								'collapse' => true,
// 								// 'items' => [
// 								// 	'Bank Accounts' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'BankAccounts']],
// 								// 	'Fees' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'StudentFee']],
// // <a href="/core/Students/bankAccounts" >Bank Accounts
// // <a href="/core/Students/StudentFee" >Fees
// 								// ]
// 							]
						]
					]
				]
			];
		*/
		}
		$controller->set('_navigations', $navigations);
	}
}

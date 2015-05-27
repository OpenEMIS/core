<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;

class NavigationComponent extends Component {
	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public $controller;
	public $action;

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];
	}

	public function beforeRender(Event $event) {
		$controller = $this->controller;
		$action = $this->action;
		$id = $this->request->param('id');

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
					'url' => ['plugin' => 'Staff', 'controller' => 'Areas', 'action' => 'index']
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
									'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'index']
								],
								'Academic Periods' => [
									'collapse' => true,
									'url' => ['plugin' => false, 'controller' => 'AcademicPeriods', 'action' => 'index']
								],
								'Education Structure' => [
									'collapse' => true,
									'url' => ['plugin' => false, 'controller' => 'Education', 'action' => 'index']
								],
								'Infrastructure' => [
									'collapse' => true,
									'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'levels']
								],
								'Assessments' => [
									'collapse' => true,
									'url' => ['plugin' => false, 'controller' => 'Assessments', 'action' => 'levels']
								],
								'Field Options' => [
									'collapse' => true,
									'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'levels']
								],
								'Translations' => [
									'collapse' => true,
									'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'levels']
								],
								'System Configurations' => [
									'collapse' => true,
									'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'levels']
								],
								'Notices' => [
									'collapse' => true,
									'url' => ['plugin' => false, 'controller' => 'Notices', 'action' => 'index']
								]
							]
						]
					]
				]
			]
		];

		if ($controller->name == 'Institutions' && $action != 'index') {
			$navigations['items']['Institutions']['items'] = [
				'Overview' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id]],
				'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'attachments']],
				'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'attachments']],
				'Positions' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'positions']],
				'Programmes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'programmes']],
				'Shifts' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'shifts']],
				'Sections' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'sections']],
				'Classes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'classes']],
				'Infrastructures' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'infrastructures']]
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
					],
					'Administration' => [
						'collapse' => true,
						'items' => [
							'System Setup' => [
								'collapse' => true,
								'items' => [
									'Administrative Boundaries' => [
										'collapse' => true,
										'items' => [
											'Area Levels (Education)' => ['url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'Areas']],
											'Areas (Education)' => ['url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'AreaLevels']],
											'Area Levels (Administrative)' => ['url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'AreaAdministratives']],
											'Areas (Administrative)' => ['url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'AreaAdministrativeLevels']]
										]
									],
									'Education Structure' => [
										'collapse' => true,
										'items' => [
											'Systems' => ['url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Systems']],
											'Levels' => ['url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Levels']],
											'Cycles' => ['url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Cycles']],
											'Programmes' => ['url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Programmes']],
											'Grades' => ['url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Grades']],
											'Subjects' => ['url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Subjects']]
										]
									],
									'Infrastructures' => [
										'collapse' => true,
										'items' => [
											'Levels' => ['url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Levels']],
											'Types' => ['url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Types']]
										]
									],
									'Notices' => ['url' => ['plugin' => false, 'controller' => 'Notices', 'action' => 'index']]
								]
							],
							'Security' => [
								'collapse' => true,
								'items' => [
									'Users' => ['url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'Users']],
									'Groups' => ['url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'Groups']],
									'Roles' => ['url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'Roles']]
								]
							],
							'Survey' => [
								'collapse' => true,
								'items' => [
									'Templates' => ['url' => ['plugin' => 'Survey', 'controller' => 'SurveyTemplates', 'action' => 'index']],
									'Questions' => ['url' => ['plugin' => 'Survey', 'controller' => 'SurveyQuestions', 'action' => 'index']],
									'Status' => ['url' => ['plugin' => 'Survey', 'controller' => 'SurveyStatuses', 'action' => 'index']]
								]
							],
							'Rubric' => [
								'collapse' => true,
								'items' => [
									'Rubrics' => ['url' => ['plugin' => false, 'controller' => 'Rubrics', 'action' => 'Templates']],
									'Status' => ['url' => ['plugin' => false, 'controller' => 'RubricStatuses', 'action' => 'index']]
								]
							],
							'Workflow' => [
								'collapse' => true,
								'items' => [
									'Workflows' => ['url' => ['plugin' => false, 'controller' => 'Workflows', 'action' => 'index']],
									'Steps' => ['url' => ['plugin' => false, 'controller' => 'WorkflowSteps', 'action' => 'index']]
								]
							],
						]
					]
				]
			];
		*/
		}
		$controller->set('_navigations', $navigations);
	}
}

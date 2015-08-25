<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;

class NavigationComponent extends Component {
	public $controller;
	public $action;
	public $breadcrumbs = [];

	public $components = ['AccessControl'];

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
		$this->controller->set('_breadcrumbs', $this->breadcrumbs);
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

	public function beforeFilter(Event $event) {
		$controller = $this->controller;
		
		$navigations = $this->buildNavigation();
		$this->checkPermissions($navigations);

		$controller->set('_navigations', $navigations);
	}

	public function checkPermissions(&$navigations) {
		if (array_key_exists('items', $navigations)) {
			foreach ($navigations['items'] as $name => $attr) {
				$access = true;
				if (array_key_exists('url', $attr)) {
					$url = $attr['url'];
					
					if ($this->AccessControl->check($url) == false) {
						$access = false;
					}
				}
				if ($access) {
					if (array_key_exists('items', $attr)) {
						$items = $this->checkPermissions($attr);
						
						if (empty($items['items'])) {
							unset($navigations['items'][$name]);
						} else {
							$navigations['items'][$name] = $items;
						}
					}
				} else {
					unset($navigations['items'][$name]);
				}
			}
		}
		return $navigations;
	}

	public function buildNavigation() {
		$navigations = $this->getNavigation();

		$controller = $this->controller;
		$action = $this->action;

		if ($controller->name == 'Institutions' && $action != 'index') {
			$navigations['items']['Institutions']['items'] = $this->getInstitutionNavigation();
		} else if ($controller->name == 'Students' && $action != 'index') {
			$navigations['items']['Students']['items'] = $this->getStudentNavigation();
		} else if ($controller->name == 'Staff' && $action != 'index') {
			$navigations['items']['Staff']['items'] = $this->getStaffNavigation();
		} else if ($controller->name == 'Guardians' && $action != 'index') {
			$navigations['items']['Guardians']['items'] = $this->getGuardianNavigation();
		} else {
			// do nothing
		}
		$navigations['items']['Reports']['items'] = $this->getReportNavigation();
		$navigations['items']['Administration']['items'] = $this->getAdministrationNavigation();

		return $navigations;
	}

	public function getNavigation() {
		$navigation = [
			'collapse' => false,
			'items' => [
				'Institutions' => [
					'icon' => '<i class="fa kd-institutions"></i>',
					'collapse' => true,
					'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']
				],
				'Students' => [
					'icon' => '<i class="fa kd-students"></i>',
					'collapse' => true,
					'url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']
				],
				'Staff' => [
					'icon' => '<i class="fa kd-staff"></i>',
					'collapse' => true,
					'url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']
				],
				'Guardians' => [
					'icon' => '<i class="fa kd-guardian"></i>',
					'collapse' => true,
					'url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']
				],

				'Reports' => [
					'icon' => '<i class="fa kd-reports"></i>',
					'collapse' => true
				],
				
				'Administration' => [
					'icon' => '<i class="fa fa-cogs"></i>',
					'collapse' => true
				]
			]
		];

		return $navigation;
	}

	public function getInstitutionNavigation() {
		$session = $this->request->session();
		$id = $session->read('Institutions.id');

		$navigation = [
			'Dashboard' => [
				'collapse' => true,
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $id]
			],
			'General' => [
				'collapse' => true,
				'items' => [
					'Overview' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id], 'selected' => ['edit']],
					'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Attachments']],
					'History' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'History']]
				]
			],
			'Academic' => [
				'collapse' => true,
				'items' => [
					'Shifts' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Shifts']],
					'Programmes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Programmes']],
					'Classes' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Sections']],
					'Subjects' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes']],
				]
			],

			'Students' => [
				'collapse' => true,
				'items' => [
					// 'Admission' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAdmission'], 'selected' => ['StudentAdmission']],
					'List' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'], 'selected' => ['TransferRequests', 'StudentUser', 'Promotion', 'StudentAdmission', 'TransferApprovals']],
					'Behaviour' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentBehaviours']],
					'Attendance' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAttendances'], 'selected' => ['StudentAttendances', 'StudentAbsences']],
					'Results' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Assessments'], 'selected' => ['Assessments', 'Results']],
					// 'Fees' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentFees']]
				]
			],

			'Staff' => [
				'collapse' => true,
				'items' => [
					'List' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Staff'], 'selected' => ['StaffUser']],
					'Positions' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Positions'], 'selected' => ['StaffPositions']],
					'Behaviour' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffBehaviours']],
					'Attendance' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAttendances'], 'selected' => ['StaffAttendances', 'StaffAbsences']]
				]
			],

			'Infrastructures' => [
				'collapse' => true,
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Infrastructures']
			],

			'Finance' => [
				'collapse' => true,
				'items' => [
					'Bank Accounts' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'BankAccounts']],
					'Fees' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Fees']]
				]
			],

			'Survey' => [
				'collapse' => true,
				'items' => [
					'Forms' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys']],
					'Rubrics' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Rubrics']]
				]
			],

			'Visits' => [
				'collapse' => true,
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Visits']
			]
		];

		return $navigation;
	}

	public function getStudentNavigation() {
		$session = $this->request->session();
		$id = $session->read('Students.id');

		$navigation = [
			'General' => [
				'collapse' => true,
				'items' => [
					'Overview' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'view', $id], 'selected' => ['edit', 'Accounts']],
					'Identities' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Identities']],
					'Nationalities' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Nationalities']],
					'Contacts' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Contacts']],
					'Guardians' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Guardians']],
					'Languages' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Languages']],
					'Special Needs' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'SpecialNeeds']],
					'Attachments' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Attachments']],
					'Comments' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Comments']],
					'History' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'History']]
				]
			],
			'Academic' => [
				'collapse' => true,
				'items' => [
					'Programmes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Programmes']],
					'Classes' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Sections']],
					'Subjects' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Classes']],
					'Absences' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Absences']],
					'Behaviours' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Behaviours']],
					'Results' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Results']],
					'Awards' => ['url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Awards']],
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

		return $navigation;
	}

	public function getStaffNavigation() {
		$session = $this->request->session();
		$id = $session->read('Staff.id');

		$navigation = [
			'General' => [
				'collapse' => true,
				'items' => [
					'Overview' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'view', $id], 'selected' => ['edit', 'add', 'Accounts']],
					'Identities' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Identities']],
					'Nationalities' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Nationalities']],
					'Contacts' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Contacts']],
					'Languages' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Languages']],
					'Special Needs' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'SpecialNeeds']],
					'Attachments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Attachments']],
					'Comments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Comments']],
					'History' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'History']]
				]
			],
			'Career' => [
				'collapse' => true,
				'items' => [
					'Employments' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Employments']],
					'Positions' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Positions']],
					'Classes' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Sections']],
					'Subjects' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Classes']],
					'Absences' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Absences']],
					'Leave' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Leaves']],
					'Behaviours' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Behaviours']],
					'Awards' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Awards']]
				]
			],
			'Professional Development' => [
				'collapse' => true,
				'items' => [
					'Qualifications' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Qualifications']],
					'Extracurriculars' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Extracurriculars']],
					'Memberships' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Memberships']],
					'Licenses' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Licenses']],
					'Trainings' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Trainings']]
				]
			],
			'Finance' => [
				'collapse' => true,
				'items' => [
					'Bank Accounts' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'BankAccounts']],
					'Salaries' => ['url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Salaries']]
				]
			]
		];

		return $navigation;
	}

	public function getGuardianNavigation() {
		$session = $this->request->session();
		$id = $session->read('Guardians.id');

		$navigation = [
			'General' => [
				'collapse' => true,
				'items' => [
					'Overview' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'view', $id]],
					'Contacts' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'Contacts']],
					'Identities' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'Identities']],
					'Nationalities' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'Nationalities']],
					'Languages' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'Languages']],
					'Comments' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'Comments']],
					'Attachments' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'Attachments']],
					'History' => ['url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'History']]
				]
			],
		];
		return $navigation;
	}

	public function getReportNavigation() {
		$navigation = [
			'Institution' => ['url' => ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'Institutions']],
			'Student' => ['url' => ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'Students']],
			'Staff' => ['url' => ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'Staff']]
		];
		return $navigation;
	}

	public function getAdministrationNavigation() {
		$navigation = [
			'System Setup' => [
				'collapse' => true,
				'items' => [
					'Administrative Boundaries' => [
						'collapse' => true,
						'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'Areas', 'index'],
						'selected' => ['Levels', 'AdministrativeLevels', 'Administratives']
					],
					'Academic Periods' => [
						'collapse' => true,
						'url' => ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => 'Periods', 'index'],
						'selected' => ['Levels']
					],
					'Education Structure' => [
						'collapse' => true,
						'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Systems', 'index'],
						'selected' => ['Levels', 'Cycles', 'Programmes', 'Grades', 'Subjects', 'Certifications', 'FieldOfStudies', 'ProgrammeOrientations']
					],
					'Assessments' => [
						'collapse' => true,
						'url' => ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => 'Assessments', 'index'],
						'selected' => ['GradingTypes', 'GradingOptions', 'Status']
					],
					'Field Options' => [
						'collapse' => true,
						'url' => ['plugin' => 'FieldOption', 'controller' => 'FieldOptions', 'action' => 'index'],
						'selected' => ['index', 'add', 'view', 'edit', 'remove']
					],
					'Custom Field' => [
						'collapse' => true,
						'items' => [
							// 'General' => [
							// 	'collapse' => true,
							// 	'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Fields'],
							// 	'selected' => ['Pages']
							// ],
							'Institution' => [
								'collapse' => true,
								'url' => ['plugin' => 'InstitutionCustomField', 'controller' => 'InstitutionCustomFields', 'action' => 'Fields'],
								'selected' => ['Pages']
							],
							'Student' => [
								'collapse' => true,
								'url' => ['plugin' => 'StudentCustomField', 'controller' => 'StudentCustomFields', 'action' => 'Fields'],
								'selected' => ['Pages']
							],
							'Staff' => [
								'collapse' => true,
								'url' => ['plugin' => 'StaffCustomField', 'controller' => 'StaffCustomFields', 'action' => 'Fields'],
								'selected' => ['Pages']
							],
							'Infrastructure' => [
								'collapse' => true,
								'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Fields'],
								'selected' => ['Pages', 'Levels', 'Types']
							],
						]
					],
					'Translations' => [
						'collapse' => true,
						'url' => ['plugin' => 'Localization', 'controller' => 'Translations', 'action' => 'index'],
						'selected' => ['add', 'view', 'edit']
					],
					'System Configurations' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Configurations', 'action' => 'index'],
						'selected' => ['add', 'view', 'edit']
					],
					'Notices' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Notices', 'action' => 'index'],
						'selected' => ['add', 'view', 'edit']
					]
				]
			],
			'Security' => [
				'collapse' => true,
				'items' => [
					'Users' => [
						'collapse' => true,
						'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'Users'],
						'selected' => ['Accounts']
					],
					'Groups' => [
						'collapse' => true,
						'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'UserGroups'],
						'selected' => ['UserGroups', 'SystemGroups']
					],
					'Roles' => [
						'collapse' => true,
						'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'Roles'],
						'selected' => ['Roles', 'Permissions']
					]
				]
			],
			'Survey' => [
				'collapse' => true,
				'items' => [
					'Forms' => [
						'collapse' => true,
						'url' => ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => 'Questions'],
						'selected' => ['Questions', 'Forms', 'Status']
					],
					'Rubrics' => [
						'collapse' => true,
						'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Templates'],
						'selected' => ['Sections', 'Criterias', 'Options', 'Status']
					]
				],
			],
			'Communications' => [
				'collapse' => true,
				'items' => [
					'Questions' => [
						'collapse' => true,
						'url' => ['plugin' => 'Alert', 'controller' => 'Alerts', 'action' => 'Questions']
					],
					'Responses' => [
						'collapse' => true,
						'url' => ['plugin' => 'Alert', 'controller' => 'Alerts', 'action' => 'Responses']
					],
					'Logs' => [
						'collapse' => true,
						'url' => ['plugin' => 'Alert', 'controller' => 'Alerts', 'action' => 'Logs']
					]
				]
			],
			'Workflow' => [
				'collapse' => true,
				'url' => ['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'Workflows'],
				'selected' => ['Steps']
			]
		];
		return $navigation;
	}
}

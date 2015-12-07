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
		// $this->checkPermissions($navigations);
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
		// $navigations = $this->getNavigation();
		$navigations = $this->getMainNavigation();

		$controller = $this->controller;
		$action = $this->action;
		$pass = [];
		if (!empty($this->request->pass)) {
			$pass = $this->request->pass;
		} else {
			$pass[0] = '';
		}

		$institutionStudentActions = ['StudentUser', 'StudentAccount', 'StudentSurveys', 'Students'];
		$institutionStaffActions = ['Staff', 'StaffUser', 'StaffAccount'];
		$institutionActions = array_merge($institutionStudentActions, $institutionStaffActions);

		if (($controller->name == 'Institutions' && $action != 'index' && (!in_array($action, $institutionActions))) || 
			($controller->name == 'Institutions' && ($action == 'Students'||$action == 'Staff') && ($pass[0] == 'index' || $pass[0] == 'add'))) {
			$navigations = $this->appendNavigation('Institutions.index', $navigations, $this->getInstitutionNavigation());
		} elseif (($controller->name == 'Students' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStudentActions))) {
			$navigations = $this->appendNavigation('Institutions.index', $navigations, $this->getInstitutionNavigation());
			$navigations = $this->appendNavigation('Institutions.Students.index', $navigations, $this->getInstitutionStudentNavigation());
		} elseif (($controller->name == 'Staff' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStaffActions))) {
			$navigations = $this->appendNavigation('Institutions.index', $navigations, $this->getInstitutionNavigation());
			$navigations = $this->appendNavigation('Institutions.Staff.index', $navigations, $this->getInstitutionStaffNavigation());
		}	elseif ($controller->name == 'Guardians' && $action != 'index') {
			$navigations = $this->appendNavigation('Guardians.index', $navigations, $this->getGuardianNavigation());;
		}

		// if (($controller->name == 'Institutions' && $action != 'index' && (!in_array($action, $institutionActions))) 
		// 	|| ($controller->name == 'Institutions' && ($action == 'Students'||$action == 'Staff') && $pass[0] == 'index')) {
		// 	$navigations['items']['Institutions']['items'] = $this->getInstitutionNavigation();
		// } elseif (($controller->name == 'Students' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStudentActions))) {
		// 	$navigations['items']['Institutions']['items'] = $this->getInstitutionNavigation();
		// 	$navigations['items']['Institutions']['items']['Students']['items'] = $this->getStudentNavigation();
		// } elseif (($controller->name == 'Staff' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStaffActions))) {
		// 	$navigations['items']['Institutions']['items'] = $this->getInstitutionNavigation();
		// 	$navigations['items']['Institutions']['items']['Staff']['items'] = $this->getStaffNavigation();
		// } elseif ($controller->name == 'Guardians' && $action != 'index') {
		// 	$navigations['items']['Guardians']['items'] = $this->getGuardianNavigation();
		// } else {
		// 	// do nothing
		// }
		// $navigations['items']['Reports']['items'] = $this->getReportNavigation();
		// $navigations['items']['Administration']['items'] = $this->getAdministrationNavigation();

		return $navigations;
	}

	private function appendNavigation($key, $originalNavigation, $navigationToAppend) {
		$count = 0;
		foreach ($originalNavigation as $navigationKey => $navigationValue) {
			$count++;
			if ($navigationKey == $key) {
				break;
			}
		}
		$result = [];
		if ($count < count($originalNavigation)) {
			$result = array_slice($originalNavigation, 0, $count, true) + $navigationToAppend + array_slice($originalNavigation, $count, count($originalNavigation) - 1, true) ;
		} elseif ($count == count($originalNavigation)) {
			$result = $originalNavigation + $navigationToAppend;
		} else {
			$result = $originalNavigation;
		}
		return $result;
	}

	public function getNavigation() {
		$navigation = [
			'collapse' => false,
			'items' => [
				'Institutions' => [
					'icon' => '<span><i class="fa kd-institutions"></i></span>',
					'collapse' => true,
					'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']
				],
				'Guardians' => [
					'icon' => '<span><i class="fa kd-guardian"></i></span>',
					'collapse' => true,
					'url' => ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']
				],

				'Reports' => [
					'icon' => '<span><i class="fa kd-reports"></i></span>',
					'collapse' => true
				],
				
				'Administration' => [
					'icon' => '<span><i class="fa fa-cogs"></i></span>',
					'collapse' => true
				]
			]
		];

		return $navigation;
	}

	public function getInstitutionNavigation() {
		$session = $this->request->session();
		$id = $session->read('Institution.Institutions.id');
		$navigation = [
			'Institutions.dashboard' => [
				'title' => 'Dashboard', 
				'parent' => 'Institutions.index', 
				'selected' => ['Institutions.dashboard'],
				'params' => ['plugin' => 'Institution']
			],

			'Institution.General' => [
				'title' => 'General', 
				'parent' => 'Institutions.index', 
				'link' => false
			],
			
				'Institutions.view' => [
					'title' => 'Overview', 
					'parent' => 'Institution.General', 
					'selected' => ['Institutions.view'], 
					'params' => ['plugin' => 'Institution']
				],

				'Institutions.Attachments.index' => [
					'title' => 'Attachments', 
					'parent' => 'Institution.General', 
					'selected' => ['Institutions.Attachments'],
					'params' => ['plugin' => 'Institution']
				],

				'Institutions.History.index' => [
					'title' => 'History', 
					'parent' => 'Institution.General', 
					'selected' => ['Institutions.History'],
					'params' => ['plugin' => 'Institution']
				],
	
			'Institution.Academic' => [
				'title' => 'Academic', 
				'parent' => 'Institutions.index', 
				'link' => false
			],

				'Institutions.Shifts' => [
					'title' => 'Shifts',
					'parent' => 'Institution.Academic',
					'selected' => ['Institution.Shifts'],
					'params' => ['plugin' => 'Institution']
				],

				'Institutions.Classes' => [
					'title' => 'Classes',
					'parent' => 'Institution.Academic',
					'selected' => ['Institution.Classes'],
					'params' => ['plugin' => 'Institution']
				],

				'Institutions.Subjects' => [
					'title' => 'Subjects',
					'parent' => 'Institution.Academic',
					'selected' => ['Institution.Subjects'],
					'params' => ['plugin' => 'Institution']
				],

			'Institutions.Students.index' => [
				'title' => 'Students',
				'parent' => 'Institutions.index',
				'selected' => ['Institutions.Students.add', 'Institutions.TransferRequests', 'Institutions.Promotion', 'Institutions.Transfer', 
					'Institutions.StudentAdmission', 'Institutions.TransferApprovals', 'Institutions.StudentDropout', 'Institutions.DropoutRequests'],
				'params' => ['plugin' => 'Institution']
			],

			'Institutions.Staff.index' => [
				'title' => 'Staff',
				'parent' => 'Institutions.index',
				'params' => ['plugin' => 'Institution'],
				'selected' => ['Institutions.Staff.add']
			],

			'Institution.Attendance' => [
				'title' => 'Attendance',
				'parent' => 'Institutions.index',
				'link' => false
			],

				'Institutions.StudentAttendances.index' => [
					'title' => 'Students',
					'parent' => 'Institution.Attendance',
					'selected' => ['Institutions.StudentAttendances', 'Institutions.StudentAbsences'],
					'params' => ['plugin' => 'Institution']
				],

				'Institutions.StaffAttendances.index' => [
					'title' => 'Staff',
					'parent' => 'Institution.Attendance',
					'selected' => ['Institutions.StaffAttendances', 'Institutions.StaffAbsences'],
					'params' => ['plugin' => 'Institution']
				],

			'Institution.Behaviour' => [
				'title' => 'Behaviour',
				'parent' => 'Institutions.index',
				'link' => false
			],

				'Institutions.StudentBehaviours.index' => [
					'title' => 'Students',
					'parent' => 'Institution.Behaviour',
					'selected' => ['Institutions.StudentBehaviours'],
					'params' => ['plugin' => 'Institution']
				],

				'Institutions.StaffBehaviours.index' => [
					'title' => 'Staff',
					'parent' => 'Institution.Behaviour',
					'selected' => ['Institutions.StaffBehaviours'],
					'params' => ['plugin' => 'Institution']
				],

			'Institutions.Assessments.index' => [
				'title' => 'Results',
				'parent' => 'Institutions.index',
				'selected' => ['Institutions.Assessments', 'Institutions.Results'],
				'params' => ['plugin' => 'Institution'],
			],	
			
			'Institutions.Positions' => [
				'title' => 'Positions',
				'parent' => 'Institutions.index',
				'params' => ['plugin' => 'Institution'],
				'selected' => ['Institutions.Positions', 'Institutions.StaffPositions'],
			],

			'Institution.Finance' => [
				'title' => 'Finance',
				'parent' => 'Institutions.index', 
				'link' => false
			],
				
				'Institutions.BankAccounts' => [
					'title' => 'Bank Accounts',
					'parent' => 'Institution.Finance', 
					'params' => ['plugin' => 'Institution'],
					'selected' => ['Institutions.BankAccounts'],
				],

				'Institutions.Fees' => [
					'title' => 'Bank Accounts',
					'parent' => 'Institution.Finance', 
					'params' => ['plugin' => 'Institution'],
					'selected' => ['Institutions.Fees'],
				],

				'Institutions.StudentFees' => [
					'title' => 'Bank Accounts',
					'parent' => 'Institution.Finance', 
					'params' => ['plugin' => 'Institution'],
					'selected' => ['Institutions.StudentFees'],
				],

			'Institutions.Infrastructures' => [
				'title' => 'Forms',
				'parent' => 'Institutions.index', 
				'params' => ['plugin' => 'Institution'],
				'selected' => ['Institutions.Infrastructures']
			],

			'Survey' => [
				'title' => 'Survey',
				'parent' => 'Institutions.index', 
				'link' => false
			],
				'Institutions.Surveys' => [
					'title' => 'Forms',
					'parent' => 'Survey', 
					'params' => ['plugin' => 'Institution'],
					'selected' => ['Institutions.Surveys'],
				],

				'Institutions.Rubrics' => [
					'title' => 'Rubrics',
					'parent' => 'Survey', 
					'params' => ['plugin' => 'Institution'],
					'selected' => ['Institutions.Rubrics', 'Institutions.RubricAnswers'],
				],

			'Institutions.Visits' => [
				'title' => 'Visits',
				'parent' => 'Institutions.index', 
				'params' => ['plugin' => 'Institution'],
				'selected' => ['Institutions.Visits']
			]
		];

		return $navigation;
	}

	public function getMainNavigation() {
		$navigation = [
			'Institutions.index' => [
				'title' => 'Institutions', 
				'icon' => '<span><i class="fa kd-institutions"></i></span>',
				'params' => ['plugin' => 'Institution'],
			],
			'Guardians.index' => [
				'title' => 'Guardians', 
				'icon' => '<span><i class="fa kd-guardian"></i></span>',
				'params' => ['plugin' => 'Guardian'],
				'selected' => ['Guardians.add'],
			],

			'Reports' => [
				'title' => 'Reports', 
				'icon' => '<span><i class="fa kd-reports"></i></span>',
				'link' => false
			],
			
			'Administration' => [
				'title' => 'Administration', 
				'icon' => '<span><i class="fa fa-cogs"></i></span>',
				'link' => false
			]
		];

		return $navigation;
	}

	public function getInstitutionStudentNavigation() {
		$session = $this->request->session();
		$id = $session->read('Institution.Students.id');
		$studentId = $session->read('Student.Students.id');
		$navigation = [
			'Institutions.StudentUser.view' => [
				'title' => 'General', 
				'parent' => 'Institutions.Students.index', 
				'params' => ['plugin' => 'Institution', '1' => $studentId, 'id' => $id], 
				'selected' => ['Institutions.StudentUser.edit', 'Institutions.StudentAccount.view', 'Institutions.StudentAccount.edit', 'Institutions.StudentSurveys.view', 'Institutions.StudentSurveys.edit', 
					'Students.Identities', 'Students.Nationalities', 'Students.Contacts', 'Students.Guardians', 'Students.Languages', 'Students.SpecialNeeds', 'Students.Attachments', 'Students.Comments', 
					'Students.History', 'Students.GuardianUser']],
			'Students.Programmes.index' => [
				'title' => 'Academic', 
				'parent' => 'Institutions.Students.index', 
				'params' => ['plugin' => 'Student'], 
				'selected' => ['Institutions.Students.view', 'Students.Programmes.index', 'Students.Sections', 'Students.Classes', 'Students.Absences', 'Students.Behaviours', 'Students.Results', 'Students.Awards', 
					'Students.Extracurriculars']],
			'Students.BankAccounts' => [
				'title' => 'Finance', 
				'parent' => 'Institutions.Students.index',
				'params' => ['plugin' => 'Student'],
				'selected' => ['Students.StudentFees']],
		];
		return $navigation;
	}

	// public function getStudentNavigation() {
	// 	$session = $this->request->session();
	// 	$id = $session->read('Institution.Students.id');
	// 	$studentId = $session->read('Student.Students.id');
	// 	$navigation = [
	// 		'General' => [
	// 			'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $studentId, 'id' => $id],
	// 			'selected' => ['Institutions.StudentUser', 'Institutions.StudentAccount', 'Institutions.StudentSurveys', 'Students.Identities', 'Students.Nationalities', 
	// 				'Students.Contacts', 'Students.Guardians', 'Students.Languages', 'Students.SpecialNeeds', 'Students.Attachments', 'Students.Comments', 'Students.History', 
	// 				'Students.GuardianUser']
	// 		],
	// 		'Academic' => [
	// 			'url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Programmes', 'index'], 
	// 			'selected' => ['Institutions.Students.view', 'Students.Programmes.index', 'Sections', 'Classes', 'Absences', 'Behaviours', 'Results', 'Awards', 'Extracurriculars'],
	// 		],
	// 		'Finance' => [
	// 			'url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'BankAccounts'], 
	// 			'selected' => ['StudentFees'],
	// 		],
	// 	];
	// 	return $navigation;
	// }

	public function getInstitutionStaffNavigation() {
		$session = $this->request->session();
		$id = $session->read('Staff.Staff.id');
		$navigation = [
			'Institutions.StaffUser.view' => [
				'title' => 'General', 
				'parent' => 'Institutions.Staff.index', 
				'params' => ['plugin' => 'Institution', '1' => $id], 
				'selected' => ['Institutions.StaffUser.edit', 'Institutions.StaffAccount', 'Staff.Identities', 'Staff.Nationalities', 
					'Staff.Contacts', 'Staff.Guardians', 'Staff.Languages', 'Staff.SpecialNeeds', 'Staff.Attachments', 'Staff.Comments', 'Staff.History']
			],
			'Staff.Employments' => [
				'title' => 'Career', 
				'parent' => 'Institutions.Staff.index', 
				'params' => ['plugin' => 'Staff'], 
				'selected' => ['Staff.Employments', 'Staff.Positions', 'Staff.Sections', 'Staff.Classes', 'Staff.Absences', 
					'Staff.Leaves', 'Staff.Behaviours', 'Staff.Awards'],
			],
			'Staff.Qualifications' => [
				'title' => 'Professional Development', 
				'parent' => 'Institutions.Staff.index', 
				'params' => ['plugin' => 'Staff'], 
				'selected' => ['Staff.Qualifications', 'Staff.Extracurriculars', 'Staff.Memberships', 'Staff.Licenses', 'Staff.Trainings'],
			],
			'Staff.BankAccounts' => [
				'title' => 'Finance', 
				'parent' => 'Institutions.Staff.index', 
				'params' => ['plugin' => 'Staff'], 
				'selected' => ['Staff.BankAccounts', 'Staff.Salaries'],
			],
			'Staff.TrainingResults' => [
				'title' => 'Training', 
				'parent' => 'Institutions.Staff.index', 
				'params' => ['plugin' => 'Staff'], 
				'selected' => ['Staff.TrainingResults'],
			],
		];
		return $navigation;
	}

	public function getGuardianNavigation() {
		$session = $this->request->session();
		$id = $session->read('Guardian.Guardians.id');

		$navigation = [
			'Guardians.view' => [
				'title' => 'General', 
				'parent' => 'Guardians.index', 
				'params' => ['plugin' => 'Guardian'], 
				'selected' => ['Guardians.edit', 'Guardians.Accounts', 'Guardians.Contacts', 'Guardians.Identities', 'Guardians.Languages', 'Guardians.Comments', 'Guardians.Attachments', 'Guardians.History']
			],
		];
		return $navigation;
	}

	public function getReportNavigation() {
		$navigation = [
			'Institution' => ['url' => ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'Institutions']],
			'Student' => ['url' => ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'Students']],
			'Staff ' => ['url' => ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'Staff']],
			'Surveys' => ['url' => ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'Surveys']],
			'Quality' => ['url' => ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'InstitutionRubrics']],
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
						'selected' => ['Areas.Levels', 'Areas.AdministrativeLevels', 'Areas.Administratives']
					],
					'Academic Periods' => [
						'collapse' => true,
						'url' => ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => 'Periods', 'index'],
						'selected' => ['AcademicPeriods.Levels']
					],
					'Education Structure' => [
						'collapse' => true,
						'url' => ['plugin' => 'Education', 'controller' => 'Educations', 'action' => 'Systems', 'index'],
						'selected' => ['Educations.Levels', 'Educations.Cycles', 'Educations.Programmes', 'Educations.Grades', 'Educations.Subjects', 'Educations.Certifications', 
							'Educations.FieldOfStudies', 'Educations.ProgrammeOrientations']
					],
					'Assessments' => [
						'collapse' => true,
						'url' => ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => 'Assessments', 'index'],
						'selected' => ['Assessments.GradingTypes', 'Assessments.GradingOptions', 'Assessments.Status']
					],
					'Field Options' => [
						'collapse' => true,
						'url' => ['plugin' => 'FieldOption', 'controller' => 'FieldOptions', 'action' => 'index'],
						'selected' => ['FieldOptions.index', 'FieldOptions.add', 'FieldOptions.view', 'FieldOptions.edit', 'FieldOptions.remove']
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
								'selected' => ['InstitutionCustomFields.Pages']
							],
							'Student' => [
								'collapse' => true,
								'url' => ['plugin' => 'StudentCustomField', 'controller' => 'StudentCustomFields', 'action' => 'Fields'],
								'selected' => ['StudentCustomFields.Pages']
							],
							'Staff' => [
								'collapse' => true,
								'url' => ['plugin' => 'StaffCustomField', 'controller' => 'StaffCustomFields', 'action' => 'Fields'],
								'selected' => ['StaffCustomFields.Pages']
							],
							'Infrastructure' => [
								'collapse' => true,
								'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Fields'],
								'selected' => ['Infrastructures.Pages', 'Infrastructures.Levels', 'Infrastructures.Types']
							],
						]
					],
					'Labels' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Labels', 'action' => 'index'],
						'selected' => ['Labels.index', 'Labels.view', 'Labels.edit']
					],
					'Translations' => [
						'collapse' => true,
						'url' => ['plugin' => 'Localization', 'controller' => 'Translations', 'action' => 'index'],
						'selected' => ['Translations.add', 'Translations.view', 'Translations.edit']
					],
					'System Configurations' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Configurations', 'action' => 'index'],
						'selected' => ['Configurations.add', 'Configurations.view', 'Configurations.edit']
					],
					'Notices' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Notices', 'action' => 'index'],
						'selected' => ['Notices.add', 'Notices.view', 'Notices.edit']
					]
				]
			],
			'Security' => [
				'collapse' => true,
				'items' => [
					'Users' => [
						'collapse' => true,
						'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'Users'],
						'selected' => ['Securities.Accounts']
					],
					'Groups' => [
						'collapse' => true,
						'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'UserGroups'],
						'selected' => ['Securities.UserGroups', 'Securities.SystemGroups']
					],
					'Roles' => [
						'collapse' => true,
						'url' => ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'Roles'],
						'selected' => ['Securities.Roles', 'Securities.Permissions']
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
			'Training' => [
				'collapse' => true,
				'items' => [
					'Courses' => [
						'collapse' => true,
						'url' => ['plugin' => 'Training', 'controller' => 'Trainings', 'action' => 'Courses']
					],
					'Sessions' => [
						'collapse' => true,
						'url' => ['plugin' => 'Training', 'controller' => 'Trainings', 'action' => 'Sessions']
					],
					'Results' => [
						'collapse' => true,
						'url' => ['plugin' => 'Training', 'controller' => 'Trainings', 'action' => 'Results']
					]
				],
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

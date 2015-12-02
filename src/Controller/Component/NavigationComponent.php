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
		$pass = [];
		if (!empty($this->request->pass)) {
			$pass = $this->request->pass;
		} else {
			$pass[0] = '';
		}

		$institutionStudentActions = ['StudentUser', 'StudentAccount', 'StudentSurveys', 'Students'];
		$institutionStaffActions = ['Staff', 'StaffUser'];
		$institutionActions = array_merge($institutionStudentActions, $institutionStaffActions);

		if (($controller->name == 'Institutions' && $action != 'index' && (!in_array($action, $institutionActions))) 
			|| ($controller->name == 'Institutions' && ($action == 'Students'||$action == 'Staff') && $pass[0] == 'index')) {
			$navigations['items']['Institutions']['items'] = $this->getInstitutionNavigation();
		} elseif (($controller->name == 'Students' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStudentActions))) {
			$navigations['items']['Institutions']['items'] = $this->getInstitutionNavigation();
			$navigations['items']['Institutions']['items']['Students']['items'] = $this->getStudentNavigation();
		} elseif (($controller->name == 'Staff' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStaffActions))) {
			$navigations['items']['Institutions']['items'] = $this->getInstitutionNavigation();
			$navigations['items']['Institutions']['items']['Staff']['items'] = $this->getStaffNavigation();
		} elseif ($controller->name == 'Guardians' && $action != 'index') {
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
			'Dashboard' => [
				'collapse' => true,
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $id]
			],
			'General' => [
				'collapse' => true,
				'items' => [
					'Overview' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id], 
						'selected' => ['Institutions.edit']],
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
					// 'Admission' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAdmission'], 'selected' => ['StudentAdmission']],
				]
			],

			'Students' => [
				'collapse' => true,
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students', 'index'],
				'selected' => ['Institutions.Students.index', 'Institutions.TransferRequests', 'Institutions.Promotion', 'Institutions.Transfer', 
					'Institutions.StudentAdmission', 'Institutions.TransferApprovals', 'Institutions.StudentDropout', 'Institutions.DropoutRequests'],
			],

			'Staff' => [
				'collapse' => true,
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Staff', 'index']
			],

			'Attendance' => [
				'collapse' => true,
				'items' => [
					'Students' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAttendances'], 
						'selected' => ['Institutions.StudentAttendances', 'Institutions.StudentAbsences']],
					'Staff' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAttendances'], 
						'selected' => ['Institutions.StaffAttendances', 'Institutions.StaffAbsences']]
				]
			],

			'Behaviour' => [
				'collapse' => true,
				'items' => [
					'Students ' => [
						'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentBehaviours'],
						'selected' => ['Institutions.StudentBehaviours']
					],
					'Staff ' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffBehaviours']],
				]
			],

			'Results' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Assessments'], 
				'selected' => ['Institutions.Assessments', 'Institutions.Results']],	
			
			'Positions' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Positions'], 
				'selected' => ['Institutions.StaffPositions']],

			'Finance' => [
				'collapse' => true,
				'items' => [
					'Bank Accounts' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'BankAccounts']],
					'Fees' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Fees']],
					'Student Fees' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentFees']],
				]
			],

			'Infrastructures' => [
				'collapse' => true,
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Infrastructures']
			],

			'Survey' => [
				'collapse' => true,
				'items' => [
					'Forms' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys']],
					'Rubrics' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Rubrics'], 
						'selected' => ['Institutions.Rubrics', 'Institutions.RubricAnswers']]
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
		$id = $session->read('Institution.Students.id');
		$studentId = $session->read('Student.Students.id');
		$navigation = [
			'General' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $studentId, 'id' => $id],
				'selected' => ['Institutions.StudentUser', 'Institutions.StudentAccount', 'Institutions.StudentSurveys', 'Students.Identities', 'Students.Nationalities', 
					'Students.Contacts', 'Students.Guardians', 'Students.Languages', 'Students.SpecialNeeds', 'Students.Attachments', 'Students.Comments', 'Students.History', 
					'Students.GuardianUser']
			],
			'Academic' => [
				'url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'Programmes', 'index'], 
				'selected' => ['Institutions.Students.view', 'Students.Programmes.index', 'Sections', 'Classes', 'Absences', 'Behaviours', 'Results', 'Awards', 'Extracurriculars'],
			],
			'Finance' => [
				'url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'BankAccounts'], 
				'selected' => ['StudentFees'],
			],
		];
		return $navigation;
	}

	public function getStaffNavigation() {
		$session = $this->request->session();
		$id = $session->read('Staff.Staff.id');
		$navigation = [
			'General' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffUser', 'view', $id],
				'selected' => ['Institutions.StudentUser', 'Institutions.StudentAccount', 'Institutions.StudentSurveys', 'Staff.Identities', 'Staff.Nationalities', 
					'Staff.Contacts', 'Staff.Guardians', 'Staff.Languages', 'Staff.SpecialNeeds', 'Staff.Attachments', 'Staff.Comments', 'Staff.History']
			],
			'Career' => [
				'url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Employments'],
				'selected' => ['Staff.Employments', 'Staff.Positions', 'Staff.Sections', 'Staff.Classes', 'Staff.Absences', 
					'Staff.Leaves', 'Staff.Behaviours', 'Staff.Awards'],
			],
			'Professional Development' => [
				'url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Qualifications'],
				'selected' => ['Staff.Qualifications', 'Staff.Extracurriculars', 'Staff.Memberships', 'Staff.Licenses', 'Staff.Trainings'],
			],
			'Finance' => [
				'url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'BankAccounts'],
				'selected' => ['Staff.BankAccounts', 'Staff.Salaries'],
			],
			'Training' => [
				'url' => ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'TrainingResults'],
				'selected' => ['Staff.TrainingResults'],
			]
		];
		return $navigation;
	}

	public function getGuardianNavigation() {
		$session = $this->request->session();
		$id = $session->read('Guardian.Guardians.id');

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

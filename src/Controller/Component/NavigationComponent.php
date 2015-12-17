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

	private function getLink($controllerActionModelLink, $params = []) {
		$url = ['plugin' => null, 'controller' => null, 'action' => null];
		if (isset($params['plugin'])) {
			$url['plugin'] = $params['plugin'];
			unset($params['plugin']);
		}
		$link = explode('.', $controllerActionModelLink);
		if (isset($link[0])) {
			$url['controller'] = $link[0];
		}
		if (isset($link[1])) {
			$url['action'] = $link[1];
		}
		if (isset($link[2])) {
			$url['0'] = $link[2];
		}
		if (!empty($params)) {
			$url = array_merge($url, $params);
		}
		return $url;
	}

	public function checkPermissions(&$navigations) {
		$noAccessArray = [];
		foreach ($navigations as $key => $value) {
			if (isset($value['link']) && $value['link']) {
				// do nothing
			} else {
				$params = [];
				if (isset($value['params'])) {
					$params = $value['params'];
				}
				$url = $this->getLink($key, $params);
				if (!$this->AccessControl->check($url)) {
					$noAccessArray[] = $key;
					if (isset($value['parent']) && in_array($value['parent'], $navigations)) {
						unset($navigations[$key]);
					}
					if (array_key_exists($key, $navigations)) {
						unset($navigations[$key]);
					}
				}
			}
		}
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

		$institutionStudentActions = ['Students', 'StudentUser', 'StudentAccount', 'StudentSurveys', 'Students'];
		$institutionStaffActions = ['Staff', 'StaffUser', 'StaffAccount'];
		$institutionActions = array_merge($institutionStudentActions, $institutionStaffActions);

		if ($controller->name == 'Institutions' && $action != 'index' && (!in_array($action, $institutionActions))) {
			$navigations = $this->appendNavigation('Institutions.index', $navigations, $this->getInstitutionNavigation());
		} elseif (($controller->name == 'Students' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStudentActions))) {
			$navigations = $this->appendNavigation('Institutions.index', $navigations, $this->getInstitutionNavigation());
			$navigations = $this->appendNavigation('Institutions.Students.index', $navigations, $this->getInstitutionStudentNavigation());
		} elseif (($controller->name == 'Staff' && $action != 'index') || ($controller->name == 'Institutions' && in_array($action, $institutionStaffActions))) {
			$navigations = $this->appendNavigation('Institutions.index', $navigations, $this->getInstitutionNavigation());
			$navigations = $this->appendNavigation('Institutions.Staff.index', $navigations, $this->getInstitutionStaffNavigation());
		} elseif ($controller->name == 'Directories' && $action != 'index') {
			$navigations = $this->appendNavigation('Directories.index', $navigations, $this->getDirectoryNavigation());

			$session = $this->request->session();
			$isStudent = $session->read('Directory.Directories.is_student');
			$isStaff = $session->read('Directory.Directories.is_staff');

			if ($isStudent) {
				$navigations = $this->appendNavigation('Directories.view', $navigations, $this->getDirectoryStudentNavigation());
				$session->write('Directory.Directories.reload', true);
			}

			if ($isStaff) {
				$navigations = $this->appendNavigation('Directories.view', $navigations, $this->getDirectoryStaffNavigation());
				$session->write('Directory.Directories.reload', true);
			}
		}

		$navigations = $this->appendNavigation('Reports', $navigations, $this->getReportNavigation());
		$navigations = $this->appendNavigation('Administration', $navigations, $this->getAdministrationNavigation());

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
				'selected' => ['Guardians.add', 'Guardians.edit', 'Guardians.view', 'Guardians.Accounts', 'Guardians.Contacts', 'Guardians.Identities', 'Guardians.Languages', 'Guardians.Comments', 'Guardians.Attachments', 
					'Guardians.History', 'Guardians.Nationalities'],
			],

			'Directories.index' => [
				'title' => 'Directory', 
				'icon' => '<span><i class="fa kd-guardian"></i></span>',
				'params' => ['plugin' => 'Directory']
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
			],
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

				'Institutions.Programmes' => [
					'title' => 'Programmes',
					'parent' => 'Institution.Academic',
					'selected' => ['Institution.Programmes'],
					'params' => ['plugin' => 'Institution']
				],

				'Institutions.Sections' => [
					'title' => 'Classes',
					'parent' => 'Institution.Academic',
					'selected' => ['Institution.Sections'],
					'params' => ['plugin' => 'Institution']
				],

				'Institutions.Classes' => [
					'title' => 'Subjects',
					'parent' => 'Institution.Academic',
					'selected' => ['Institution.Classes'],
					'params' => ['plugin' => 'Institution']
				],

			'Institutions.Students.index' => [
				'title' => 'Students',
				'parent' => 'Institutions.index',
				'selected' => ['Institutions.Students', 'Institutions.Students.add', 'Institutions.TransferRequests', 'Institutions.Promotion', 'Institutions.Transfer', 
					'Institutions.StudentAdmission', 'Institutions.TransferApprovals', 'Institutions.StudentDropout', 'Institutions.DropoutRequests', 'Institutions.ImportStudents'],
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
					'selected' => ['Institutions.StudentAttendances', 'Institutions.StudentAbsences', 'Institutions.ImportStudentAttendances'],
					'params' => ['plugin' => 'Institution']
				],

				'Institutions.StaffAttendances.index' => [
					'title' => 'Staff',
					'parent' => 'Institution.Attendance',
					'selected' => ['Institutions.StaffAttendances', 'Institutions.StaffAbsences', 'Institutions.ImportStaffAttendances'],
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
					'title' => 'Institution Fees',
					'parent' => 'Institution.Finance', 
					'params' => ['plugin' => 'Institution'],
					'selected' => ['Institutions.Fees'],
				],

				'Institutions.StudentFees' => [
					'title' => 'Student Fees',
					'parent' => 'Institution.Finance', 
					'params' => ['plugin' => 'Institution'],
					'selected' => ['Institutions.StudentFees'],
				],

			'Institutions.Infrastructures' => [
				'title' => 'Infrastructures',
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
					'selected' => ['Institutions.Surveys', 'Institutions.ImportInstitutionSurveys'],
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

	public function getInstitutionStudentNavigation() {
		$session = $this->request->session();
		$id = $session->read('Institution.Students.id');
		$studentId = $session->read('Student.Students.id');
		$navigation = [
			'Institutions.StudentUser.view' => [
				'title' => 'General', 
				'parent' => 'Institutions.Students.index', 
				'params' => ['plugin' => 'Institution', '1' => $studentId, 'id' => $id], 
				'selected' => ['Institutions.StudentUser.edit', 'Institutions.StudentAccount.view', 'Institutions.StudentAccount.edit', 'Institutions.StudentSurveys', 'Institutions.StudentSurveys.edit', 
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

	public function getDirectoryNavigation() {
		$navigation = [
			'Directories.view' => [
				'title' => 'General', 
				'parent' => 'Directories.index', 
				'params' => ['plugin' => 'Directory'], 
				'selected' => ['Directories.view', 'Directories.edit', 'Directories.Accounts', 'Directories.Identities', 'Directories.Nationalities', 'Directories.Languages', 'Directories.Comments', 'Directories.Attachments', 
					'Directories.History', 'Directories.SpecialNeeds', 'Directories.ImportUsers']
			]
		];
		return $navigation;
	}

	public function getDirectoryStaffNavigation() {
		$session = $this->request->session();
		$id = $session->read('Guardian.Guardians.id');

		$navigation = [
			'Directories.Staff' => [
				'title' => 'Staff', 
				'parent' => 'Directories.index', 
				'link' => false,
			],
				'Directories.StaffEmployments' => [
					'title' => 'Career',
					'parent' => 'Directories.Staff',
					'params' => ['plugin' => 'Directory'],
					'selected' => ['Directories.StaffEmployments', 'Directories.StaffPositions', 'Directories.StaffSections', 'Directories.StaffClasses', 
						'Directories.StaffAbsences', 'Directories.StaffLeaves', 'Directories.StaffBehaviours', 'Directories.StaffAwards']
				],
				'Directories.StaffQualifications' => [
					'title' => 'Professional Development',
					'parent' => 'Directories.Staff',
					'params' => ['plugin' => 'Directory'],
					'selected' => ['Directories.StaffQualifications', 'Directories.StaffExtracurriculars', 'Directories.StaffMemberships', 'Directories.StaffLicenses', 'Directories.StaffTrainings']
				],
				'Directories.StaffBankAccounts' => [
					'title' => 'Finance',
					'parent' => 'Directories.Staff',
					'params' => ['plugin' => 'Directory', 'type' => 'staff'],
					'selected' => ['Directories.StaffBankAccounts', 'Directories.StaffSalaries']
				],
				'Directories.TrainingResults' => [
					'title' => 'Training',
					'parent' => 'Directories.Staff',
					'params' => ['plugin' => 'Directory'],
					'selected' => ['Directories.TrainingResults']
				],
		];
		return $navigation;
	}

	public function getDirectoryStudentNavigation() {
		$session = $this->request->session();
		$id = $session->read('Guardian.Guardians.id');

		$navigation = [
			'Directories.Student' => [
				'title' => 'Student', 
				'parent' => 'Directories.index', 
				'link' => false,
			],
				'Directories.StudentGuardians' => [
					'title' => 'Guardians',
					'parent' => 'Directories.Student',
					'params' => ['plugin' => 'Directory'],
					'selected' => ['Directories.StudentGuardians']
				],
				'Directories.StudentProgrammes.index' => [
					'title' => 'Academic',
					'parent' => 'Directories.Student',
					'params' => ['plugin' => 'Directory'],
					'selected' => ['Directories.StudentProgrammes.index', 'Directories.StudentSubjects', 'Directories.StudentClasses', 'Directories.StudentAbsences', 'Directories.StudentBehaviours', 
						'Directories.StudentResults', 'Directories.StudentAwards', 'Directories.StudentExtracurriculars']
				],
				'Directories.StudentBankAccounts' => [
					'title' => 'Finance', 
					'parent' => 'Directories.Student',
					'params' => ['plugin' => 'Directory', 'type' => 'student'],
					'selected' => ['Directories.StudentBankAccounts', 'Directories.StudentFees']
				],
		];
		return $navigation;
	}

	public function getReportNavigation() {
		$navigation = [
			'Reports.Institutions' => [
				'title' => 'Institutions',
				'parent' => 'Reports',
				'params' => ['plugin' => 'Report'],
			],
			'Reports.Students' => [
				'title' => 'Students',
				'parent' => 'Reports',
				'params' => ['plugin' => 'Report'],
			],
			'Reports.Staff' => [
				'title' => 'Staff',
				'parent' => 'Reports',
				'params' => ['plugin' => 'Report'],
			],
			'Reports.Surveys' => [
				'title' => 'Surveys',
				'parent' => 'Reports',
				'params' => ['plugin' => 'Report'],
			],
			'Reports.InstitutionRubrics' => [
				'title' => 'Quality',
				'parent' => 'Reports',
				'params' => ['plugin' => 'Report'],
			],
			'Reports.DataQuality' => [
				'title' => 'Data Quality',
				'parent' => 'Reports',
				'params' => ['plugin' => 'Report']
			],
			'Reports.Audit' => [
				'title' => 'Audit',
				'parent' => 'Reports',
				'params' => ['plugin' => 'Report'],
			],
		];
		return $navigation;
	}

	public function getAdministrationNavigation() {
		$navigation = [
			'SystemSetup' => [
				'title' => 'System Setup',
				'parent' => 'Administration',
				'link' => false,
			],
				'Areas.Areas' => [
					'title' => 'Administrative Boundaries', 
					'parent' => 'SystemSetup', 
					'params' => ['plugin' => 'Area'], 
					'selected' => ['Areas.Areas', 'Areas.Levels', 'Areas.AdministrativeLevels', 'Areas.Administratives']
				],
				'AcademicPeriods.Periods' => [
					'title' => 'Academic Periods', 
					'parent' => 'SystemSetup', 
					'params' => ['plugin' => 'AcademicPeriod'], 
					'selected' => ['AcademicPeriods.Periods', 'AcademicPeriods.Levels']
				],
				'Educations.Systems' => [
					'title' => 'Education Structure', 
					'parent' => 'SystemSetup', 
					'params' => ['plugin' => 'Education'], 
					'selected' => ['Educations.Systems', 'Educations.Levels', 'Educations.Cycles', 'Educations.Programmes', 'Educations.Grades', 'Educations.Subjects', 'Educations.Certifications', 
							'Educations.FieldOfStudies', 'Educations.ProgrammeOrientations']
				],
				'Assessments.Assessments' => [
					'title' => 'Assessments', 
					'parent' => 'SystemSetup', 
					'params' => ['plugin' => 'Assessment'], 
					'selected' => ['Assessments.Assessments', 'Assessments.GradingTypes', 'Assessments.GradingOptions', 'Assessments.Status']
				],
				'FieldOptions.index' => [
					'title' => 'Field Options', 
					'parent' => 'SystemSetup', 
					'params' => ['plugin' => 'FieldOption'], 
					'selected' => ['FieldOptions.index', 'FieldOptions.add', 'FieldOptions.view', 'FieldOptions.edit', 'FieldOptions.remove']
				],
				'SystemSetup.CustomField' => [
					'title' => 'Custom Field', 
					'parent' => 'SystemSetup', 
					'link' => false,
				],
					'InstitutionCustomFields.Fields' => [
						'title' => 'Institution', 
						'parent' => 'SystemSetup.CustomField', 
						'params' => ['plugin' => 'InstitutionCustomField'], 
						'selected' => ['InstitutionCustomFields.Fields', 'InstitutionCustomFields.Pages']
					],
					'StudentCustomFields.Fields' => [
						'title' => 'Student', 
						'parent' => 'SystemSetup.CustomField', 
						'params' => ['plugin' => 'StudentCustomField'], 
						'selected' => ['StudentCustomFields.Fields', 'StudentCustomFields.Pages']
					],
					'StaffCustomFields.Fields' => [
						'title' => 'Staff', 
						'parent' => 'SystemSetup.CustomField', 
						'params' => ['plugin' => 'StaffCustomField'], 
						'selected' => ['StaffCustomFields.Fields', 'StaffCustomFields.Pages']
					],
					'Infrastructures.Fields' => [
						'title' => 'Infrastructure', 
						'parent' => 'SystemSetup.CustomField', 
						'params' => ['plugin' => 'Infrastructure'], 
						'selected' => ['Infrastructures.Fields', 'Infrastructures.Pages', 'Infrastructures.Levels', 'Infrastructures.Types']
					],
				'Labels.index' => [
					'title' => 'Labels',
					'parent' => 'SystemSetup',
					'selected' => ['Labels.index', 'Labels.view', 'Labels.edit']
				],

				'Translations.index' => [
					'title' => 'Translations',
					'parent' => 'SystemSetup',
					'params' => ['plugin' => 'Localization'],
					'selected' => ['Translations.add', 'Translations.view', 'Translations.edit']
				],
				'Configurations.index' => [
					'title' => 'System Configurations',
					'parent' => 'SystemSetup',
					'selected' => ['Configurations.index', 'Configurations.add', 'Configurations.view', 'Configurations.edit']
				],
				'Notices.index' => [
					'title' => 'Notices',
					'parent' => 'SystemSetup',
					'selected' => ['Notices.index', 'Notices.add', 'Notices.view', 'Notices.edit']
				],

			'Security' => [
				'title' => 'Security',
				'parent' => 'Administration',
				'link' => false,
			],

				'Securities.Users' => [
					'title' => 'Users',
					'parent' => 'Security',
					'params' => ['plugin' => 'Security'],
					'selected' => ['Securities.Users', 'Securities.Accounts']
				],

				'Securities.UserGroups' => [
					'title' => 'Groups',
					'parent' => 'Security',
					'params' => ['plugin' => 'Security'],
					'selected' => ['Securities.UserGroups', 'Securities.SystemGroups']
				],

				'Securities.Roles' => [
					'title' => 'Roles',
					'parent' => 'Security',
					'params' => ['plugin' => 'Security'],
					'selected' => ['Securities.Roles', 'Securities.Permissions']
				],

			'Administration.Survey' => [
				'title' => 'Survey',
				'parent' => 'Administration',
				'link' => false,
			],

				'Surveys.Questions' => [
					'title' => 'Forms',
					'parent' => 'Administration.Survey',
					'params' => ['plugin' => 'Survey'],
					'selected' => ['Surveys.Questions', 'Surveys.Forms', 'Surveys.Status']
				],

				'Rubrics.Templates' => [
					'title' => 'Rubrics',
					'parent' => 'Administration.Survey',
					'params' => ['plugin' => 'Rubric'],
					'selected' => ['Rubrics.Sections', 'Rubrics.Criterias', 'Rubrics.Options', 'Rubrics.Status']
				],

			'Administration.Communications' => [
				'title' => 'Communications',
				'parent' => 'Administration',
				'link' => false,
			],

				'Alerts.Questions' => [
					'title' => 'Questions',
					'parent' => 'Administration.Communications',
					'params' => ['plugin' => 'Alert'],
					'selected' => ['Alerts.Questions']
				],

				'Alerts.Responses' => [
					'title' => 'Responses',
					'parent' => 'Administration.Communications',
					'params' => ['plugin' => 'Alert'],
					'selected' => ['Alerts.Responses']
				],

				'Alerts.Logs' => [
					'title' => 'Logs',
					'parent' => 'Administration.Communications',
					'params' => ['plugin' => 'Alert'],
					'selected' => ['Alerts.Logs']
				],

			'Administration.Training' => [
				'title' => 'Training',
				'parent' => 'Administration',
				'link' => false,
			],

				'Trainings.Courses' => [
					'title' => 'Logs',
					'parent' => 'Administration.Training',
					'params' => ['plugin' => 'Training'],
					'selected' => ['Trainings.Courses']
				],

				'Trainings.Sessions' => [
					'title' => 'Sessions',
					'parent' => 'Administration.Training',
					'params' => ['plugin' => 'Training'],
					'selected' => ['Trainings.Sessions']
				],

				'Trainings.Results' => [
					'title' => 'Results',
					'parent' => 'Administration.Training',
					'params' => ['plugin' => 'Training'],
					'selected' => ['Trainings.Results']
				],

			'Workflows.Workflows' => [
				'title' => 'Workflow',
				'parent' => 'Administration',
				'params' => ['plugin' => 'Workflow'],
				'selected' => ['Workflow.Workflows', 'Workflow.Steps']
			],
		];
		return $navigation;
	}
}

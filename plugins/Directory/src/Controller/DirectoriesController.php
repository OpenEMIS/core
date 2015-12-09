<?php
namespace Directory\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class DirectoriesController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Directory.Directories');
		$this->ControllerAction->models = [
			// Users
			'Contacts' 				=> ['className' => 'User.Contacts'],
			'Identities' 			=> ['className' => 'User.Identities'],
			'Nationalities' 		=> ['className' => 'User.Nationalities'],
			'Languages' 			=> ['className' => 'User.UserLanguages'],
			'Comments' 				=> ['className' => 'User.Comments'],
			'Attachments' 			=> ['className' => 'User.Attachments'],
			'Accounts'				=> ['className' => 'Directory.Accounts', 'actions' => ['view', 'edit']],
			'Awards' 				=> ['className' => 'User.Awards'],
			'SpecialNeeds' 			=> ['className' => 'User.SpecialNeeds'],
			
			// Student
			'StudentGuardians'		=> ['className' => 'Student.Guardians'],
			'StudentGuardianUser'	=> ['className' => 'Student.GuardianUser'],
			'StudentProgrammes'		=> ['className' => 'Student.Programmes', 'actions' => ['index', 'view']],
			'StudentBankAccounts'	=> ['className' => 'User.BankAccounts'],
			'StudentProgrammes' 	=> ['className' => 'Student.Programmes', 'actions' => ['index', 'view']],
			'StudentClasses'		=> ['className' => 'Student.StudentSections', 'actions' => ['index', 'view']],
			'StudentSubjects' 		=> ['className' => 'Student.StudentClasses', 'actions' => ['index', 'view']],
			'StudentAbsences' 		=> ['className' => 'Student.Absences', 'actions' => ['index', 'view']],
			'StudentBehaviours' 	=> ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
			'StudentResults' 		=> ['className' => 'Student.Results', 'actions' => ['index']],
			'StudentExtracurriculars' => ['className' => 'Student.Extracurriculars'],
			'StudentFees' 			=> ['className' => 'Student.StudentFees', 'actions' => ['index', 'view']],
			'StudentHistory' 		=> ['className' => 'Student.StudentActivities', 'actions' => ['index']],

			// Staff
			'StaffBankAccount'		=> ['className' => 'User.BankAccounts'],
			'StaffSalaries'			=> ['className' => 'Staff.Salaries'],
		];

		$this->loadComponent('User.Image');

		$this->set('contentHeader', 'Directories');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Directory', ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'index']);
		$header = __('Directory');
		$session = $this->request->session();
		$action = $this->request->params['action'];
		if ($action == 'index') {
			$session->delete('Directory.Directories.id');
			$session->delete('Directory.Directories.name');
			$session->delete('Directory.Directories.is_student');
			$session->delete('Directory.Directories.is_staff');
			$session->delete('Directory.Directories.is_guardian');
			$session->delete('Directory.Directories.reload');
			$session->delete('Staff.Staff.id');
			$session->delete('Staff.Staff.name');
			$session->delete('Student.Students.id');
			$session->delete('Student.Students.name');
		} elseif ($session->check('Directory.Directories.id') || $action == 'view' || $action == 'edit') {
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Directory.Directories.id')) {
				$id = $session->read('Directory.Directories.id');
			}
			if (!empty($id)) {
				$entity = $this->Directories->get($id);
				$name = $entity->name;
				$header = $name . ' - ' . __('Overview');
				$this->Navigation->addCrumb($name, ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'view', $id]);
			}
		}

		$this->set('contentHeader', $header);
	}

	public function onInitialize(Event $event, Table $model) {
		/**
		 * if student object is null, it means that students.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */
		$session = $this->request->session();
		if ($session->check('Directory.Directories.id')) {
			$header = '';
			$userId = $session->read('Directory.Directories.id');

			if (!$this->AccessControl->isAdmin()) {
				$institutionIds = $session->read('AccessControl.Institutions.ids');
				$studentId = $session->read('Student.Students.id');
				$enrolledStatus = false;
				$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
				foreach ($institutionIds as $id) {
					$enrolledStatus = $InstitutionStudentsTable->checkEnrolledInInstitution($studentId, $id);
					if ($enrolledStatus) {
						break;
					}
				}
				if (! $enrolledStatus) {
					if ($model->alias() != 'BankAccounts' && $model->alias() != 'StudentFees') {
						$this->ControllerAction->removeDefaultActions(['add', 'edit', 'remove']);
					}
				}
			}

			if ($session->check('Directory.Directories.name')) {
				$header = $session->read('Directory.Directories.name');
			}

			$alias = $model->alias;
			$this->Navigation->addCrumb($model->getHeader($alias));
			// temporary fix for renaming Sections and Classes
			if ($alias == 'Sections') $alias = 'Classes';
			else if ($alias == 'Classes') $alias = 'Subjects';
			$header = $header . ' - ' . $model->getHeader($alias);

			// $params = $this->request->params;
			$this->set('contentHeader', $header);

			if ($model->hasField('security_user_id')) {
				$model->fields['security_user_id']['type'] = 'hidden';
				$model->fields['security_user_id']['value'] = $userId;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$exists = $model->exists([
						$model->aliasField($model->primaryKey()) => $modelId,
						$model->aliasField('security_user_id') => $userId
					]);
					
					/**
					 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
					 */
					if (!$exists) {
						$this->Alert->warning('general.notExists');
						return $this->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => $alias]);
					}
				}
			}
		} else {
			if ($model->alias() == 'ImportStudents') {
				$this->Navigation->addCrumb($model->getHeader($model->alias()));
				$header = __('Students') . ' - ' . $model->getHeader($model->alias());
				$this->set('contentHeader', $header);
			} else {
				$this->Alert->warning('general.notExists');
				$event->stopPropagation();
				return $this->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'index']);
			}
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();
		if ($model->alias() != 'Directories') {
			if ($session->check('Directory.Directories.id')) {
				if ($model->hasField('security_user_id')) { // will need to remove this part once we change institution_sites to institutions
					$userId = $session->read('Directory.Directories.id');
					$query->where([$model->aliasField('security_user_id') => $userId]);
				} else if ($model->hasField('student_id')) {
					$userId = $session->read('Directory.Directories.id');
					$query->where([$model->aliasField('student_id') => $userId]);
				} else if ($model->hasField('staff_id')) {
					$userId = $session->read('Directory.Directories.id');
					$query->where([$model->aliasField('staff_id') => $userId]);
				}
			} else {
				$this->Alert->warning('general.noData');
				$event->stopPropagation();
				return $this->redirect(['action' => 'index']);
			}
		}
	}

	public function excel($id=0) {
		$this->Students->excel($id);
		$this->autoRender = false;
	}

	public function getImage($id) {
		$this->autoRender = false;
		$this->ControllerAction->autoRender = false;
		$this->Image->getUserImage($id);
	}


	public function getUserTabElements($options = []) {
		$plugin = $this->plugin;
		$name = $this->name;

		$id = (array_key_exists('id', $options))? $options['id']: $this->request->session()->read($name.'.id');

		$tabElements = [
			$this->name => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'view', $id],
				'text' => __('Overview')
			],
			'Accounts' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $id],
				'text' => __('Account')	
			],
			'Identities' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Identities'],
				'text' => __('Identities')	
			],
			'Nationalities' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Nationalities'],
				'text' => __('Nationalities')	
			],
			'Languages' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Languages'],
				'text' => __('Languages')	
			],
			'Comments' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Comments'],
				'text' => __('Comments')	
			],
			'Attachments' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Attachments'],
				'text' => __('Attachments')	
			],
			'Awards' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Awards'],
				'text' => __('Awards')
			],
			'SpecialNeeds' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'SpecialNeeds'],
				'text' => __('Special Needs')
			],
		];
		return $tabElements;
	}

	public function getStudentGeneralTabElements($options = []) {
		$plugin = $this->plugin;
		$name = $this->name;
		$tabElements = [
			'Guardians' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentGuardians'],
				'text' => __('Guardians')	
			],
			'History' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentHistory'],
				'text' => __('History')	
			]
		];

		return $tabElements;
	}

	public function getAcademicTabElements($options = []) {
		// $action = (array_key_exists('action', $options))? $options['action']: 'add';
		$id = (array_key_exists('id', $options))? $options['id']: 0;

		$tabElements = [];
		$studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
		$studentTabElements = [
			'Programmes' => ['text' => __('Programmes')],
			'Classes' => ['text' => __('Classes')],
			'Subjects' => ['text' => __('Subjects')],
			'Absences' => ['text' => __('Absences')],
			'Behaviours' => ['text' => __('Behaviours')],
			'Results' => ['text' => __('Results')],
			'Extracurriculars' => ['text' => __('Extracurriculars')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index']);
			$params = [$id];
			$tabElements[$key]['url'] = array_merge($tabElements[$key]['url'], $params);
		}
		return $tabElements;
	}

	public function getFinanceTabElements($options = []) {
		// $action = (array_key_exists('action', $options))? $options['action']: 'add';
		$id = (array_key_exists('id', $options))? $options['id']: 0;
		$plugin = $this->plugin;
		$name = $this->name;
		$tabElements = [];
		$studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
		$studentTabElements = [
			'BankAccounts' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentBankAccounts'],
				'text' => __('Bank Accounts')
			],
			'StudentFees' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentFees'],
				'text' => __('Fees')
			],
		];
		return $studentTabElements;
	}

	// For staff
	public function getCareerTabElements($options = []) {
		$tabElements = [];
		$studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
		$studentTabElements = [
			'Employments' => ['text' => __('Employments')],
			'Positions' => ['text' => __('Positions')],
			'Sections' => ['text' => __('Classes')],
			'Classes' => ['text' => __('Subjects')],
			'Absences' => ['text' => __('Absences')],
			'Leaves' => ['text' => __('Leaves')],
			'Behaviours' => ['text' => __('Behaviours')],
			'Awards' => ['text' => __('Awards')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
		}
		return $tabElements;
	}

	public function getProfessionalDevelopmentTabElements($options = []) {
		$tabElements = [];
		$studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
		$studentTabElements = [
			'Qualifications' => ['text' => __('Qualifications')],
			'Extracurriculars' => ['text' => __('Extracurriculars')],
			'Memberships' => ['text' => __('Memberships')],
			'Licenses' => ['text' => __('Licenses')],
			'Trainings' => ['text' => __('Trainings')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
		}
		return $tabElements;
	}

	public function getStaffFinanceTabElements($options = []) {
		$tabElements = [];
		$studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
		$studentTabElements = [
			'BankAccounts' => ['text' => __('Bank Accounts')],
			'Salaries' => ['text' => __('Salaries')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' => 'Staff'.$key, 'index']);
		}
		return $tabElements;
	}

	public function getTrainingTabElements($options = []) {
		$tabElements = [];
		$studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
		$studentTabElements = [
			'TrainingResults' => ['text' => __('Training Results')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
		}
		return $tabElements;
	}
}

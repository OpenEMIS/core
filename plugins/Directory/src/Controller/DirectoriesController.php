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
			'History' 				=> ['className' => 'User.UserActivities', 'actions' => ['index']],
			'SpecialNeeds' 			=> ['className' => 'User.SpecialNeeds'],


			// Users - Health
			'Healths' 				=> ['className' => 'Health.Healths'],
			'HealthAllergies' 		=> ['className' => 'Health.Allergies'],
			'HealthConsultations' 	=> ['className' => 'Health.Consultations'],
			'HealthFamilies' 		=> ['className' => 'Health.Families'],
			'HealthHistories' 		=> ['className' => 'Health.Histories'],
			'HealthImmunizations' 	=> ['className' => 'Health.Immunizations'],
			'HealthMedications' 	=> ['className' => 'Health.Medications'],
			'HealthTests' 			=> ['className' => 'Health.Tests'],


			// Student
			'StudentGuardians'		=> ['className' => 'Student.Guardians'],
			'StudentGuardianUser'	=> ['className' => 'Student.GuardianUser'],
			'StudentProgrammes'		=> ['className' => 'Student.Programmes', 'actions' => ['index', 'view']],
			'StudentClasses'		=> ['className' => 'Student.StudentSections', 'actions' => ['index', 'view']],
			'StudentSubjects' 		=> ['className' => 'Student.StudentClasses', 'actions' => ['index', 'view']],
			'StudentAbsences' 		=> ['className' => 'Student.Absences', 'actions' => ['index', 'view']],
			'StudentBehaviours' 	=> ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
			'StudentResults' 		=> ['className' => 'Student.Results', 'actions' => ['index']],
			'StudentExtracurriculars' => ['className' => 'Student.Extracurriculars'],
			'StudentFees' 			=> ['className' => 'Student.StudentFees', 'actions' => ['index', 'view']],
			'StudentBankAccounts'	=> ['className' => 'User.BankAccounts'],
			'StudentAwards' 		=> ['className' => 'User.Awards'],


			// Staff
			'StaffEmployments'		=> ['className' => 'Staff.Employments'],
			'StaffSalaries'			=> ['className' => 'Staff.Salaries'],
			'StaffQualifications'	=> ['className' => 'Staff.Qualifications'],
			'StaffPositions'		=> ['className' => 'Staff.Positions', 'actions' => ['index', 'view']],
			'StaffSections'			=> ['className' => 'Staff.StaffSections', 'actions' => ['index', 'view']],
			'StaffClasses'			=> ['className' => 'Staff.StaffClasses', 'actions' => ['index', 'view']],
			'StaffAbsences'			=> ['className' => 'Staff.Absences', 'actions' => ['index', 'view']],
			'StaffLeaves'			=> ['className' => 'Staff.Leaves'],
			'StaffBehaviours'		=> ['className' => 'Staff.StaffBehaviours', 'actions' => ['index', 'view']],
			'StaffExtracurriculars'	=> ['className' => 'Staff.Extracurriculars'],
			'StaffMemberships'		=> ['className' => 'Staff.Memberships'],
			'StaffLicenses'			=> ['className' => 'Staff.Licenses'],
			'StaffTrainings'		=> ['className' => 'Staff.StaffTrainings'],
			'TrainingResults'		=> ['className' => 'Staff.TrainingResults', 'actions' => ['index', 'view']],
			'TrainingNeeds'			=> ['className' => 'Staff.TrainingNeeds'],
			'StaffBankAccounts'		=> ['className' => 'User.BankAccounts'],
			'StaffAwards' 			=> ['className' => 'User.Awards'],

			'ImportUsers' 			=> ['className' => 'Directory.ImportUsers', 'actions' => ['add']],
		];

		$this->loadComponent('Training.Training');
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
			$session->delete('Directory.Directories');
			$session->delete('Staff.Staff.id');
			$session->delete('Staff.Staff.name');
			$session->delete('Student.Students.id');
			$session->delete('Student.Students.name');
		} else if ($session->check('Directory.Directories.id') || $action == 'view' || $action == 'edit') {
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
			} else if ($model->hasField('staff_id')) {
				$model->fields['staff_id']['type'] = 'hidden';
				$model->fields['staff_id']['value'] = $userId;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$exists = $model->exists([
						$model->aliasField($model->primaryKey()) => $modelId,
						$model->aliasField('staff_id') => $userId
					]);
					
					/**
					 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
					 */
					if (!$exists) {
						$this->Alert->warning('general.notExists');
						return $this->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => $alias]);
					}
				}
			} else if ($model->hasField('student_id')) {
				$model->fields['student_id']['type'] = 'hidden';
				$model->fields['student_id']['value'] = $userId;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$exists = $model->exists([
						$model->aliasField($model->primaryKey()) => $modelId,
						$model->aliasField('student_id') => $userId
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
			if ($model->alias() == 'ImportUsers') {
				$this->Navigation->addCrumb($model->getHeader($model->alias()));
				$header = __('Users') . ' - ' . $model->getHeader($model->alias());
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
				$userId = $session->read('Directory.Directories.id');
				if ($model->hasField('security_user_id')) {
					$query->where([$model->aliasField('security_user_id') => $userId]);
				} else if ($model->hasField('student_id')) {
					$query->where([$model->aliasField('student_id') => $userId]);
				} else if ($model->hasField('staff_id')) {
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

		$id = (array_key_exists('id', $options))? $options['id']: $this->request->session()->read($plugin.'.'.$name.'.id');
		
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
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Identities', $id],
				'text' => __('Identities')	
			],
			'Nationalities' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Nationalities', $id],
				'text' => __('Nationalities')	
			],
			'Contacts' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Contacts', $id],
				'text' => __('Contacts')	
			],
			'Languages' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Languages', $id],
				'text' => __('Languages')	
			],
			'Comments' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Comments', $id],
				'text' => __('Comments')	
			],
			'Attachments' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Attachments', $id],
				'text' => __('Attachments')	
			],
			'SpecialNeeds' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'SpecialNeeds', $id],
				'text' => __('Special Needs')
			],
			'History' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'History', $id],
				'text' => __('History')	
			]
		];
		return $tabElements;
	}

	public function getStudentGuardianTabElements($options = []) {
		$type = (array_key_exists('type', $options))? $options['type']: null;
		$plugin = $this->plugin;
		$name = $this->name;
		$tabElements = [
			'Guardians' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentGuardians', 'type' => $type],
				'text' => __('Guardians')	
			],
		];

		return $tabElements;
	}

	public function getAcademicTabElements($options = []) {
		$id = (array_key_exists('id', $options))? $options['id']: 0;
		$type = (array_key_exists('type', $options))? $options['type']: null;
		$tabElements = [];
		$studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
		$studentTabElements = [
			'Programmes' => ['text' => __('Programmes')],
			'Classes' => ['text' => __('Classes')],
			'Subjects' => ['text' => __('Subjects')],
			'Absences' => ['text' => __('Absences')],
			'Behaviours' => ['text' => __('Behaviours')],
			'Results' => ['text' => __('Results')],
			'Awards' => ['text' => __('Awards')],
			'Extracurriculars' => ['text' => __('Extracurriculars')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
		}
		return $tabElements;
	}

	public function getFinanceTabElements($options = []) {
		$type = (array_key_exists('type', $options))? $options['type']: null;
		$plugin = $this->plugin;
		$name = $this->name;
		$tabElements = [];
		$studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
		$studentTabElements = [
			'BankAccounts' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentBankAccounts', 'type' => $type],
				'text' => __('Bank Accounts')
			],
			'StudentFees' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentFees', 'type' => $type],
				'text' => __('Fees')
			],
		];

		foreach ($studentTabElements as $key => $tab) {
			$studentTabElements[$key]['url'] = array_merge($studentTabElements[$key]['url'], ['type' => $type]);
		}
		return $studentTabElements;
	}

	// For staff
	public function getCareerTabElements($options = []) {
		$type = (array_key_exists('type', $options))? $options['type']: null;
		$tabElements = [];
		$studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
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
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' => 'Staff'.$key, 'type' => 'staff']);
		}
		return $tabElements;
	}

	public function getProfessionalDevelopmentTabElements($options = []) {
		$tabElements = [];
		$studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
		$studentTabElements = [
			'Qualifications' => ['text' => __('Qualifications')],
			'Extracurriculars' => ['text' => __('Extracurriculars')],
			'Memberships' => ['text' => __('Memberships')],
			'Licenses' => ['text' => __('Licenses')],
			'Trainings' => ['text' => __('Trainings')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' => 'Staff'.$key, 'index']);
		}
		return $tabElements;
	}

	public function getStaffFinanceTabElements($options = []) {
		$type = (array_key_exists('type', $options))? $options['type']: null;
		$tabElements = [];
		$staffUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
		$staffTabElements = [
			'BankAccounts' => ['text' => __('Bank Accounts')],
			'Salaries' => ['text' => __('Salaries')],
		];

		$tabElements = array_merge($tabElements, $staffTabElements);

		foreach ($staffTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($staffUrl, ['action' => 'Staff'.$key, 'type' => $type]);
		}
		return $tabElements;
	}

	public function getTrainingTabElements($options = []) {
		$tabElements = [];
		$studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
		$studentTabElements = [
			'TrainingResults' => ['text' => __('Training Results')],
			'TrainingNeeds' => ['text' => __('Training Needs')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
		}
		return $tabElements;
	}
}

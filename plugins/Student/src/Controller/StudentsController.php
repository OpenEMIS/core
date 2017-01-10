<?php
namespace Student\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class StudentsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Institution.StudentUser');
		$this->ControllerAction->models = [
			'Accounts' 			=> ['className' => 'Student.Accounts', 'actions' => ['view', 'edit']],
			'Nationalities' 	=> ['className' => 'User.Nationalities'],
			'Attachments' 		=> ['className' => 'User.Attachments'],
			'Guardians' 		=> ['className' => 'Student.Guardians'],
			'GuardianUser' 		=> ['className' => 'Student.GuardianUser', 'actions' => ['add', 'view', 'edit']],
			'Absences' 			=> ['className' => 'Student.Absences', 'actions' => ['index', 'view']],
			'Behaviours' 		=> ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
			'Extracurriculars' 	=> ['className' => 'Student.Extracurriculars'],
			'History' 			=> ['className' => 'User.UserActivities', 'actions' => ['index']],
			'ImportStudents' 	=> ['className' => 'Student.ImportStudents', 'actions' => ['index', 'add']],

			// Healths
			'Healths' 				=> ['className' => 'Health.Healths'],
			'HealthAllergies' 		=> ['className' => 'Health.Allergies'],
			'HealthConsultations' 	=> ['className' => 'Health.Consultations'],
			'HealthFamilies' 		=> ['className' => 'Health.Families'],
			'HealthHistories' 		=> ['className' => 'Health.Histories'],
			'HealthImmunizations' 	=> ['className' => 'Health.Immunizations'],
			'HealthMedications' 	=> ['className' => 'Health.Medications'],
			'HealthTests' 			=> ['className' => 'Health.Tests']
		];

		$this->loadComponent('User.Image');
		$this->loadComponent('Institution.InstitutionAccessControl');
		$this->attachAngularModules();

		$this->set('contentHeader', 'Students');
	}

	// CAv4
	public function StudentFees() 	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentFees']); }
	public function Classes() 		{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentClasses']); }
	public function Subjects() 		{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentSubjects']); }
    public function Nationalities() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']); }
    public function Languages() 	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserLanguages']); }
    public function SpecialNeeds() 	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.SpecialNeeds']); }
    public function Contacts() 		{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Contacts']); }
    public function BankAccounts() 	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.BankAccounts']); }
    public function Comments() 		{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Comments']); }
    public function Identities() 	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']); }
    public function Awards() 		{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Awards']); }
	// End

	// AngularJS
	public function Results() {
		$session = $this->request->session();

		if ($session->check('Student.Students.id')) {
			$studentId = $session->read('Student.Students.id');
			$session->write('Student.Results.student_id', $studentId);

			// tabs
			$options = ['type' => 'student'];
	        $tabElements = $this->getAcademicTabElements($options);
	        $this->set('tabElements', $tabElements);
	        $this->set('selectedAction', 'Results');
	        // End

			$this->set('ngController', 'StudentResultsCtrl as StudentResultsController');
		}
	}
	// End

	private function attachAngularModules() {
		$action = $this->request->action;

		switch ($action) {
			case 'Results':
				$this->Angular->addModules([
					'alert.svc',
					'student.results.ctrl',
					'student.results.svc'
				]);
				break;
		}
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		$institutionName = $session->read('Institution.Institutions.name');
		$institutionId = $session->read('Institution.Institutions.id');
		$this->Navigation->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $institutionId]);
		$this->Navigation->addCrumb('Students', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students']);
		$header = __('Students');

		if ($action == 'index') {

		} else if ($session->check('Student.Students.id') || $action == 'view' || $action == 'edit' || $action == 'Results') {
			// add the student name to the header
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Student.Students.id')) {
				$id = $session->read('Student.Students.id');
			} else if ($session->check('Institution.Students.id')) {
				$id = $session->read('Institution.Students.id');
			}

			if (!empty($id)) {
				$entity = $this->StudentUser->get($id);
				$name = $entity->name;
				$header = $action == 'Results' ? $name . ' - ' . __('Assessments') : $name . ' - ' . __('Overview');
				$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $id])]);
			}
		}

		$this->set('contentHeader', $header);
	}

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		/**
		 * if student object is null, it means that students.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */

		$session = $this->request->session();
		if ($session->check('Student.Students.id')) {
			$header = '';
			$userId = $session->read('Student.Students.id');

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

			if ($session->check('Student.Students.name')) {
				$header = $session->read('Student.Students.name');
			}

			$idKey = $this->ControllerAction->getPrimaryKey($model);
			$primaryKey = $model->primaryKey();

			$alias = $model->alias;
			$this->Navigation->addCrumb($model->getHeader($alias));
			$header = $header . ' - ' . $model->getHeader($alias);

			// $params = $this->request->params;
			$this->set('contentHeader', $header);

			if ($model->hasField('security_user_id')) {
				$model->fields['security_user_id']['type'] = 'hidden';
				$model->fields['security_user_id']['value'] = $userId;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$ids = $this->ControllerAction->paramsDecode($modelId);
					$idKey = $this->ControllerAction->getIdKeys($model, $ids);
					$idKey[$model->aliasField('security_user_id')] = $userId;

					/**
					 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
					 */
					if (!$model->exists($idKey)) {
						$this->Alert->warning('general.notExists');
						return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => $alias]);
					}
				}
			} else if ($model->hasField('student_id')) {
				$model->fields['student_id']['type'] = 'hidden';
				$model->fields['student_id']['value'] = $userId;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$ids = $this->ControllerAction->paramsDecode($modelId);
					$idKey = $this->ControllerAction->getIdKeys($model, $ids);
					$idKey[$model->aliasField('student_id')] = $userId;

					/**
					 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
					 */
					if (!$model->exists($idKey)) {
						$this->Alert->warning('general.notExists');
						return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => $alias]);
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
				return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
			}
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();

		if ($model->alias() != 'Students') {
			if ($session->check('Student.Students.id')) {
				if ($model->hasField('security_user_id')) {
					$userId = $session->read('Student.Students.id');
					$query->where([$model->aliasField('security_user_id') => $userId]);
				} else if ($model->hasField('student_id')) {
					$userId = $session->read('Student.Students.id');
					$query->where([$model->aliasField('student_id') => $userId]);
				}
			} else {
				$this->Alert->warning('general.noData');
				$event->stopPropagation();
				return $this->redirect(['action' => 'index']);
			}
		}
	}

	public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra) {
		$this->beforePaginate($event, $model, $query, $extra);
	}

	public function excel($id=0) {
		$this->Students->excel($id);
		$this->autoRender = false;
	}

	// public function getUserTabElements($options = []) {
	// 	$plugin = $this->plugin;
	// 	$name = $this->name;

	// 	$id = (array_key_exists('id', $options))? $options['id']: $this->request->session()->read($name.'.id');

	// 	$tabElements = [
	// 		$this->name => [
	// 			'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'view', $id],
	// 			'text' => __('Details')
	// 		],
	// 		'Accounts' => [
	// 			'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $id],
	// 			'text' => __('Account')
	// 		]
	// 	];

	// 	return $tabElements;
	// }

	public function getUserTabElements($options = []) {
		$session = $this->request->session();
		$tabElements = $session->read('Institution.Students.tabElements');

		return $tabElements;
	}

	public function getAcademicTabElements($options = []) {
		return TableRegistry::get('Institution.StudentUser')->getAcademicTabElements($options);
	}

	public function getFinanceTabElements($options = []) {
		// $action = (array_key_exists('action', $options))? $options['action']: 'add';
		$id = (array_key_exists('id', $options))? $options['id']: 0;

		$tabElements = [];
		$studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
		$studentTabElements = [
			'BankAccounts' => ['text' => __('Bank Accounts')],
			'StudentFees' => ['text' => __('Fees')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>$key, 'index']);
			$params = [$id];
			$tabElements[$key]['url'] = array_merge($tabElements[$key]['url'], $params);
		}
		return $tabElements;
	}

	public function getImage($id) {
		$this->autoRender = false;
		$this->ControllerAction->autoRender = false;
		$this->Image->getUserImage($id);
	}

	public function getStudentGuardianTabElements($options = []) {
		$type = (array_key_exists('type', $options))? $options['type']: null;
		$plugin = $this->plugin;
		$name = $this->name;
		$tabElements = [
			'Guardians' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Guardians', 'type' => $type],
				'text' => __('Guardians')
			],
		];
		return $tabElements;
	}
}

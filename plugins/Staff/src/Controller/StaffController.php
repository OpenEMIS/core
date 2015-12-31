<?php
namespace Staff\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Controller\AppController;

class StaffController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Staff.Staff');

		$this->ControllerAction->models = [
			'Accounts'			=> ['className' => 'Staff.Accounts', 'actions' => ['view', 'edit']],
			'Contacts'			=> ['className' => 'User.Contacts'],
			'Identities'		=> ['className' => 'User.Identities'],
			'Nationalities' 	=> ['className' => 'User.Nationalities'],
			'Languages'			=> ['className' => 'User.UserLanguages'],
			'Comments'			=> ['className' => 'User.Comments'],
			'SpecialNeeds'		=> ['className' => 'User.SpecialNeeds'],
			'Awards'			=> ['className' => 'User.Awards'],
			'Attachments'		=> ['className' => 'User.Attachments'],
			'Qualifications'	=> ['className' => 'Staff.Qualifications'],
			'Positions'			=> ['className' => 'Staff.Positions', 'actions' => ['index', 'view']],
			'Sections'			=> ['className' => 'Staff.StaffSections', 'actions' => ['index', 'view']],
			'Classes'			=> ['className' => 'Staff.StaffClasses', 'actions' => ['index', 'view']],
			'Absences'			=> ['className' => 'Staff.Absences', 'actions' => ['index', 'view']],
			'Leaves'			=> ['className' => 'Staff.Leaves'],
			'Behaviours'		=> ['className' => 'Staff.StaffBehaviours', 'actions' => ['index', 'view']],
			'Extracurriculars'	=> ['className' => 'Staff.Extracurriculars'],
			'Trainings'			=> ['className' => 'Staff.StaffTrainings'],
			'Employments'		=> ['className' => 'Staff.Employments'],
			'Salaries'			=> ['className' => 'Staff.Salaries'],
			'Memberships'		=> ['className' => 'Staff.Memberships'],
			'Licenses'			=> ['className' => 'Staff.Licenses'],
			'BankAccounts'		=> ['className' => 'User.BankAccounts'],
			'History'			=> ['className' => 'User.UserActivities', 'actions' => ['index']],
			'ImportStaff' 		=> ['className' => 'Staff.ImportStaff', 'actions' => ['index', 'add']],
			'TrainingNeeds'		=> ['className' => 'Staff.TrainingNeeds'],
			'TrainingResults'	=> ['className' => 'Staff.TrainingResults', 'actions' => ['index', 'view']],
			'Achievements'		=> ['className' => 'Staff.Achievements']
		];

		$this->loadComponent('Training.Training');
		$this->loadComponent('User.Image');

		$this->set('contentHeader', 'Staff');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$session = $this->request->session();
		$this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		$institutionName = $session->read('Institution.Institutions.name');
		$institutionId = $session->read('Institution.Institutions.id');
		$this->Navigation->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $institutionId]);
		$this->Navigation->addCrumb('Staff', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Staff']);
		$action = $this->request->params['action'];
		$header = __('Staff');

		if ($action == 'index') {
			
		} else if ($session->check('Staff.Staff.id') || $action == 'view' || $action == 'edit') {
			// add the student name to the header
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Staff.Staff.id')) {
				$id = $session->read('Staff.Staff.id');
			} else if ($session->check('Institution.Staff.id')) {
				$id = $session->read('Institution.Staff.id');
			}

			if (!empty($id)) {
				$entity = $this->Staff->get($id);
				$name = $entity->name;
				$header = $name . ' - ' . __('Overview');
				$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffUser', 'view', $id]);
			}
		}
		$this->set('contentHeader', $header);
    }

	public function onInitialize($event, $model) {
		/**
		 * if student object is null, it means that student.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */
		$session = $this->request->session();
		if ($session->check('Staff.Staff.id')) {
			$header = '';
			$userId = $session->read('Staff.Staff.id');

			if ($session->check('Staff.Staff.name')) {
				$header = $session->read('Staff.Staff.name');
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
						return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => $alias]);
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
						return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => $alias]);
					}
				}
			}
		} else {
			if ($model->alias() == 'ImportStaff') {
				$this->Navigation->addCrumb($model->getHeader($model->alias()));
				$header = __('Staff') . ' - ' . $model->getHeader($model->alias());
				$this->set('contentHeader', $header);
			} else {
				$this->Alert->warning('general.notExists');
				$event->stopPropagation();
				return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']);
			}
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();
		
		if ($model->alias() != 'Staff') {
			if ($session->check('Staff.Staff.id')) {
				$userId = $session->read('Staff.Staff.id');
				if ($model->hasField('security_user_id')) {
					$query->where([$model->aliasField('security_user_id') => $userId]);
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
		$this->Staff->excel($id);
		$this->autoRender = false;
	}

	public function getUserTabElements($options = []) {
		$session = $this->request->session();
		$tabElements = $session->read('Institution.Staff.tabElements');
		return $tabElements;
	}

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

	public function getFinanceTabElements($options = []) {
		$tabElements = [];
		$studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
		$studentTabElements = [
			'BankAccounts' => ['text' => __('Bank Accounts')],
			'Salaries' => ['text' => __('Salaries')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
		}
		return $tabElements;
	}

	public function getTrainingTabElements($options = []) {
		$tabElements = [];
		$studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
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

	public function getImage($id) {
		$this->autoRender = false;
		$this->ControllerAction->autoRender = false;
		$this->Image->getUserImage($id);
	}
}

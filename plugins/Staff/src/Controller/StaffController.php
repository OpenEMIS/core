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
			'Accounts'			=> ['className' => 'User.Accounts', 'actions' => ['view', 'edit']],
			'Contacts'			=> ['className' => 'User.Contacts'],
			'Identities'		=> ['className' => 'User.Identities'],
			'Nationalities' 	=> ['className' => 'User.Nationalities'],
			'Languages'			=> ['className' => 'User.UserLanguages'],
			'Comments'			=> ['className' => 'User.Comments'],
			'SpecialNeeds'		=> ['className' => 'User.SpecialNeeds'],
			'Awards'			=> ['className' => 'User.Awards'],
			'Attachments'		=> ['className' => 'User.Attachments'],
			'Qualifications'	=> ['className' => 'Staff.Qualifications'],
			'Positions'			=> ['className' => 'Staff.Positions', 'actions' => ['index']],
			'Sections'			=> ['className' => 'Staff.StaffSections', 'actions' => ['index']],
			'Classes'			=> ['className' => 'Staff.StaffClasses', 'actions' => ['index']],
			'Absences'			=> ['className' => 'Staff.Absences', 'actions' => ['index']],
			'Leaves'			=> ['className' => 'Staff.Leaves'],
			'Behaviours'		=> ['className' => 'Staff.StaffBehaviours', 'actions' => ['index']],
			'Extracurriculars'	=> ['className' => 'Staff.Extracurriculars'],
			'Trainings'			=> ['className' => 'Staff.StaffTrainings'],
			'Employments'		=> ['className' => 'Staff.Employments'],
			'Salaries'			=> ['className' => 'Staff.Salaries'],
			'Memberships'		=> ['className' => 'Staff.Memberships'],
			'Licenses'			=> ['className' => 'Staff.Licenses'],
			'BankAccounts'		=> ['className' => 'User.BankAccounts'],
			'History'			=> ['className' => 'Staff.StaffActivities', 'actions' => ['index']],
		];

		$this->set('contentHeader', 'Staff');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Staff', ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		$header = __('Staff');

		if ($action == 'index') {
			$session->delete('Staff.id');
			$session->delete('Staff.name');
		} else if ($session->check('Staff.id') || $action == 'view' || $action == 'edit') {
			// add the student name to the header
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Staff.id')) {
				$id = $session->read('Staff.id');
			}

			if (!empty($id)) {
				$entity = $this->Staff->get($id);
				$name = $entity->name;
				$header = $name . ' - ' . __('Overview');
				$this->Navigation->addCrumb($name, ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'view', $id]);
			}
		}
		$this->set('contentHeader', $header);
    }

	public function onInitialize($event, $model) {
		/**
		 * if student object is null, it means that student.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */
		$session = $this->request->session();
		if ($session->check('Staff.id')) {
			$header = '';
			$userId = $session->read('Staff.id');

			if ($session->check('Staff.name')) {
				$header = $session->read('Staff.name');
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
			}
		} else {
			$this->Alert->warning('general.notExists');
			$event->stopPropagation();
			return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']);
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();
		
		if ($model->alias() != 'Staff') {
			if ($session->check('Staff.id')) {
				$userId = $session->read('Staff.id');
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
}

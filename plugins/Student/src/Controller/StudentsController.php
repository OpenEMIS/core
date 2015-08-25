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

		$this->ControllerAction->model('Student.Students');
		$this->ControllerAction->models = [
			'Accounts' 			=> ['className' => 'User.Accounts', 'actions' => ['view', 'edit']],
			'Contacts' 			=> ['className' => 'User.Contacts'],
			'Identities' 		=> ['className' => 'User.Identities'],
			'Languages' 		=> ['className' => 'User.UserLanguages'],
			'Comments' 			=> ['className' => 'User.Comments'],
			'SpecialNeeds' 		=> ['className' => 'User.SpecialNeeds'],
			'Awards' 			=> ['className' => 'User.Awards'],
			'Attachments' 		=> ['className' => 'User.Attachments'],
			'Guardians' 		=> ['className' => 'Student.Guardians'],
			'GuardianUser' 		=> ['className' => 'Student.GuardianUser', 'actions' => ['add', 'view', 'edit']],
			'Programmes' 		=> ['className' => 'Student.Programmes', 'actions' => ['index']],
			'Sections'			=> ['className' => 'Student.StudentSections', 'actions' => ['index']],
			'Classes' 			=> ['className' => 'Student.StudentClasses', 'actions' => ['index']],
			'Absences' 			=> ['className' => 'Student.Absences', 'actions' => ['index']],
			'Behaviours' 		=> ['className' => 'Student.StudentBehaviours', 'actions' => ['index']],
			'Results' 			=> ['className' => 'Student.Results', 'actions' => ['index']],
			'Extracurriculars' 	=> ['className' => 'Student.Extracurriculars'],
			'BankAccounts' 		=> ['className' => 'User.BankAccounts'],
			'StudentFees' 		=> ['className' => 'Student.StudentFees', 'actions' => ['index', 'view']],
			'History' 			=> ['className' => 'Student.StudentActivities', 'actions' => ['index']]
		];
		$this->set('contentHeader', 'Students');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Student', ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		$header = __('Students');

		if ($action == 'index') {
			$session->delete('Students.id');
			$session->delete('Students.name');
		} else if ($session->check('Students.id') || $action == 'view' || $action == 'edit') {
			// add the student name to the header
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Students.id')) {
				$id = $session->read('Students.id');
			}

			if (!empty($id)) {
				$entity = $this->Students->get($id);
				$name = $entity->name;
				$header = $name . ' - ' . __('Overview');
				$this->Navigation->addCrumb($name, ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'view', $id]);
			}
		}
		$this->set('contentHeader', $header);
	}

	public function onInitialize(Event $event, Table $model) {
		/**
		 * if student object is null, it means that students.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */
		
		$session = $this->request->session();
		if ($session->check('Students.id')) {
			$header = '';
			$userId = $session->read('Students.id');

			if ($session->check('Students.name')) {
				$header = $session->read('Students.name');
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
						return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => $alias]);
					}
				}
			}
		} else {
			$this->Alert->warning('general.notExists');
			$event->stopPropagation();
			return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();
		
		if ($model->alias() != 'Students') {
			if ($session->check('Students.id')) {
				if ($model->hasField('security_user_id')) { // will need to remove this part once we change institution_sites to institutions
					$userId = $session->read('Students.id');
					$query->where([$model->aliasField('security_user_id') => $userId]);
				} else if ($model->hasField('student_id')) {
					$userId = $session->read('Students.id');
					$query->where([$model->aliasField('student_id') => $userId]);
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
}

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
			'Contacts' 			=> ['className' => 'User.Contacts'],
			'Identities' 		=> ['className' => 'User.Identities'],
			'Nationalities' 	=> ['className' => 'User.Nationalities'],
			'Languages' 		=> ['className' => 'User.UserLanguages'],
			'Comments' 			=> ['className' => 'User.Comments'],
			'Attachments' 		=> ['className' => 'User.Attachments'],

			// Student

			// 'SpecialNeeds' 		=> ['className' => 'User.SpecialNeeds'],
			// 'Awards' 			=> ['className' => 'User.Awards'],
			// 'BankAccounts' 		=> ['className' => 'User.BankAccounts'],

			// Staff
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
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Identities', $id],
				'text' => __('Identities')	
			],
			'Nationalities' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Nationalities', $id],
				'text' => __('Nationalities')	
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
			'History' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'History', $id],
				'text' => __('History')	
			]
		];

		return $tabElements;
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
}

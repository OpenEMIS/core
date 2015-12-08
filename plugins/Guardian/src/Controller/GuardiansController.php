<?php
namespace Guardian\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use App\Controller\AppController;

class GuardiansController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Guardian.Guardians');
		// $this->ControllerAction->model()->addBehavior('Guardian.Guardian');
        // $this->ControllerAction->model()->addBehavior('TrackActivity', ['target' => 'Guardian.GuardianActivities', 'key' => 'security_user_id', 'session' => 'Users.id']);
        // $this->ControllerAction->model()->addBehavior('AdvanceSearch');

		$this->ControllerAction->models = [
			'Accounts' 			=> ['className' => 'Guardian.Accounts', 'actions' => ['view', 'edit']],
			'Contacts' 			=> ['className' => 'User.Contacts'],
			'Identities' 		=> ['className' => 'User.Identities'],
			'Nationalities' 	=> ['className' => 'User.Nationalities'],
			'Languages' 		=> ['className' => 'User.UserLanguages'],
			'Comments' 			=> ['className' => 'User.Comments'],
			'Attachments' 		=> ['className' => 'User.Attachments'],
			'History' 			=> ['className' => 'Guardian.GuardianActivities', 'actions' => ['index']],
		];

		$this->loadComponent('User.Image');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Guardian', ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		$header = __('Guardians');

		if ($action == 'index') {
			$session->delete('Guardian.Guardians.id');
			$session->delete('Guardian.Guardians.name');
		} else if ($session->check('Guardian.Guardians.id') || $action == 'view' || $action == 'edit') {
			// add the student name to the header
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Guardian.Guardians.id')) {
				$id = $session->read('Guardian.Guardians.id');
			}

			if (!empty($id)) {
				$entity = $this->Guardians->get($id);
				$name = $entity->name;
				$header = $name . ' - ' . __('Overview');
				$this->Navigation->addCrumb($name, ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'view', $id]);
			}
		}
		$this->set('contentHeader', $header);
	}

	public function onInitialize($event, $model) {
		/**
		 * if guardian object is null, it means that guardian.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */

		$session = $this->request->session();
		if ($session->check('Guardian.Guardians.id')) {
			$header = '';
			$userId = $session->read('Guardian.Guardians.id');

			if ($session->check('Guardian.Guardians.name')) {
				$header = $session->read('Guardian.Guardians.name');
			}

			$alias = $model->alias;
			$this->Navigation->addCrumb($model->getHeader($alias));
			$header = $header . ' - ' . $model->getHeader($alias);

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
						return $this->redirect(['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => $alias]);
					}
				}
			}
		} else {
			$this->Alert->warning('general.notExists');
			$event->stopPropagation();
			return $this->redirect(['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']);
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();

		if ($model->alias() != 'Guardians') {
			if ($session->check('Guardian.Guardians.id')) {
				$userId = $session->read('Guardian.Guardians.id');
				if ($model->hasField('security_user_id')) {
					$query->where([$model->aliasField('security_user_id') => $userId]);
				} else if ($model->hasField('guardian_id')) {
					$query->where([$model->aliasField('guardian_id') => $userId]);
				}
			} else {
				$this->Alert->warning('general.noData');
				$event->stopPropagation();
				return $this->redirect(['action' => 'index']);
			}
		}
		return $options;
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

	public function getImage($id) {
		$this->autoRender = false;
		$this->ControllerAction->autoRender = false;
		$this->Image->getUserImage($id);
	}
}

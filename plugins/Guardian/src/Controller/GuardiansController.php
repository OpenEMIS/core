<?php
namespace Guardian\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use App\Controller\AppController;

class GuardiansController extends AppController {
	public $activeObj = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('User.Users');
		$this->ControllerAction->model()->addBehavior('Guardian.Guardian');
        $this->ControllerAction->model()->addBehavior('TrackActivity', ['target' => 'Guardian.GuardianActivities', 'key' => 'security_user_id', 'session' => 'Users.id']);

		$this->ControllerAction->models = [
			'Accounts' 			=> ['className' => 'User.Accounts', 'actions' => ['view', 'edit']],
			'Contacts' 			=> ['className' => 'User.Contacts'],
			'Identities' 		=> ['className' => 'User.Identities'],
			'Languages' 		=> ['className' => 'User.UserLanguages'],
			'Comments' 			=> ['className' => 'User.Comments'],
			'Attachments' 		=> ['className' => 'User.Attachments'],
			'History' 			=> ['className' => 'Guardian.GuardianActivities', 'actions' => ['index']],
		];

		$this->loadComponent('Paginator');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Guardian', ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		$header = __('Guardians');

		if ($action == 'index') {
			$session->delete('Guardians.security_user_id');
			$session->delete('Users.id');
		} elseif ($session->check('Guardians.security_user_id') || $session->check('Users.id') || $action == 'view' || $action == 'edit') {
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Guardians.security_user_id')) {
				$id = $session->read('Guardians.security_user_id');
			} else if ($session->check('Users.id')) {
				$id = $session->read('Users.id');
			}
			if (!empty($id)) {
				$this->activeObj = $this->Users->get($id);
				$name = $this->activeObj->name;
				$header = $name .' - Overview';
				$this->Navigation->addCrumb($name, ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'view', $id]);
			} else {
				return $this->redirect(['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']);
			}
		}

		$this->set('contentHeader', $header);
	}

	public function onInitialize($event, $model) {
		/**
		 * if guardian object is null, it means that guardian.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */
		if (!is_null($this->activeObj)) {
			$session = $this->request->session();
			$action = false;
			$params = $this->request->params;
			if (isset($params['pass'][0])) {
				$action = $params['pass'][0];
			}

			if ($action) {
				$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => $model->alias]);
				if (strtolower($action) != 'index')	{
					$this->Navigation->addCrumb(ucwords($action));
				}
			} else {
				$this->Navigation->addCrumb($model->getHeader($model->alias));
			}

			$header = $this->activeObj->name . ' - ' . $model->getHeader($model->alias);

			if ($model->hasField('security_user_id') && !is_null($this->activeObj)) {
				$model->fields['security_user_id']['type'] = 'hidden';
				$model->fields['security_user_id']['value'] = $this->activeObj->id;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$exists = $model->exists([
						$model->aliasField($model->primaryKey()) => $modelId,
						$model->aliasField('security_user_id') => $this->activeObj->id
						]);
					
					/**
					 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
					 */
					if (!$exists) {
						$this->Alert->warning('general.notExists');
						return $this->redirect(['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => $model->alias]);
					}
				}
			}

			$this->set('contentHeader', $header);
		} else {
			$this->Alert->warning('general.notExists');
			$event->stopPropagation();
			return $this->redirect(['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']);
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();

		if (in_array($model->alias, array_keys($this->ControllerAction->models))) {
			if ($session->check('Guardians.security_user_id')) {
				$userId = $session->read('Guardians.security_user_id');

				if ($model->hasField('security_user_id')) {
					$query->where([$model->aliasField('security_user_id') => $userId]);
				}
			} else {
				$this->Alert->warning('general.noData');
				$this->redirect(['action' => 'index']);
				return false;
			}
		}
		return $options;
	}
}

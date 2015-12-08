<?php
namespace App\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;

class PreferencesController extends AppController {
	public $activeObj = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Users');
		$this->ControllerAction->models = [
			'Users' => ['className' => 'Users'],
			'Contacts'=> ['className' => 'UserContacts']
		];
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = __('Preferences');

		$action = $this->request->params['action'];
		$session = $this->request->session();
		if ($action == 'view') {
			$session->write($this->name.'.security_user_id', $this->Auth->user('id'));
		} else {
			$this->activeObj = $this->Users->get($this->Auth->user('id'));
			$name = $this->activeObj->name;
		}

		$this->Navigation->addCrumb('Preferences', ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']);

		$this->set('contentHeader', $header);
	}

	public function onInitialize($event, $model) {
		if (!is_null($this->activeObj)) {
			if ($model->hasField('security_user_id') && !is_null($this->activeObj)) {
				$model->fields['security_user_id']['type'] = 'hidden';
				$model->fields['security_user_id']['value'] = $this->activeObj->id;
			}

		}
	}

	public function index() {
		$userId = $this->Auth->user('id');
		return $this->redirect(['plugin' => false, 'controller' => $this->name, 'action' => 'Users', 'view', $userId]);
	}

	public function getTabElements() {
		$userId = $this->Auth->user('id');
		$tabElements = [
			'account' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'view', $userId],
				'text' => __('Account')
			],
			'password' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Users', 'password'],
				'text' => __('Password')
			],
			'contacts' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Contacts'],
				'text' => __('Contacts')
			]
		];
		return $tabElements;
	}

	public function beforePaginate(Event $event, $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();

		if ($session->check($this->name.'.security_user_id')) {
			if ($model->hasField('security_user_id')) {
				$userId = $this->Auth->user('id');
				$query->where([$model->aliasField('security_user_id') => $userId]);
			}
		} else {
			$this->Alert->warning('general.noData');
			$event->stopPropagation();
			return $this->redirect(['action' => 'index']);
		}
	}
}

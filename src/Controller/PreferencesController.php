<?php
namespace App\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;

class PreferencesController extends AppController {
	public $activeObj = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Users');
		$this->ControllerAction->models = [
			'Users' 				=> ['className' => 'Users'],
			'Contacts'				=> ['className' => 'UserContacts'],
			'Identities' 			=> ['className' => 'User.Identities'],
			'Nationalities' 		=> ['className' => 'User.Nationalities'],
			'Languages' 			=> ['className' => 'User.UserLanguages'],
			'Comments' 				=> ['className' => 'User.Comments'],
			'Attachments' 			=> ['className' => 'User.Attachments'],
			'History' 				=> ['className' => 'User.UserActivities', 'actions' => ['index']],
			'SpecialNeeds' 			=> ['className' => 'User.SpecialNeeds'],
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

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
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

	public function getUserTabElements() {
		$Config = TableRegistry::get('ConfigItems');
		$canChangeAdminPassword = $Config->value('change_password');
		$isSuperAdmin = $this->Auth->user('super_admin');
		$userId = $this->Auth->user('id');
		$tabElements = [
			'Account' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'view', $userId],
				'text' => __('Account')
			],
			'Password' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Users', 'password'],
				'text' => __('Password')
			],
			'Contacts' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Contacts'],
				'text' => __('Contacts')
			],
			'Identities' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Identities'],
				'text' => __('Identities')
			],
			'Nationalities' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Nationalities'],
				'text' => __('Nationalities')	
			],
			'Languages' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Languages'],
				'text' => __('Languages')	
			],
			'Comments' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Comments'],
				'text' => __('Comments')	
			],
			'Attachments' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Attachments'],
				'text' => __('Attachments')	
			],
			'SpecialNeeds' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'SpecialNeeds'],
				'text' => __('Special Needs')
			],
			'History' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'History'],
				'text' => __('History')	
			]
		];
		if (!$canChangeAdminPassword && $isSuperAdmin) {
			unset($tabElements['Password']);
		}
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

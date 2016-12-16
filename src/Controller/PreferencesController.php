<?php
namespace App\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;

use App\Controller\AppController;

class PreferencesController extends AppController {
	public $activeObj = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Users');
		$this->ControllerAction->models = [
			'Users' 				=> ['className' => 'Users'],
			'Account' 				=> ['className' => 'UserAccounts', 'actions' => ['view', 'edit']],
			'Nationalities' 		=> ['className' => 'User.Nationalities'],
			'Attachments' 			=> ['className' => 'User.Attachments'],
			'History' 				=> ['className' => 'User.UserActivities', 'actions' => ['index']],
		];
	}

	public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        return true;
    }

    // CAv4
    public function Nationalities()	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']); }
    public function Languages()		{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserLanguages']); }
    public function SpecialNeeds()	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.SpecialNeeds']); }
    public function Comments()		{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Comments']); }
    public function Contacts()		{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'UserContacts']); }
    public function Identities() 	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']); }
    // End

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = __('Preferences');

		$action = $this->request->params['action'];
		$this->activeObj = $this->Users->get($this->Auth->user('id'));

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
		return $this->redirect(['plugin' => false, 'controller' => $this->name, 'action' => 'Users', 'view', $this->ControllerAction->paramsEncode(['id' => $userId])]);
	}

	public function getUserTabElements() {
		$Config = TableRegistry::get('Configuration.ConfigItems');
		$canChangeAdminPassword = $Config->value('change_password');
		$isSuperAdmin = $this->Auth->user('super_admin');
		$userId = $this->Auth->user('id');
		$tabElements = [
			'General' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'view', $this->ControllerAction->paramsEncode(['id' => $userId])],
				'text' => __('General')
			],
			'Account' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Account', 'view', $this->ControllerAction->paramsEncode(['id' => $userId])],
				'text' => __('Account')
			],
			'Contacts' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Contacts'],
				'text' => __('Contacts')
			],
			'Identities' => [
				'url' => ['plugin' => null, 'controller' => $this->name, 'action' => 'Identities'],
				'text' => __('Identities')
			],
			'UserNationalities' => [
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
		$user = $this->Auth->user();
		if (isset($user['id'])) {
			$userId = $user['id'];
			$query->where([$model->aliasField('security_user_id') => $userId]);
		} else {
			$this->Alert->warning('general.noData');
			$event->stopPropagation();
			return $this->redirect(['action' => 'index']);
		}
	}

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra) {
        $this->beforePaginate($event, $model, $query, $extra);
    }
}

<?php
namespace Security\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;

class SecuritiesController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Accounts'		=> ['className' => 'User.Accounts', 'actions' => ['view', 'edit']],
			'Users'			=> ['className' => 'Security.Users', 'searchFunction' => false],
			'UserGroups'	=> ['className' => 'Security.UserGroups'],
			'SystemGroups'	=> ['className' => 'Security.SystemGroups', 'actions' => ['!add', '!remove']],
			'Roles'			=> ['className' => 'Security.SecurityRoles'],
			'Permissions'	=> ['className' => 'Security.Permissions', 'actions' => ['index']]
		];
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = 'Security';
		$this->Navigation->addCrumb($header, ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'index']);
		$this->Navigation->addCrumb($this->request->action);
		
		$this->set('contentHeader', __($header));
	}

	public function onInitialize(Event $event, Table $model) {
		$header = __('Security');
		$header .= ' - ' . __($model->getHeader($model->alias));
		$this->set('contentHeader', $header);
	}

	public function index() {
		return $this->redirect(['action' => 'Users']);
	}
}

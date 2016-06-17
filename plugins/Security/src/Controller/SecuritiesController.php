<?php
namespace Security\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;

class SecuritiesController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Accounts'		=> ['className' => 'Security.Accounts', 'actions' => ['view', 'edit']],
			'Users'			=> ['className' => 'Security.Users'],
			'UserGroups'	=> ['className' => 'Security.UserGroups'],
			'SystemGroups'	=> ['className' => 'Security.SystemGroups', 'actions' => ['!add', '!edit', '!remove']],
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

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = __('Security');
		$header .= ' - ' . __($model->getHeader($model->alias));
		$this->set('contentHeader', $header);
	}

	public function index() {
		return $this->redirect(['action' => 'Users']);
	}

	public function getUserTabElements($options = []) {
		$plugin = $this->plugin;
		$name = $this->name;

		$id = (array_key_exists('id', $options))? $options['id']: $this->request->session()->read($name.'.id');

		$tabElements = [
			$this->name => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Users', 'view', $id],
				'text' => __('Details')
			],
			'Accounts' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $id],
				'text' => __('Account')	
			]
		];

		return $tabElements;
	}
}

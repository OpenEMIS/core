<?php
namespace Security\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class SecuritiesController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Accounts' => ['className' => 'User.Accounts'],
			'Users' => ['className' => 'User.Users'],
			'Groups' => ['className' => 'Security.SecurityGroups'],
			'Roles' => ['className' => 'Security.SecurityRoles']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Security', ['plugin' => 'Security', 'controller' => 'Securities', 'action' => $this->request->action]);
		$this->Navigation->addCrumb($this->request->action);

    	$header = __('Security');
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			$header .= ' - ' . $model->alias;

			$controller->set('contentHeader', $header);
		};

		$this->ControllerAction->beforePaginate = function($model, $options) {
			// logic here
			return $options;
		};

		$this->set('contentHeader', $header);
	}
}

<?php
namespace CustomField\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class CustomFieldsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Modules' => ['className' => 'CustomField.CustomModules'],
			'Groups' => ['className' => 'CustomField.CustomGroups'],
			'Fields' => ['className' => 'CustomField.CustomFields'],
			'Forms' => ['className' => 'CustomField.CustomForms']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Modules' => [
				'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Modules'],
				'text' => __('Modules')
			],
			'Groups' => [
				'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Groups'],
				'text' => __('Groups')
			],
			'Fields' => [
				'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Fields'],
				'text' => __('Fields')
			],
			'Forms' => [
				'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Forms'],
				'text' => __('Forms')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

    public function onInitialize(Event $event, Table $model) {
		$header = __('Custom Field');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Custom Field', ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }

    public function beforePaginate(Event $event, Table $model, array $options) {
    	return $options;
    }
}

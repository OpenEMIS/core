<?php
namespace CustomField\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;

class CustomFieldsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Modules' => ['className' => 'CustomField.CustomModules'],
			'Fields' => ['className' => 'CustomField.CustomFields'],
			'Pages' => ['className' => 'CustomField.CustomForms'],
			'Records' => ['className' => 'CustomField.CustomRecords']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$plugin = $this->plugin;
    	$name = $this->name;

		$tabElements = [
			'Modules' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Modules'],
				'text' => __('Modules')
			],
			'Fields' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Fields'],
				'text' => __('Fields')
			],
			'Pages' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Pages'],
				'text' => __('Pages')
			],
			'Records' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Records'],
				'text' => __('Records')
			]
		];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = __('Custom Field');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Custom Field', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}

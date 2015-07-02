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
			//'Modules' => ['className' => 'CustomField.CustomModules'],
			'Fields' => ['className' => 'CustomField.CustomFields'],
			'Pages' => ['className' => 'CustomField.CustomForms'],
			//'Records' => ['className' => 'CustomField.CustomRecords']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			// 'Modules' => [
			// 	'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Modules'],
			// 	'text' => __('Modules')
			// ],
			'Fields' => [
				'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Fields'],
				'text' => __('Fields')
			],
			'Pages' => [
				'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Pages'],
				'text' => __('Pages')
			],
			// 'Records' => [
			// 	'url' => ['plugin' => 'CustomField', 'controller' => 'CustomFields', 'action' => 'Records'],
			// 	'text' => __('Records')
			// ]
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
}

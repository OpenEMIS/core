<?php
namespace Survey\Controller;

use Survey\Controller\AppController;
use Cake\Event\Event;

class SurveyTemplatesController extends AppController {
    private $selectedModule = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Survey.SurveyTemplates');
		$this->loadComponent('Paginator');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.beforePaginate'] = 'beforePaginate';
        return $events;
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
        $this->Navigation->addCrumb('Survey', ['plugin' => 'Survey', 'controller' => 'SurveyTemplates', 'action' => 'index']);
        $this->Navigation->addCrumb('Templates');

    	$header = __('Templates');
    	$this->set('contentHeader', $header);

        if ($this->request->action == 'index') {
            $query = $this->request->query;

            $toolbarElements = [
                ['name' => 'Survey.controls', 'data' => [], 'options' => []]
            ];

            $modules = $this->SurveyTemplates->SurveyModules->find('list')->toArray();
            $this->selectedModule = isset($query['module']) ? $query['module'] : key($modules);

            $moduleOptions = [];
            foreach ($modules as $key => $module) {
                $moduleOptions['module=' . $key] = $module;
            }

            $this->set('toolbarElements', $toolbarElements);
            $this->set('selectedModule', $this->selectedModule);
            $this->set('moduleOptions', $moduleOptions);
        } else if ($this->request->action == 'add' || $this->request->action == 'edit') {
            $this->SurveyTemplates->fields['survey_module_id']['type'] = 'select';

            $this->ControllerAction->setFieldOrder('survey_module_id', 1);
        }
    }

    public function beforePaginate($event, $model, $options) {
        if (!is_null($this->selectedModule)) {
            $options['conditions'][] = [
                $model->aliasField('survey_module_id') => $this->selectedModule
            ];
        }

        return $options;
    }
}

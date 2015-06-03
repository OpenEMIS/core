<?php
namespace Survey\Controller;

use Survey\Controller\AppController;
use Cake\Event\Event;

class SurveyStatusesController extends AppController {
    private $selectedModule = null;
    private $selectedTemplate = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Survey.SurveyStatuses');
		$this->loadComponent('Paginator');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.beforePaginate'] = 'beforePaginate';
        return $events;
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
        $this->Navigation->addCrumb('Survey', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses', 'action' => 'index']);
        $this->Navigation->addCrumb('Statuses');

    	$header = __('Statuses');
    	$this->set('contentHeader', $header);

        if ($this->request->action == 'index') {
            $query = $this->request->query;

            $SurveyModules = $this->SurveyStatuses->SurveyTemplates->SurveyModules;
            $SurveyTemplates = $this->SurveyStatuses->SurveyTemplates;

            $toolbarElements = [
                ['name' => 'Survey.controls', 'data' => [], 'options' => []]
            ];

            $modules = $SurveyModules->find('list')->toArray();
            $this->selectedModule = isset($query['module']) ? $query['module'] : key($modules);
            $moduleOptions = [];
            foreach ($modules as $key => $module) {
                $moduleOptions['module=' . $key] = $module;
            }

            $templates = $SurveyTemplates->find('list')
                ->where([$SurveyTemplates->aliasField('survey_module_id') => $this->selectedModule])
                ->toArray();
            $this->selectedTemplate = isset($query['template']) ? $query['template'] : key($templates);
            $templateOptions = [];
            foreach ($templates as $key => $template) {
                $templateOptions['template=' . $key] = $template;
            }

            $this->set('toolbarElements', $toolbarElements);
            $this->set('selectedModule', $this->selectedModule);
            $this->set('moduleOptions', $moduleOptions);
            $this->set('selectedTemplate', $this->selectedTemplate);
            $this->set('templateOptions', $templateOptions);
        } else if ($this->request->action == 'add' || $this->request->action == 'edit') {
        }
    }

    public function beforePaginate($event, $model, $options) {
        if (!is_null($this->selectedTemplate)) {
            $options['conditions'][] = [
                $model->aliasField('survey_template_id') => $this->selectedTemplate
            ];
            $options['order'] = [
                $model->aliasField('date_disabled DESC')
            ];
        }
        return $options;
    }
}

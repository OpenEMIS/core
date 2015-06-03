<?php
namespace Survey\Controller;

use Survey\Controller\AppController;
use Cake\Event\Event;

class SurveyQuestionsController extends AppController {
    private $selectedModule = null;
    private $selectedTemplate = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Survey.SurveyQuestions');
		$this->loadComponent('Paginator');
		$this->loadComponent('CustomField.CustomField');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.beforePaginate'] = 'beforePaginate';
        return $events;
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
        $this->Navigation->addCrumb('Survey', ['plugin' => 'Survey', 'controller' => 'SurveyQuestions', 'action' => 'index']);
        $this->Navigation->addCrumb('Questions');

    	$header = __('Questions');
    	$this->set('contentHeader', $header);

        if ($this->request->action == 'index') {
            $query = $this->request->query;

            $SurveyModules = $this->SurveyQuestions->SurveyTemplates->SurveyModules;
            $SurveyTemplates = $this->SurveyQuestions->SurveyTemplates;

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
                $model->aliasField('order'),
                $model->aliasField('id')
            ];
        }
        return $options;
    }
}

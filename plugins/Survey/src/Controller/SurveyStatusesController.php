<?php
namespace Survey\Controller;

use Survey\Controller\AppController;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class SurveyStatusesController extends AppController {
    private $selectedModule = null;
    private $selectedTemplate = null;
    private $surveyModuleKey = null;
    private $surveyTemplateKey = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Survey.SurveyStatuses');
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Statuses');
    	$this->set('contentHeader', $header);

        if ($this->request->action == 'index') {
            $query = $this->request->query;

            $SurveyModules = $this->SurveyStatuses->SurveyTemplates->SurveyModules;
            $SurveyTemplates = $this->SurveyStatuses->SurveyTemplates;

            $this->surveyModuleKey = Inflector::underscore(Inflector::singularize($SurveyModules->alias())) . '_id';
            $this->surveyTemplateKey = Inflector::underscore(Inflector::singularize($SurveyTemplates->alias())) . '_id';

            $toolbarElements = [
                ['name' => 'Survey.controls', 'data' => [], 'options' => []]
            ];

            $modules = $SurveyModules->getList();
            $this->selectedModule = isset($query['module']) ? $query['module'] : key($modules);
            $moduleOptions = array();
            foreach ($modules as $key => $module) {
                $moduleOptions['module=' . $key] = $module;
            }

            $templateListOptions = [
                'conditions' => [
                    $SurveyTemplates->alias().'.'.$this->surveyModuleKey => $this->selectedModule
                ]
            ];
            $templates = $SurveyTemplates->getList($templateListOptions);
            $this->selectedTemplate = isset($query['template']) ? $query['template'] : key($templates);
            $templateOptions = array();
            foreach ($templates as $key => $template) {
                $templateOptions['template=' . $key] = $template;
            }

            $this->ControllerAction->beforePaginate = function($model, $options) {
                if (!is_null($this->selectedTemplate)) {
                    $options['conditions'][] = [
                        $model->alias().'.'.$this->surveyTemplateKey => $this->selectedTemplate
                    ];
                    $options['order'] = [
                        $model->alias().'.date_disabled DESC'
                    ];
                }
                return $options;
            };

            $this->set('toolbarElements', $toolbarElements);
            $this->set('selectedModule', $this->selectedModule);
            $this->set('moduleOptions', $moduleOptions);
            $this->set('selectedTemplate', $this->selectedTemplate);
            $this->set('templateOptions', $templateOptions);
        } else if ($this->request->action == 'add' || $this->request->action == 'edit') {
        }
    }
}

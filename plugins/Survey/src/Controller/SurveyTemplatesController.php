<?php
namespace Survey\Controller;

use Survey\Controller\AppController;
use Cake\Event\Event;

class SurveyTemplatesController extends AppController {

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Survey.SurveyTemplates');
		$this->loadComponent('Paginator');
		
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Templates');
    	$this->set('contentHeader', $header);

        if ($this->request->action = 'add' || $this->request->action = 'edit') {
            $moduleOptions = $this->SurveyTemplates->SurveyModules->getList();

            $this->SurveyTemplates->fields['survey_module_id']['type'] = 'select';
            $this->SurveyTemplates->fields['survey_module_id']['options'] = $moduleOptions;
        }
    }
}

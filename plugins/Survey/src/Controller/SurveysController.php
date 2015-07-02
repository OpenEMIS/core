<?php
namespace Survey\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;

class SurveysController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Questions' => ['className' => 'Survey.SurveyQuestions'],
			'Forms' => ['className' => 'Survey.SurveyForms'],
			'Status' => ['className' => 'Survey.SurveyStatuses']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Questions' => [
				'url' => ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => 'Questions'],
				'text' => __('Questions')
			],
			'Forms' => [
				'url' => ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => 'Forms'],
				'text' => __('Forms')
			],
			'Status' => [
				'url' => ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => 'Status'],
				'text' => __('Status')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

	public function onInitialize(Event $event, Table $model) {
		$header = __('Survey');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Survey', ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }

    public function beforePaginate(Event $event, Table $model, array $options) {
    	if ($model->alias == 'Status') {
	        list($statusOptions, $selectedStatus, $moduleOptions, $selectedModule, $formOptions, $selectedForm) = array_values($this->getSelectOptions());
	        $this->set(compact('statusOptions', 'selectedStatus', 'moduleOptions', 'selectedModule', 'formOptions', 'selectedForm'));

	        $todayDate = date('Y-m-d');
	        $todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));

	        $options['conditions'][] = [
	            $model->aliasField('survey_form_id') => $selectedForm
	        ];
	        $options['conditions'][] = ($selectedStatus == 1) ? [$model->aliasField('date_disabled >=') => $todayTimestamp] : [$model->aliasField('date_disabled <') => $todayTimestamp];
    	}
    	return $options;
    }

    public function getSelectOptions() {
        //Return all required options and their key
        $query = $this->request->query;

        $statusOptions = ['1' => 'Current', '0' => 'Past'];
        $selectedStatus = isset($query['status']) ? $query['status'] : key($statusOptions);

        $CustomModules = $this->SurveyStatuses->SurveyForms->CustomModules;
        $moduleOptions = $CustomModules
        	->find('list', ['keyField' => 'id', 'valueField' => 'code'])
        	->where([$CustomModules->aliasField('parent_id') => 0])
        	->toArray();
        $selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

        $formOptions = $this->SurveyStatuses->SurveyForms
        	->find('list')
        	->where([$this->SurveyStatuses->SurveyForms->aliasField('custom_module_id') => $selectedModule])
        	->toArray();
        $selectedForm = isset($query['form']) ? $query['form'] : key($formOptions);

        return compact('statusOptions', 'selectedStatus', 'moduleOptions', 'selectedModule', 'formOptions', 'selectedForm');
    }
}

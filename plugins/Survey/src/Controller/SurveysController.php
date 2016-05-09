<?php
namespace Survey\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Utility\Inflector;

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
		$this->attachAngularModules();
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Questions' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Questions'],
				'text' => __('Questions')
			],
			'Forms' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Forms'],
				'text' => __('Forms')
			],
			'Rules' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Rules'],
				'text' => __('Rules')
			],
			'Status' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Status'],
				'text' => __('Status')
			]
		];
		$name = $this->name;
		$action = $this->request->action;
		$actionName = __(Inflector::humanize($action));
		$header = $name .' - '.$actionName;
		$this->Navigation->addCrumb(__($name), ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $action]);
		$this->Navigation->addCrumb($actionName);
		$this->set('contentHeader', $header);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

	public function Rules() {
		$pass = isset($this->request->pass[0]) ? $this->request->pass[0] : 'index';
		
		if ($pass == 'index') {
			$this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Survey.SurveyRules']);
		} else {
			$this->set('ngController', 'SurveyRulesCtrl as SurveyRulesController');
		}
		
	}

	private function attachAngularModules() {
		$action = $this->request->action;
		$pass = isset($this->request->pass[0]) ? $this->request->pass[0] : 'index';
		// pr($action);
		switch ($action) {
			case 'Rules':
				if ($pass != 'index') {
					$this->Angular->addModules([
						'alert.svc',
						'survey.rules.ctrl',
						'survey.rules.svc'
					]);
				}
				break;
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
    	if ($model->alias == 'Status') {
	        list($statusOptions, $selectedStatus, $moduleOptions, $selectedModule, $formOptions, $selectedForm) = array_values($this->_getSelectOptions());
	        $this->set(compact('statusOptions', 'selectedStatus', 'moduleOptions', 'selectedModule', 'formOptions', 'selectedForm'));

	        $todayDate = date('Y-m-d');
	        $todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));

	        $query->where([$model->aliasField('survey_form_id') => $selectedForm]);
	        if ($selectedStatus == 1) {
				$query->where([$model->aliasField('date_disabled >=') => $todayTimestamp]);
	        } else {
				$query->where([$model->aliasField('date_disabled <') => $todayTimestamp]);
	        }
    	}
    }

    public function _getSelectOptions() {
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

<?php
namespace Survey\Controller;

use Survey\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;

class SurveyStatusesController extends AppController {
    private $selectedModule = null;
    private $selectedForm = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Survey.SurveyStatuses');
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $header = __('Survey Statuses');
        $this->Navigation->addCrumb('Survey Statuses', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses', 'action' => 'index']);
        $this->set('contentHeader', $header);
    }

    public function beforePaginate(Event $event, Table $model, array $options) {
        list($statusOptions, $selectedStatus, $moduleOptions, $selectedModule, $formOptions, $selectedForm) = array_values($this->getSelectOptions());
        $this->set(compact('statusOptions', 'selectedStatus', 'moduleOptions', 'selectedModule', 'formOptions', 'selectedForm'));

        $todayDate = date('Y-m-d');
        $todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));

        $options['conditions'][] = [
            $model->aliasField('survey_form_id') => $selectedForm
        ];
        $options['conditions'][] = ($selectedStatus == 1) ? [$model->aliasField('date_disabled >=') => $todayTimestamp] : [$model->aliasField('date_disabled <') => $todayTimestamp];

        return $options;
    }

    public function getSelectOptions() {
        //Return all required options and their key
        $query = $this->request->query;

        $statusOptions = ['1' => 'Current', '0' => 'Past'];
        $selectedStatus = isset($query['status']) ? $query['status'] : key($statusOptions);

        $CustomModules = $this->SurveyStatuses->SurveyForms->CustomModules;
        $moduleOptions = $CustomModules->find('list')->where([$CustomModules->aliasField('parent_id') => 0])->toArray();
        $selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

        $formOptions = $this->SurveyStatuses->SurveyForms->find('list')->where([$this->SurveyStatuses->SurveyForms->aliasField('custom_module_id') => $selectedModule])->toArray();
        $selectedForm = isset($query['form']) ? $query['form'] : key($formOptions);

        return compact('statusOptions', 'selectedStatus', 'moduleOptions', 'selectedModule', 'formOptions', 'selectedForm');
    }
}

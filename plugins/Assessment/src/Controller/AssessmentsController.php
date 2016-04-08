<?php
namespace Assessment\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class AssessmentsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->loadComponent('Paginator');
		$model = $this->request->action;
		$action = isset($this->request->pass[0]) ? $this->request->pass[0] : '';
		if ($model=='Assessments') {
			switch ($action) {
				case 'add':
					$this->Angular->addModules([
						'kd.module'
					]);
				break;
			}
		}
    }

	// CAv4
	public function GradingTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Assessment.AssessmentGradingTypes']); }
	public function Assessments() {
		$model = $this->request->action;
		$action = isset($this->request->pass[0]) ? $this->request->pass[0] : '';
		if ($model=='Assessments') {
			switch ($action) {
				case 'add':
					$this->set('ngController', 'kdCtrl');
				break;
			}
		}
		$this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Assessment.Assessments']);
	}
	// End

	public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra) {
		$session = $this->request->session();

		if (!$this->request->is('ajax')) {
			if ($model->hasField('institution_id')) {
				if (!$session->check('Institution.Institutions.id')) {
					$this->Alert->error('general.notExists');
					// should redirect
				} else {
					$query->where([$model->aliasField('institution_id') => $session->read('Institution.Institutions.id')]);
				}
			}
		}
	}

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Assessments' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Assessments'],
				'text' => __('Assessments')
			],
			'GradingTypes' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'GradingTypes'],
				'text' => __('Grading Types')
			],
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);

	    if ($this->request->action=='addNewAssessmentPeriod') {
	    	$this->request->params['_ext'] = 'json';
	    }

	}

	public $components = [
		'RequestHandler'
	];

    public function addNewAssessmentPeriod() {
    	$model = TableRegistry::get('Assessment.AssessmentPeriods');
    	$this->set([
			'data' => $model->addNewAssessmentPeriod(),
            '_serialize' => ['data']
        ]);
    }

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = __('Assessment');
		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Assessments', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}

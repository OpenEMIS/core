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
	}

	// CAv4
	public function Assessments() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Assessment.Assessments']); }
	public function AssessmentPeriods() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Assessment.AssessmentPeriods']); }
	public function GradingTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Assessment.AssessmentGradingTypes']); }
	// End

	public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Assessments' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Assessments'],
				'text' => __('Templates')
			],
			'AssessmentPeriods' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'AssessmentPeriods'],
				'text' => __('Assessment Periods')
			],
			'GradingTypes' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'GradingTypes'],
				'text' => __('Grading Types')
			],
		];

		$tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);

	    if ($this->request->action=='addNewAssessmentPeriod') {
	    	$this->request->params['_ext'] = 'json';
	    }

	}

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = __('Assessment');
		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Assessments', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}

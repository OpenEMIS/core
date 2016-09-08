<?php
namespace Examination\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;

class ExaminationsController extends AppController
{
	public function initialize() {
        parent::initialize();
        $this->attachAngularModules();
    }

    // CAv4
    public function Exams() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.Exams']); }
    public function GradingTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationGradingTypes']); }
    public function Centres() {  $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.Centres']); }
    // public function Results() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationGradingTypes']); }
    // End

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        if ($this->request->action=='addNewAssessmentPeriod') {
            $this->request->params['_ext'] = 'json';
        }
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $header = __('Examination');
        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Examinations', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function getExamsTab()
    {
    	$tabElements = [
            'Exams' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Exams'],
                'text' => __('Exams')
            ],
            'GradingTypes' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'GradingTypes'],
                'text' => __('Grading Types')
            ],
        ];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    private function attachAngularModules() {
        $action = $this->request->action;

        switch ($action) {
            case 'Results':
                // $this->Angular->addModules([
                //     'alert.svc',
                //     'institutions.results.ctrl',
                //     'institutions.results.svc'
                // ]);
                break;
        }
    }
}

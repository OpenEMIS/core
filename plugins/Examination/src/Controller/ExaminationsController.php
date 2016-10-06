<?php
namespace Examination\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class ExaminationsController extends AppController
{
	public function initialize() {
        parent::initialize();
        $this->attachAngularModules();
    }

    // CAv4
    public function Exams() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.Examinations']); }
    public function GradingTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationGradingTypes']); }
    public function ExamCentres($pass = 'index')
    {
        // if ($pass == 'add') {
        //     $this->set('ngController', 'ExaminationCentresCtrl as ExamCentreController');
        //     $this->render('examinationCentres');
        // } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentres']);
        // }
    }
    public function RegisteredStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentreStudents']); }
    public function NotRegisteredStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentreNotRegisteredStudents']); }
    // End

    // AngularJS
    public function Results() {
        $this->set('_edit', true);
        $this->set('ngController', 'ExaminationsResultsCtrl as ExaminationsResultsController');
    }
    // End

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $action = $this->request->params['action'];

        if ($action == 'Results') {
            $header = __('Examination');
            $header .= ' - '.__(Inflector::humanize($action));

            $this->Navigation->addCrumb('Examination', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $action]);
            $this->Navigation->addCrumb(Inflector::humanize($action));

            $this->set('contentHeader', $header);
        }
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $header = __('Examination');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Examination', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
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
            'ExamCentres' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamCentres'],
                'text' => __('Exam Centres')
            ],
            'GradingTypes' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'GradingTypes'],
                'text' => __('Grading Types')
            ],
        ];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function getStudentsTab()
    {
        $tabElements = [
            'RegisteredStudents' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'RegisteredStudents'],
                'text' => __('Registered')
            ],
            'NotRegisteredStudents' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'NotRegisteredStudents'],
                'text' => __('Not Registered')
            ]
        ];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    private function checkExamCentresPermission() {
        return $this->Auth->user('super_admin') == 1 || $this->AccessControl->check(['Examinations', 'Centres', 'add']);
    }

    private function attachAngularModules() {
        $action = $this->request->action;
    //     $pass = isset($this->request->pass[0]) ? $this->request->pass[0] : 'index';
        switch ($action) {
    //         case 'Centres':
    //             if ($pass == 'add' && $this->checkExamCentresPermission()) {
    //                 $this->Angular->addModules([
    //                     'alert.svc',
    //                     'examination.centres.ctrl',
    //                     'examination.centres.svc'
    //                 ]);
    //             }
    //             break;
            case 'Results':
                $this->Angular->addModules([
                    'alert.svc',
                    'examinations.results.ctrl',
                    'examinations.results.svc'
                ]);
                break;
        }
    }
}
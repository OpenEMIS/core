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
    public function Centres($pass = 'index')
    {
        // if ($pass == 'add') {
        //     $this->set('ngController', 'ExaminationCentresCtrl as ExamCentreController');
        //     $this->render('examinationCentres');
        // } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentres']);
        // }
    }
    public function Students() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationStudents']); }
    public function NotRegisteredStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationNotRegisteredStudents']); }
    // End

    // AngularJS
    public function Results() {
        $this->set('_edit', true);
        $this->set('ngController', 'ExaminationsResultsCtrl as ExaminationsResultsController');
    }
    // End

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $header = __('Examination');
        $this->Navigation->addCrumb('Examinations');
        $action = $this->request->params['action'];
        $header = $header .' - '.__(Inflector::humanize($action));
        $this->Navigation->addCrumb(__(Inflector::humanize($action)), ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => $action]);
        $this->set('contentHeader', $header);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $action = $model->action;
        if ($action != 'index') {
            $this->Navigation->addCrumb(__(Inflector::humanize($action)));
        }
    }

    public function getExamsTab()
    {
    	$tabElements = [
            'Exams' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Exams'],
                'text' => __('Exams')
            ],
            'Centres' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Centres'],
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
            'Students' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Students'],
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

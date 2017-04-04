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
        $this->ControllerAction->models = [
            'ImportResults' => ['className' => 'Examination.ImportResults', 'actions' => ['add']],
            'ImportExaminationCentreRooms' => ['className' => 'Examination.ImportExaminationCentreRooms', 'actions' => ['add']],
        ];
        $this->attachAngularModules();
    }

    // CAv4
    public function Exams() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.Examinations']); }
    public function GradingTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationGradingTypes']); }
    public function Centres($pass = 'index') { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentres']);}
    public function ExaminationCentres() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentresExaminations']);}
    public function RegisteredStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentreStudents']); }
    public function BulkStudentRegistration() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.BulkStudentRegistration']); }
    public function NotRegisteredStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentreNotRegisteredStudents']); }
    public function RegistrationDirectory() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.RegistrationDirectory']); }
    public function ExamCentreRooms() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentreRooms']); }
    public function LinkedInstitutionAddStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.LinkedInstitutionAddStudents']); }
    public function ExamResults() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationResults']); }
    public function ExaminationCentreStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentreStudents']); }
    public function ExaminationCentreSubjects() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentresExaminationsSubjects']); }
    // End

    // AngularJS
    public function Results() {
        $this->set('_edit', $this->AccessControl->check(['Examinations', 'Results', 'edit']));
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

        $alias = ($model->alias == 'ExamResults') ? 'Results' : $model->alias;
        $header .= ' - ' . $model->getHeader($alias);
        $this->Navigation->addCrumb('Examination', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);

        $persona = false;
        $event = new Event('Model.Navigation.breadcrumb', $this, [$this->request, $this->Navigation, $persona]);
        $event = $model->eventManager()->dispatch($event);
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

    public function getExamCentresTab($action = null)
    {
        $queryString = $this->request->query('queryString');
        $tabElements = [
            'Centres' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Centres', 'view', 'queryString' => $queryString],
                'text' => __('Overview')
            ],
            'ExaminationCentreSubjects' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExaminationCentreSubjects', 'queryString' => $queryString],
                'text' => __('Subjects')
            ],
            'ExaminationCentreStudents' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExaminationCentreStudents', 'queryString' => $queryString],
                'text' => __('Students')
            ],
            'ExamCentreRooms' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamCentreRooms', 'queryString' => $queryString],
                'text' => __('Rooms')
            ],

        ];

        $this->set('tabElements', $tabElements);
        $action = !is_null($action) ? $action : $this->request->action;
        $this->set('selectedAction', $action);
    }

    public function getStudentsTab($action = null)
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
        $action = !is_null($action) ? $action : $this->request->action;
        $this->set('selectedAction', $action);
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

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
    public function ExamCentres($pass = 'index') { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentres']);}
    public function ExamCentreExams() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentresExaminations']);}
    public function RegisteredStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentresExaminationsStudents']); }
    public function BulkStudentRegistration() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.BulkStudentRegistration']); }
    public function NotRegisteredStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentreNotRegisteredStudents']); }
    public function RegistrationDirectory() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.RegistrationDirectory']); }
    public function ExamCentreRooms() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentreRooms']); }
    public function ExamResults() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationResults']); }
    public function ExamCentreStudents() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExamCentreStudents']); }
    public function ExamCentreSubjects() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentresExaminationsSubjects']); }
    public function ExamCentreInvigilators() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentresExaminationsInvigilators']); }
    public function ExamCentreLinkedInstitutions() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Examination.ExaminationCentresExaminationsInstitutions']); }
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

            $this->Navigation->addCrumb('Examination', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamResults']);
            $this->Navigation->addCrumb('Exam Results');

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
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function getExamCentresTab($action = null)
    {
        $queryString = $this->request->query('queryString');
        $tabElements = [
            'ExamCentres' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamCentres', 'view', 'queryString' => $queryString],
                'text' => __('Overview')
            ],
            'ExamCentreRooms' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamCentreRooms', 'queryString' => $queryString],
                'text' => __('Rooms')
            ],
            'ExamCentreExams' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamCentreExams', 'queryString' => $queryString],
                'text' => __('Examinations')
            ],
            'ExamCentreSubjects' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamCentreSubjects', 'queryString' => $queryString],
                'text' => __('Subjects')
            ],
            'ExamCentreStudents' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamCentreStudents', 'queryString' => $queryString],
                'text' => __('Students')
            ],
            'ExamCentreInvigilators' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamCentreInvigilators', 'queryString' => $queryString],
                'text' => __('Invigilators')
            ],
            'ExamCentreLinkedInstitutions' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ExamCentreLinkedInstitutions', 'queryString' => $queryString],
                'text' => __('Linked Institutions')
            ]
        ];

        // pass query string for selected exam across tabs
        if (!is_null($this->request->query('examination_id'))) {
            $examinationId = $this->request->query('examination_id');
            foreach ($tabElements as $key => $obj) {
                $tabElements[$key]['url']['examination_id'] = $examinationId;
            }
        }
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
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
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $action = !is_null($action) ? $action : $this->request->action;
        $this->set('selectedAction', $action);
    }

    private function checkExamCentresPermission() {
        return $this->Auth->user('super_admin') == 1 || $this->AccessControl->check(['Examinations', 'Centres', 'add']);
    }

    private function attachAngularModules() {
        $action = $this->request->action;
        switch ($action) {
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

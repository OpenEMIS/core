<?php
namespace Institution\Controller;

use Cake\I18n\Date;
use Cake\Event\Event;

use App\Controller\PageController;

class StudentBodyMassesController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('User.UserBodyMasses');
        $this->Page->loadElementsFromTable($this->UserBodyMasses);
        $this->Page->disable(['search']); // to disable the search function
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $studentId = $session->read('Student.Students.id');
        $studentName = $session->read('Student.Students.name');

        parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
        $encodedStudentId = $this->paramsEncode(['id' => $studentId]);

        $page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb('Students', ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'Students', 'institutionId' => $encodedInstitutionId]);
        $page->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $encodedStudentId]);
        $page->addCrumb('Body Mass');

        $page->get('security_user_id')->setControlType('hidden')->setValue($studentId); // set value and hide the student_id

        $page->move('academic_period_id')->first(); // move academic_period_id to be the first

        // set header
        $page->setHeader($studentName . ' - ' . __('Body Mass'));

        // set tabElement
        $tabPlugin = 'Student';
        $tabController = 'Students';

        // overviewTab
        if ($this->AccessControl->check([$tabController, 'Healths', 'index'])) {
            $overviewTab = $page->addTab('Overview');
            $overviewTab->setTitle('Overview');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'Healths'
            ];
            $overviewTab->setUrl($url);
        }

        // allergiesTab
        if ($this->AccessControl->check([$tabController, 'HealthAllergies', 'index'])) {
            $allergiesTab = $page->addTab('Allergies');
            $allergiesTab->setTitle('Allergies');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'HealthAllergies'
            ];
            $allergiesTab->setUrl($url);
        }

        // consultationsTab
        if ($this->AccessControl->check([$tabController, 'HealthConsultations', 'index'])) {
            $consultationsTab = $page->addTab('Consultations');
            $consultationsTab->setTitle('Consultations');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'HealthConsultations'
            ];
            $consultationsTab->setUrl($url);
        }

        // familiesTab
        if ($this->AccessControl->check([$tabController, 'HealthFamilies', 'index'])) {
            $familiesTab = $page->addTab('Families');
            $familiesTab->setTitle('Families');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'HealthFamilies'
            ];
            $familiesTab->setUrl($url);
        }

        // historiesTab
        if ($this->AccessControl->check([$tabController, 'HealthHistories', 'index'])) {
            $historiesTab = $page->addTab('Histories');
            $historiesTab->setTitle('Histories');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'HealthHistories'
            ];
            $historiesTab->setUrl($url);
        }

        // immunizationsTab
        if ($this->AccessControl->check([$tabController, 'HealthImmunizations', 'index'])) {
            $immunizationsTab = $page->addTab('Immunizations');
            $immunizationsTab->setTitle('Immunizations');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'HealthImmunizations'
            ];
            $immunizationsTab->setUrl($url);
        }

        // medicationsTab
        if ($this->AccessControl->check([$tabController, 'HealthMedications', 'index'])) {
            $medicationsTab = $page->addTab('Medications');
            $medicationsTab->setTitle('Medications');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'HealthMedications'
            ];
            $medicationsTab->setUrl($url);
        }

        // testsTab
        if ($this->AccessControl->check([$tabController, 'HealthTests', 'index'])) {
            $testsTab = $page->addTab('Tests');
            $testsTab->setTitle('Tests');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'HealthTests'
            ];
            $testsTab->setUrl($url);
        }

        // bodyMassesTab
        $bodyMassesTab = $page->addTab('BodyMasses');
        $bodyMassesTab->setTitle('Body Mass');
        $url = [
                'plugin' => 'Institution',
                'institutionId' => $encodedInstitutionId,
                'controller' => 'StudentBodyMasses',
                'action' => 'index'
            ];
        $bodyMassesTab->setUrl($url);
        $bodyMassesTab->setActive('true');
        // end of set Tab

        // set tooltip
        $action = ['add', 'edit', 'view'];
        if (in_array($this->request->params['action'], $action)) {
            $page->get('height')->setLabel([
                'escape' => false,
                'class' => 'tooltip-desc',
                'text' => __('Height') . $this->tooltipMessage(__('Within 0 to 3 metre'))
            ]);

            $page->get('weight')->setLabel([
                'escape' => false,
                'class' => 'tooltip-desc',
                'text' => __('Weight') . $this->tooltipMessage(__('Within 0 to 500 kilogram'))
            ]);

            $page->get('body_mass_index')->setLabel([
                'escape' => false,
                'class' => 'tooltip-desc',
                'text' => __('Body Mass Index') . $this->tooltipMessage(__('Weight / Height<sup>2</sup>'))
            ]);
        }
        // end set tooltip

        // set queryString
        $page->setQueryString('institution_id', $institutionId);
        $page->setQueryString('security_user_id', $studentId);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['comment', 'security_user_id']);

        $requestQuery = $this->request->query;
        if (array_key_exists('sort', $requestQuery)) {
            $page->setQueryOption('sort', $requestQuery['sort']);
            $page->setQueryOption('direction', $requestQuery['direction']);
        }

        parent::index();
    }

    public function add()
    {
        $page = $this->Page;
        $page->get('body_mass_index')->setControlType('hidden');
        $requestData = $this->request->data;

        // Academic Period Field
        $periodOptions = $this->AcademicPeriods->getYearList();

        $page->get('academic_period_id')
            ->setLabel('Academic Period')
            ->setControlType('select')
            ->setId('academic_period_id')
            ->setOptions($periodOptions);
        // end Academic Period Field

        parent::add();
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->get('body_mass_index')->setControlType('hidden');

        parent::edit($id);
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }
}

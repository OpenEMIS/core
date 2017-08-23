<?php
namespace Institution\Controller;

use Cake\I18n\Date;
use Cake\Event\Event;

use App\Controller\PageController;

class BodyMassesController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->Page->loadElementsFromTable($this->BodyMasses);
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

        $page->get('user_id')->setControlType('hidden')->setValue($studentId); // set value and hide the student_id

        $page->move('academic_period_id')->first(); // move academic_period_id to be the first

        // set header
        $header = $page->getHeader();
        $page->setHeader($studentName . ' - ' . $header);

        // set tabElement
        $tabPlugin = 'Student';
        $tabController = 'Students';

        // overviewTab
        $overviewTab = $page->addTab('Overview');
        $overviewTab->setTitle('Overview');
        $url = [
            'plugin' => $tabPlugin,
            'controller' => $tabController,
            'action' => 'Healths'
        ];
        $overviewTab->setUrl($url);

        // allergiesTab
        $allergiesTab = $page->addTab('Allergies');
        $allergiesTab->setTitle('Allergies');
        $url = [
            'plugin' => $tabPlugin,
            'controller' => $tabController,
            'action' => 'HealthAllergies'
        ];
        $allergiesTab->setUrl($url);

        // consultationsTab
        $consultationsTab = $page->addTab('Consultations');
        $consultationsTab->setTitle('Consultations');
        $url = [
            'plugin' => $tabPlugin,
            'controller' => $tabController,
            'action' => 'HealthConsultations'
        ];
        $consultationsTab->setUrl($url);

        // familiesTab
        $familiesTab = $page->addTab('Families');
        $familiesTab->setTitle('Families');
        $url = [
            'plugin' => $tabPlugin,
            'controller' => $tabController,
            'action' => 'HealthFamilies'
        ];
        $familiesTab->setUrl($url);

        // historiesTab
        $historiesTab = $page->addTab('Histories');
        $historiesTab->setTitle('Histories');
        $url = [
            'plugin' => $tabPlugin,
            'controller' => $tabController,
            'action' => 'HealthHistories'
        ];
        $historiesTab->setUrl($url);

        // immunizationsTab
        $immunizationsTab = $page->addTab('Immunizations');
        $immunizationsTab->setTitle('Immunizations');
        $url = [
            'plugin' => $tabPlugin,
            'controller' => $tabController,
            'action' => 'HealthImmunizations'
        ];
        $immunizationsTab->setUrl($url);

        // medicationsTab
        $medicationsTab = $page->addTab('Medications');
        $medicationsTab->setTitle('Medications');
        $url = [
            'plugin' => $tabPlugin,
            'controller' => $tabController,
            'action' => 'HealthMedications'
        ];
        $medicationsTab->setUrl($url);

        // testsTab
        $testsTab = $page->addTab('Tests');
        $testsTab->setTitle('Tests');
        $url = [
            'plugin' => $tabPlugin,
            'controller' => $tabController,
            'action' => 'HealthTests'
        ];
        $testsTab->setUrl($url);

        // bodyMassesTab
        $bodyMassesTab = $page->addTab('BodyMasses');
        $bodyMassesTab->setTitle('Body Mass');
        $bodyMassesTab->setActive('true');
        // end of set Tab

        // set queryString
        $page->setQueryString('institution_id', $institutionId);
        $page->setQueryString('student_id', $studentId);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['comment', 'user_id']);
        $url = $page->getUrl();

        parent::index();
    }

    public function add()
    {
        $page = $this->Page;
        $requestData = $this->request->data;

        // Academic Period Field
        $periodOptions = $this->AcademicPeriods->getYearList();

        $page->get('academic_period_id')
            ->setLabel('Academic Period')
            ->setControlType('dropdown')
            ->setId('academic_period_id')
            ->setOptions($periodOptions);
        // end Academic Period Field

        // Date field
        $date = !empty($requestData['BodyMasses']['date']) ? $requestData['BodyMasses']['date']: new Date();
        $page->get('date')->setValue($date);
        // end Date field

        // Height field
        $height = !empty($requestData['BodyMasses']['height']) ? $requestData['BodyMasses']['height']: null;
        $page->get('height')
            ->setLabel([
                'escape' => false,
                'class' => 'tooltip-desc',
                'text' => __('Height') . $this->tooltipMessage(__('Within 0 to 3 Meter'))
            ])
            ->setValue($height)
        ;
        // end Height field

        // Weight field
        $weight = !empty($requestData['BodyMasses']['weight']) ? $requestData['BodyMasses']['weight']: null;
        $page->get('weight')
            ->setLabel([
                'escape' => false,
                'class' => 'tooltip-desc',
                'text' => __('Weight') . $this->tooltipMessage(__('Within 0 to 500 Kilogram'))
            ])
            ->setValue($weight);
        // end Weight field

        $page->get('body_mass_index')->setControlType('hidden');

        parent::add();

        $entity = $page->getData();
        if (!empty($entity->height) && !empty($entity->weight)) {
            $height = $entity->height;
            $weight = $entity->weight;

            $bmi = $this->getBodyMassIndex($height, $weight);

            $entity->body_mass_index = $bmi;
            $this->BodyMasses->save($entity);
        }
    }

    public function edit($id)
    {
        $page = $this->Page;

        $page->get('body_mass_index')->setControlType('hidden');

        parent::edit($id);

        if ($page->getData()->has('height') && $page->getData()->has('weight')) {
            $entity = $page->getData();
            $height = $entity->height;
            $weight = $entity->weight;

            $bmi = $this->getBodyMassIndex($height, $weight);

            $entity->body_mass_index = $bmi;
            $this->BodyMasses->save($entity);
        }
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

    private function getBodyMassIndex($height, $weight)
    {
        // from wikipedia bmi.
        $bmi = round(($weight / ($height * $height)), 2);

        return $bmi;
    }
}

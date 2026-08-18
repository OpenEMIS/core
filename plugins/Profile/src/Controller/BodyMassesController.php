<?php
namespace Profile\Controller;

use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use App\Controller\PageController;
use Page\Model\Entity\PageElement; //POCOR-6255

class BodyMassesController extends PageController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->AcademicPeriods = $this->fetchTable('AcademicPeriod.AcademicPeriods');
        $this->UserBodyMasses = $this->fetchTable('User.UserBodyMasses');
        $this->Page->loadElementsFromTable($this->UserBodyMasses);
        $this->Page->disable(['search']); // to disable the search function
        $this->Page->enable(['download']);

    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['comment', 'security_user_id', 'file_name', 'file_content']);//POCOR-6255

        $requestQuery = $this->request->getQuery();
        if (isset($requestQuery['sort'])) {
            $page->setQueryOption('sort', $requestQuery['sort']);
            $page->setQueryOption('direction', $requestQuery['direction']);
        }

        parent::index();
    }

    public function beforeFilter(EventInterface $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);
        //POCOR-6255 start
        if ($page->is(['view', 'add', 'edit'])) {
            $page->exclude(['file_name']);
            $page->move('file_content')->after('comment');

        }//POCOR-6255 end
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
        //POCOR-6255 start
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');//POCOR-6255 end
        parent::add();
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->get('body_mass_index')->setControlType('hidden');

        parent::edit($id);
    }
    //POCOR-6255 start
    public function view($id)
    {
        $page = $this->Page;
        $page->exclude(['file_name']);

        // set the file download for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');

        parent::view($id);

        $entity = $page->getData();
    }//POCOR-6255 end

    public function setBreadCrumb($options)
    {
        $page = $this->Page;
        $plugin = $this->getPlugin();

        $userId = isset($options['userId']) ? $options['userId'] : 0;
        $userName = isset($options['userName']) ? $options['userName'] : '';
        $encodedUserId = $this->paramsEncode(['id' => $userId]);

        // for Institution Staff and Institution Students
        if ($plugin == 'Institution') {
            $userRole = isset($options['userRole']) ? $options['userRole'] : '';
            $encodedInstitutionId = isset($options['institutionId']) ? $options['institutionId'] : 0;
            $institutionName = isset($options['institutionName']) ? $options['institutionName'] : '';
            $pluralUserRole = Inflector::pluralize($userRole);

            $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
            $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
            $page->addCrumb($pluralUserRole, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $pluralUserRole, 'institutionId' => $encodedInstitutionId]);
            $page->addCrumb($userName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $userRole.'User', 'view', $encodedUserId]);
            $page->addCrumb('Body Mass');
        } elseif ($plugin == 'Profile') {
            $page->addCrumb('Profile', ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Profiles', 'view', $encodedUserId]);
            $page->addCrumb($userName);
            $page->addCrumb('Body Mass');
        } elseif ($plugin == 'Directory') {
            $page->addCrumb('Directory', ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'index']);
            $page->addCrumb($userName, ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'view', $encodedUserId]);
            $page->addCrumb('Body Mass');
        }

        $page->move('academic_period_id')->first(); // move academic_period_id to be the first
    }

    // for Profiles & Directories
    public function setupTabElements($options)
    {
        $page = $this->Page;
        $plugin = $this->getPlugin();
        $userId = isset($options['userId']) ? $options['userId'] : 0;
        $userName = isset($options['userName']) ? $options['userName'] : '';

        $encodedUserId = $this->paramsEncode(['security_user_id' => $userId]);
        $pluralPlugin = Inflector::pluralize($plugin);

        $tabElements = [
            'Healths' => ['text' => __('Overview')],
            'HealthAllergies' => ['text' => __('Allergies')],
            'HealthConsultations' => ['text' => __('Consultations')],
            'HealthFamilies' => ['text' => __('Families')],
            'HealthHistories' => ['text' => __('Histories')],
            'HealthImmunizations' => ['text' => __('Immunizations')],
            'HealthMedications' => ['text' => __('Medications')],
            'HealthTests' => ['text' => __('Tests')],
            'BodyMasses' => ['text' => __('Body Mass')],
            'Insurances' => ['text' => __('Insurances')]
        ];

        //POCOR-9718: append $encodedUserId as pass[] so every Health tab carries context.
        foreach ($tabElements as $action => &$obj) {
            if ($action == 'Insurances' || $action == 'BodyMasses') {
                $obj['url'] = [
                    'plugin' => $plugin,
                    'controller' => $plugin.$action,
                    'action' => 'index',
                    $encodedUserId,
                ];
            } else {
                $obj['url'] = [
                    'plugin' => $plugin,
                    'controller' => $pluralPlugin,
                    'action' => $action,
                    'index',
                    $encodedUserId,
                ];
            }
        }

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        // set active tab
        $page->getTab('BodyMasses')->setActive('true');
    }

    // for Institution Staff and Institution Students
    public function setupInstitutionTabElements($options)
    {
        $page = $this->Page;
        $plugin = $this->getPlugin();
        $userId = isset($options['userId']) ? $options['userId'] : 0;
        $userName = isset($options['userName']) ? $options['userName'] : '';
        $userRole = isset($options['userRole']) ? $options['userRole'] : '';
        $encodedInstitutionId = isset($options['institutionId']) ? $options['institutionId'] : 0;

        $encodedUserId = $this->paramsEncode(['security_user_id' => $userId]);
        $pluralUserRole = Inflector::pluralize($userRole);
        $pluralPlugin = Inflector::pluralize($plugin);

        $tabElements = [
            'Healths' => ['text' => __('Overview')],
            'HealthAllergies' => ['text' => __('Allergies')],
            'HealthConsultations' => ['text' => __('Consultations')],
            'HealthFamilies' => ['text' => __('Families')],
            'HealthHistories' => ['text' => __('Histories')],
            'HealthImmunizations' => ['text' => __('Immunizations')],
            'HealthMedications' => ['text' => __('Medications')],
            'HealthTests' => ['text' => __('Tests')],
            'BodyMasses' => ['text' => __('Body Mass')],
            'Insurances' => ['text' => __('Insurances')]
        ];

        //POCOR-9718: append $encodedUserId as pass[] so every Health tab carries context.
        foreach ($tabElements as $action => &$obj) {
            if ($action == 'Insurances' || $action == 'BodyMasses') {
                $obj['url'] = [
                    'plugin' => 'Institution',
                    'institutionId' => $encodedInstitutionId,
                    'controller' => $userRole.$action,
                    'action' => 'index',
                    $encodedUserId,
                ];
            } else {
                $obj['url'] = [
                    'plugin' => $userRole,
                    'institutionId' => $encodedInstitutionId,
                    'controller' => $pluralUserRole,
                    'action' => $action,
                    'index',
                    $encodedUserId,
                ];
            }
        }

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        // set active tab
        $page->getTab('BodyMasses')->setActive('true');
    }

    public function setTooltip()
    {
        $page = $this->Page;
        $action = ['add', 'edit', 'view'];
        if (in_array($this->request->getParam('action'), $action)) {
            $page->get('height')->setLabel([
                'escape' => false,
                'class' => 'tooltip-desc',
                'text' => __('Height') . $this->tooltipMessage(__('Within 0 to 300 centimetres'))
            ]);

            $page->get('weight')->setLabel([
                'escape' => false,
                'class' => 'tooltip-desc',
                'text' => __('Weight') . $this->tooltipMessage(__('Within 0 to 500 kilograms'))
            ]);

            $page->get('body_mass_index')->setLabel([
                'escape' => false,
                'class' => 'tooltip-desc',
                'text' => __('Body Mass Index') . $this->tooltipMessage(__('Weight (kg) / Height<sup>2</sup> (m)'))
            ]);
        }
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

    public function beforeRender(EventInterface $event)
    {
        // if (!array_key_exists('_serialize', $this->viewVars) &&
        //     in_array($this->response->type(), ['application/json', 'application/xml'])
        // ) {
        //     $this->set('_serialize', true);
        // }
        $this->set('_serialize', true);
        $this->viewBuilder()->addHelper('Label');
        $this->viewBuilder()->addHelper('Text');
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
        $this->viewBuilder()->addHelper('ControllerAction.HtmlField');
        $this->viewBuilder()->addHelper('OpenEmis.Navigation');
        $this->viewBuilder()->addHelper('OpenEmis.Resource');
        $this->viewBuilder()->addHelpers(['Html', 'Form', 'Paginator', 'Label', 'Url']);

    }
}

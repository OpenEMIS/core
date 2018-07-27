<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Cake\Utility\Inflector;
use App\Controller\PageController;

class InsurancesController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Health.InsuranceProviders');
        $this->loadModel('Health.InsuranceTypes');
        $this->loadModel('User.UserInsurances');
        $this->Page->loadElementsFromTable($this->UserInsurances);
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

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);

        $page->get('insurance_provider_id')
            ->setLabel('Provider');
        $page->get('insurance_type_id')
            ->setLabel('Type');
    }

    public function add()
    {
        parent::add();
        $this->addEditInsurances();
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEditInsurances();
    }

    private function addEditInsurances()
    {
        $page = $this->Page;
        // Insurance Providers Field
        $page->get('insurance_provider_id')
            ->setLabel('Provider')
            ->setControlType('select');
        // end Insurance Providers Field

        // Insurance Types Field    
        $page->get('insurance_type_id')
            ->setLabel('Type')
            ->setControlType('select');
        // end Insurance Types Field
    }

    public function delete($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        parent::delete($id);
    }

    public function setBreadCrumb($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;

        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';
        $encodedUserId = $this->paramsEncode(['id' => $userId]);

        // for Institution Staff and Institution Students
        if ($plugin == 'Institution') {
            $userRole = array_key_exists('userRole', $options) ? $options['userRole'] : '';
            $institutionId = array_key_exists('institutionId', $options) ? $options['institutionId'] : 0;
            $institutionName = array_key_exists('institutionName', $options) ? $options['institutionName'] : '';

            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
            $pluralUserRole = Inflector::pluralize($userRole);

            $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
            $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
            $page->addCrumb($pluralUserRole, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $pluralUserRole, 'institutionId' => $encodedInstitutionId]);
            $page->addCrumb($userName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $userRole.'User', 'view', $encodedUserId]);
            $page->addCrumb('Insurances');
        } elseif ($plugin == 'Directory') {
            $page->addCrumb('Directory', ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'index']);
            $page->addCrumb($userName, ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'view', $encodedUserId]);
            $page->addCrumb('Insurances');
        }elseif ($plugin == 'Profile') {
            $page->addCrumb('Profile', ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Profiles', 'view', $encodedUserId]);
            $page->addCrumb($userName);
            $page->addCrumb('Insurances');
        }
    }

    // for Profiles & Directories
    public function setupTabElements($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;
        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';

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

        foreach ($tabElements as $action => &$obj) {
            if ($action == 'Insurances' || $action == 'BodyMasses') {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $plugin.$action,
                    'action' => 'index'
                ];
                $obj['url'] = $url;
            } else {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $pluralPlugin,
                    'action' => $action, 'index'
                ];
                $obj['url'] = $url;
            }
        }
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }
        // set active tab
        $page->getTab('Insurances')->setActive('true');
    }

    // for Institution Staff and Institution Students
    public function setupInstitutionTabElements($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;
        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';
        $userRole = array_key_exists('userRole', $options) ? $options['userRole'] : '';
        $institutionId = array_key_exists('institutionId', $options) ? $options['institutionId'] : 0;

        $encodedUserId = $this->paramsEncode(['security_user_id' => $userId]);
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
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

        foreach ($tabElements as $action => &$obj) {
            if ($action == 'Insurances' || $action == 'BodyMasses') {
                $url = [
                    'plugin' => 'Institution',
                    'institutionId' => $encodedInstitutionId,
                    'controller' => $userRole.$action,
                    'action' => 'index'
                ];
                $obj['url'] = $url;
            } else {
                $url = [
                    'plugin' => $userRole,
                    'controller' => $pluralUserRole,
                    'action' => $action, 'index'
                ];
                $obj['url'] = $url;
            }
        }

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }
        // set active tab
        $page->getTab('Insurances')->setActive('true');
    }
}

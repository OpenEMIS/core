<?php

declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use ControllerAction\Model\Traits\ControllerActionTrait;
use ControllerAction\Model\Traits\SecurityTrait;
use Cake\Utility\Inflector;
use Cake\Cache\Cache;
use Cake\ORM\Table;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use App\Utility\ApplicationTimezone;


/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    use ControllerActionTrait;
    use SecurityTrait;

    private $productName = 'OpenEMIS Core';
    /*public $helpers = [
        'Text',

        // Custom Helper
        'ControllerAction.ControllerAction',
        'OpenEmis.Navigation',
        'OpenEmis.Resource'
    ];*/

    private $webhookListUrl = [
        'plugin' => 'Webhook',
        'controller' => 'Webhooks',
        'action' => 'listWebhooks'
    ];
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        if (!file_exists(CONFIG . 'app_local.php')) {
            $url = Router::url(['plugin' => 'Installer', 'controller' => 'Installer', 'action' => 'index'], true);
            header('Location: ' . $url);
            die;
        }

        if (Configure::read('schoolMode')) {
            $this->productName = 'OpenEMIS School';
        }

        parent::initialize();
        $theme = 'core';
        if (Configure::read('schoolMode')) {
            $theme = 'school';
            $this->productName = 'OpenEMIS School';
        }

        // don't load ControllerAction component if it is not a PageController
        if ($this instanceof \Page\Controller\PageController == false) {
            // ControllerActionComponent must be loaded before AuthComponent for it to work
            $this->loadComponent('ControllerAction.ControllerAction', [
                'ignoreFields' => ['modified_user_id', 'created_user_id', 'order']
            ]);
        }

        $this->loadComponent('Auth', [
            'authenticate' => [
                'Form' => [
                    'userModel' => 'User.Users',
                    'finder' => 'auth',
                    'passwordHasher' => [
                        'className' => 'Fallback',
                        //'hashers' => ['Default', 'Legacy']
                        'hashers' => ['Default']
                    ]
                ],
            ],
            'loginAction' => [
                'plugin' => 'User',
                'controller' => 'Users',
                'action' => 'login'
            ],
            'loginRedirect' => [
                'plugin' => false,
                'controller' => 'Dashboard',
                'action' => 'index'
            ],
            'logoutRedirect' => [
                'plugin' => 'User',
                'controller' => 'Users',
                'action' => 'login'
            ]
        ]);

        $this->loadComponent('Paginator');

        $this->Auth->SetConfig('authorize', ['Security']);

        // Custom Components
        $this->loadComponent('Navigation');
        $this->productName = 'OpenEMIS Core';
        $this->loadComponent('Localization.Localization', [
            'productName' => $this->productName
        ]);
        $themeData = $this->getTheme(); // POCOR-8951
        $this->loadComponent('OpenEmis.OpenEmis', [
            'homeUrl' => ['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index'],
            'headerMenu' => [
                'Preferences' => [
                    'url' => ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']
                ],
                'Logout' => [
                    'url' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'logout']
                ]
            ],
            // POCOR-8951 start
            'productName' => $themeData['application_name'] ?? 'OpenEMIS',
            'productLogo' => $themeData['logo'] ?? null,
            'footerText' => $themeData['copyright_notice_in_footer'] ?? '',
            'theme' => $theme ?? null,
            'lastModified' => $themeData['timestamp'] ?? time(),
            // POCOR-8951 end
            ]);

        $this->loadComponent('OpenEmis.ApplicationSwitcher', [
            'productName' => $this->productName
        ]);
        //


        // Angular initialization
        $this->loadComponent('Angular.Angular', [
            'app' => 'OE_Core',
            'modules' => [
                'bgDirectives',
                'ui.bootstrap',
                'ui.bootstrap-slider',
                'ui.tab.scroll',
                'agGrid',
                'app.ctrl',
                'advanced.search.ctrl',
                'kd-elem-sizes',
                'kd-angular-checkbox-radio',
                'multi-select-tree',
                'kd-angular-tree-dropdown',
                'kd-angular-ag-grid',
                'sg.tree.ctrl',
                'sg.tree.svc'
            ]
        ]);

        $this->loadComponent('ControllerAction.Alert');
        $this->loadComponent('AccessControl');
        //POCOR-7810

        $this->loadComponent('Workflow.Workflow');
        $this->loadComponent('SSO.SSO', [
            'homePageURL' => ['plugin' => null, 'controller' => 'Dashboard', 'action' => 'index'],
            'loginPageURL' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'login'],
            'userModel' => 'User.Users',
            'cookieAuth' => [
                'username' => 'openemis_no'
            ],
            'cookie' => [
                'domain' => Configure::read('domain')
            ]
        ]); // for single sign on authentication
        $this->loadComponent('Security.SelectOptionsTampering');
        $this->loadComponent('Security', [
            'unlockedActions' => [
                'postLogin'
            ]
        ]);

        $this->loadComponent('Csrf');
        if ($this->getRequest()->getParam('action') == 'postLogin') {
            $this->getEventManager()->off($this->Csrf);
        }
        $this->loadComponent('TabPermission');
        // START: POCOR-6538 — display timezone from config_items (cached; PHP default remains UTC in bootstrap).
        ApplicationTimezone::registerDisplayTimezone(); //POCOR-9565
        $this->checkAccessControl();
        // END: POCOR-6538
    }

    private function darkenColour($rgb, $darker = 2)
    {
        // Ensure $rgb is a string and not null
        if (!is_string($rgb) || empty($rgb)) {
            return '#000000'; // Return a default color if $rgb is invalid
        }

        $hash = (strpos($rgb, '#') !== false) ? '#' : '';
        $rgb = (strlen($rgb) == 7) ? str_replace('#', '', $rgb) : ((strlen($rgb) == 6) ? $rgb : false);

        if ($rgb === false || strlen($rgb) != 6) {
            return $hash . '000000'; // Return black if the format is invalid
        }

        $darker = ($darker > 1) ? $darker : 1;

        list($R16, $G16, $B16) = str_split($rgb, 2);

        $R = sprintf("%02X", floor(hexdec($R16) / $darker));
        $G = sprintf("%02X", floor(hexdec($G16) / $darker));
        $B = sprintf("%02X", floor(hexdec($B16) / $darker));

        return $hash . $R . $G . $B;
    }


    public function getTheme()
    {
        // POCOR-8951 start

        $themes = Cache::read('themes');
        if($themes){
            return $themes;
        }
        $configItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $coreConfig = $configItems->find()
            ->select(['id'])
            ->where(['code' => 'openemis_core', 'type' => 'Online Services'])
            ->first();

        if (!$coreConfig) {
            return []; // Return empty if no core config found
        }

        // Delete old theme images
        $themesPath = WWW_ROOT . 'img' . DS . 'themes';
        if (is_dir($themesPath)) {
            $this->deleteDirectory($themesPath);
        }

        // Get only themes with the correct config_item_id
        $themeTable = TableRegistry::getTableLocator()->get('Theme.Themes');
        $themeQuery = $themeTable->find()
            ->where(['config_item_id' => $coreConfig->id]);

        $themes = [];

        foreach ($themeQuery as $r) {
            // Handle file content writing
            if ($r->content) {
                $filePath = WWW_ROOT . 'img' . DS . 'themes' . DS . $r->value;
                $dir = dirname($filePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($filePath, stream_get_contents($r->content));
            }

            // Create code-friendly key
            $code = Inflector::underscore(str_replace(' ', '', $r->name));

            // Apply specific rules per theme item type
            if (in_array($code, ['login_page_image', 'favicon'])) {
                $themes[$code] = !empty($r->value) ? 'themes/' . $r->value : 'default_images/' . $r->default_value;
            } elseif (in_array($code, ['copyright_notice_in_footer', 'logo'])) {
                $themes[$code] = !empty($r->value) ? 'themes/' . $r->value : null;
            } else {
                $themes[$code] = !empty($r->value) ? $r->value : $r->default_value;
            }
        }

// Return an empty array if no results found
        if (empty($themes)) {
            return [];
        }
        Log::write('debug', 'Theme data: ' . print_r($themes, true));
        // Modify CSS template
        $colour = $themes['colour'] ?? '000000';
        $secondaryColour = $this->darkenColour($colour);

        $customPath = ROOT . DS . 'plugins' . DS . 'OpenEmis' . DS . 'webroot' . DS . 'css' . DS . 'themes' . DS . 'custom' . DS;
        $basePath = Router::url(['controller' => '', 'action' => 'index', 'plugin' => false]) . '/';

        $loginBackground = $basePath . Configure::read('App.imageBaseUrl') . $themes['login_page_image'];

        $templatePath = $customPath . 'layout.core.template.css';
        $template = file_exists($templatePath) ? file_get_contents($templatePath) : '';

        $template = str_replace('${bgImg}', "'$loginBackground'", $template);
        $template = str_replace('${secondColor}', $secondaryColour, $template);
        $template = str_replace('${prodColor}', "#$colour", $template);

        // Write final CSS
        $finalCssPath = WWW_ROOT . 'css' . DS . 'themes' . DS . 'layout.min.css';
        $finalCssDir = dirname($finalCssPath);
        if (!is_dir($finalCssDir)) {
            mkdir($finalCssDir, 0755, true);
        }
        file_put_contents($finalCssPath, $template);

        // Add timestamp and cache
        $themes['timestamp'] = $configItems->value('themes');
        Cache::write('themes', $themes);
        Cache::write('cake_themes', $themes);
        return $themes;
    // POCOR-8951 end
    }


    /**
     * Before render callback.
     *
     * @param \Cake\Event\EventInterface $event The beforeRender event.
     * @return void
     */
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

    // Triggered from LocalizationComponent
    // Controller.Localization.getLanguageOptions
    public function getLanguageOptions(EventInterface $event)
    {
        $ConfigItemsTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $languageArr = $ConfigItemsTable->getSystemLanguageOptions();
        $systemLanguage = $languageArr['language'];
        $showLanguage = $languageArr['language_menu'];
        $session = $this->request->getSession();
        if (!$session->check('System.language_menu')) {
            $session->write('System.language', $systemLanguage);
            $session->write('System.language_menu', $showLanguage);
        }
        return [$showLanguage, $systemLanguage];
    }

    // Triggered from Localization component
    // Controller.Localization.updateLoginLanguage
    public function updateLoginLanguage(EventInterface $event, $user, $lang)
    {
        $UsersTable = TableRegistry::getTableLocator()->get('User.Users');
        $UsersTable->dispatchEvent('Model.Users.updateLoginLanguage', [$user, $lang], $this);
    }

    //POCOR-7534 Starts
    public function beforeFilter(EventInterface $event)
    {
        try {
            if ($this->getPlugin() == $this->getPlugin()) { // POCOR-8080-1
                $this->Security->setConfig('validatePost', false);
            }
        } catch (\Exception $exception) {
            // if no plugin, skip it
        }
        parent::beforeFilter($event);
        $session = $this->request->getSession();
        $superAdmin = $session->read('Auth.User.super_admin');
        //POCOR-8595 starts
        if (!is_null($_COOKIE['Restful'])) {
            return true;
        } //POCOR-8595 ends
        if ($superAdmin == 0) {
            $UserData = $session->read('Auth.User')['id'];
            // POCOR-8534 start
            if (!$UserData) {
                return;
            }
            $hasPermission = $this->oldSecurityCheck($session, $event);
            if ($hasPermission == 0) {
                $event->stopPropagation();
                $this->Alert->warning('general.notAccess');
                $this->redirect($this->referer());
            }
            $hasPermission = $this->newSecurityCheck($event);
            if ($hasPermission == 0) {
                $event->stopPropagation();
                $this->Alert->warning('general.notAccess');
                $this->redirect($this->referer());
            }
        }
        // POCOR-8534 End

    }

    public function getIdBySecurityFunctionName($actionParam, $controllerParam)
    {
        //POCOR-7562 start
        $session = $this->request->getSession();
        $superAdmin = $session->read('Auth.User.super_admin');
        //POCOR-7562 end
        $name = '';
        if ($controllerParam == 'Securities') {
            if ($actionParam == 'Users') {
                $name = 'Users';
            } else if (($actionParam == 'UserGroups' || $actionParam == 'SystemGroups')) {
                $name = 'Groups';
            } else if ($actionParam == 'Roles') {
                $name = ($this->request->getQuery['type'] == 'system') ? 'System Roles' : 'User Roles';
            } else if ($actionParam == 'Accounts') {
                $name = 'Accounts';
            } else if ($actionParam == 'UserGroupsList') {
                $name = 'User Group List';
            }
        } else if ($controllerParam == 'Credentials') {
            if ($actionParam == 'add' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'Credentials';
            }
        } else if ($controllerParam == 'Areas') {
            if ($actionParam == 'Levels' || $actionParam == 'AdministrativeLevels') {
                $name = 'Area Levels';
            } else if ($actionParam == 'Areas' || $actionParam == 'Administratives') {
                $name = 'Areas';
            }
        } else if ($controllerParam == 'AcademicPeriods') {
            if ($actionParam == 'Levels') {
                $name = 'Academic Period Levels';
            } else if ($actionParam == 'Periods') {
                $name = 'Academic Periods';
            }
        } else if ($controllerParam == 'Educations') {
            if ($actionParam == 'Systems') {
                $name = 'Education Systems';
            } else if ($actionParam == 'Levels') {
                $name = 'Education Levels';
            } else if ($actionParam == 'Cycles') {
                $name = 'Education Cycles';
            } else if ($actionParam == 'Programmes') {
                $name = 'Education Programmes';
            } else if ($actionParam == 'Grades') {
                $name = 'Education Grades';
            } else if ($actionParam == 'Stages' || $actionParam == 'GradeSubjects') {
                $name = 'Setup';
            }
        } else if ($controllerParam == 'Attendances') {
            if ($actionParam == 'StudentMarkTypes' || $actionParam == 'StudentMarkTypeStatuses') {
                $name = 'Attendances';
            }
        } else if ($controllerParam == 'FieldOptions') {
            $actionParam = $this->request->getParam('pass')[0];
            if (($actionParam == '' || $actionParam == 'index') || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'add' || $actionParam == 'remove' || $actionParam == 'transfer') {
                $name = 'Setup';
            }
        } else if ($controllerParam == 'Labels') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit') {
                $name = 'Labels';
            }
        } else if ($controllerParam == 'Configurations') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit') {
                $name = 'Configurations';
            } else if ($actionParam == 'AuthSystemAuthentications') {
                $name = 'Authentication';
            } else if ($actionParam == 'ExternalDataSource') {
                $name = 'External Data Source';
            } else if ($actionParam == 'ProductLists') {
                $name = 'Product Lists';
            } else if ($actionParam == 'Webhooks') {
                $name = 'Webhooks';
            }
        } else if ($controllerParam == 'Themes') {
            $controllerParam = 'Configurations';
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit') {
                $name = 'Configurations';
            }
        } else if ($controllerParam == 'Notices') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'Notices';
            }
        } else if ($controllerParam == 'Risks') {
            if ($actionParam == 'Risks') {
                $name = 'Risks';
            }
        } else if ($controllerParam == 'InstitutionCustomFields') {
            if ($actionParam == 'Fields' || $actionParam == 'Pages') {
                $name = 'Institution';
            }
        } else if ($controllerParam == 'StudentCustomFields') {
            if ($actionParam == 'Fields' || $actionParam == 'Pages') {
                $name = 'Student';
            }
        } else if ($controllerParam == 'StaffCustomFields') {
            if ($actionParam == 'Fields' || $actionParam == 'Pages') {
                $name = 'Staff';
            }
        } else if ($controllerParam == 'Infrastructures') {
            if ($actionParam == 'Fields' || $actionParam == 'Pages' || $actionParam == 'LandPages' || $actionParam == 'LandTypes' || $actionParam == 'BuildingPages' || $actionParam == 'BuildingTypes' || $actionParam == 'FloorPages' || $actionParam == 'FloorTypes' || $actionParam == 'RoomPages' || $actionParam == 'RoomTypes') {
                $name = 'Infrastructure';
            }
        } else if ($controllerParam == 'Locales') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'Languages';
            }
        } else if ($controllerParam == 'LocaleContents') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'Translations';
            }
        } else if ($controllerParam == 'ProfileTemplates') {
            if ($actionParam == 'Institutions' || $actionParam == 'InstitutionProfiles') {
                $name = 'Institutions';
            } else if ($actionParam == 'Staff' || $actionParam == 'StaffProfiles') {
                $name = 'Staff';
            } else if ($actionParam == 'Students' || $actionParam == 'StudentProfiles') {
                $name = 'Students';
            } else if ($actionParam == 'Classes' || $actionParam == 'ClassesProfiles') {
                $name = 'Classes';
            }
        } else if ($controllerParam == 'Surveys') {
            if ($actionParam == 'Questions') {
                $name = 'Questions';
            } else if ($actionParam == 'Forms') {
                $name = 'Forms';
            } else if ($actionParam == 'Status') {
                $name = 'Status';
            } else if ($actionParam == 'Rules') {
                $name = 'Rules';
            } else if ($actionParam == 'Filters') { //POCOR-7562
                $name = 'Rules';
            } else if ($actionParam == 'Recipients') {
                $name = 'Rules';
            }
        } else if ($controllerParam == 'Rubrics') {
            if ($actionParam == 'Templates' || $actionParam == 'Sections' || $actionParam == 'Criterias' || $actionParam == 'Options') {
                $name = 'Setup';
            } else if ($actionParam == 'Status') {
                $name = 'Status';
            }
        } else if ($controllerParam == 'Alerts') {
            if ($actionParam == 'Alerts') {
                $name = 'Alerts';
            } else if ($actionParam == 'Logs') {
                $name = 'Logs';
            } else if ($actionParam == 'AlertRules') {
                $name = 'AlertRules';
            }
        } else if ($controllerParam == 'Trainings') {
            if ($actionParam == 'Courses') {
                $name = 'Courses';
            } else if ($actionParam == 'Sessions' || $actionParam == 'ImportTrainees') {
                $name = 'Sessions';
            } else if ($actionParam == 'Results' || $actionParam == 'ImportTrainingSessionTraineeResults') {
                $name = 'Results';
            } else if ($actionParam == 'Applications') {
                $name = 'Applications';
            }
        } else if ($controllerParam == 'Competencies') {
            if ($actionParam == 'Templates' || $actionParam == 'Items' || $actionParam == 'Criterias') {
                $name = 'Competency Setup';
            } else if ($actionParam == 'Periods') {
                $name = 'Periods';
            } else if ($actionParam == 'GradingTypes') {
                $name = 'GradingTypes';
            } else if ($actionParam == 'ImportCompetencyTemplates') {
                $name = 'Import Competency Templates';
            }
        } else if ($controllerParam == 'Outcomes') {
            if ($actionParam == 'Templates' || $actionParam == 'ImportOutcomeTemplates' || $actionParam == 'Criterias') {
                $name = 'Outcome Setup';
            } else if ($actionParam == 'Periods') {
                $name = 'Periods';
            } else if ($actionParam == 'GradingTypes') {
                $name = 'Grading Types';
            }
        } else if ($controllerParam == 'Assessments') {
            if ($actionParam == 'Assessments') {
                $name = 'Assessments';
            } else if ($actionParam == 'GradingTypes' || $actionParam == 'GradingOptions') {
                $name = 'Grading Types';
            } else if ($actionParam == 'Status') {
                $name = 'Status';
            } else if ($actionParam == 'AssessmentPeriods') {
                $name = 'Assessment Periods';
            }
        } else if ($controllerParam == 'ReportCards') {
            if ($actionParam == 'Templates') {
                $name = 'Templates';
            } else if ($actionParam == 'ReportCardEmail') {
                $name = 'Email Templates';
            } else if ($actionParam == 'Processes') {
                $name = 'Processes';
            }
        } else if ($controllerParam == 'Examinations') {
            if ($actionParam == 'Exams') {
                $name = 'Exams';
            } else if ($actionParam == 'GradingTypes') {
                $name = 'Grading Types';
            } else if ($actionParam == 'ExamCentres' || $actionParam == 'ExamCentreExams') {
                $name = 'Exam Centres';
            } else if ($actionParam == 'ImportExaminationCentreRooms') {
                $name = 'Import Examination Rooms';
            } else if ($actionParam == 'RegisteredStudents' || $actionParam == 'RegistrationDirectory' || $actionParam == 'BulkStudentRegistration') {
                $name = 'Registered Students';
            } else if ($actionParam == 'NotRegisteredStudents' || $actionParam == 'RegistrationDirectory' || $actionParam == 'BulkStudentRegistration') {
                $name = 'Not Registered Students';
            } else if ($actionParam == 'ExamResults' || $actionParam == 'Results') {
                $name = 'Results';
            } else if ($actionParam == 'ImportResults') {
                $name = 'Import Results';
            } else if ($actionParam == 'ExamCentreLinkedInstitutions') {
                $name = 'Exam Centre Invigilators';
            } else if ($actionParam == 'ExamCentreInvigilators') {
                $name = 'Exam Centre Linked Institutions';
            } else if ($actionParam == 'ExamCentreSubjects') {
                $name = 'Exam Centre Subjects';
            } else if ($actionParam == 'ExamCentreRooms') {
                $name = 'Exam Centre Rooms';
            } else if ($actionParam == 'ExamCentreStudents') {
                $name = 'Exam Centre Students';
            }
        } else if ($controllerParam == 'Scholarships') {
            if ($actionParam == 'Scholarships') {
                $name = 'Scholarships';
            } else if ($actionParam == 'Applications') {
                $name = 'Applications';
            } else if ($actionParam == 'Identities') {
                $name = 'Identities';
            } else if ($actionParam == 'Nationalities') {
                $name = 'Nationalities';
            } else if ($actionParam == 'Contacts') {
                $name = 'Contacts';
            } else if ($actionParam == 'Guardians') {
                $name = 'Guardians';
            } else if ($actionParam == 'Histories') {
                $name = 'Histories';
            } else if ($actionParam == 'RecipientPaymentStructures') {
                $name = 'Payment Structures';
            } else if ($actionParam == 'RecipientPayments') {
                $name = 'Disbursements';
            }
        } else if ($controllerParam == 'UsersDirectory') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit') {
                $name = 'Users Directory';
            }
        } else if ($controllerParam == 'ScholarshipRecipients') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit') {
                $name = 'Recipients';
            }
        } else if ($controllerParam == 'ScholarshipRecipientInstitutionChoices') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit') {
                $name = 'Institution Choices';
            }
        } else if ($controllerParam == 'ScholarshipRecipientCollections') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'Collections';
            }
        } else if ($controllerParam == 'ScholarshipRecipientAcademicStandings') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'Academic Standings';
            }
        } else if ($controllerParam == 'ScholarshipApplicationInstitutionChoices') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'Institution Choices';
            }
        } else if ($controllerParam == 'ScholarshipApplicationAttachments') {
            if ($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'Application Attachments';
            }
        } else if ($controllerParam == 'StaffAppraisals') {
            if ($actionParam == 'Criterias') {
                $name = 'Criterias';
            } else if ($actionParam == 'Forms' || $actionParam == 'Scores') {
                $name = 'Forms';
            } else if ($actionParam == 'Types') {
                $name = 'Types';
            } else if ($actionParam == 'Periods') {
                $name = 'Periods';
            }
        } else if ($controllerParam == 'Textbooks') {
            if ($actionParam == 'Textbooks') {
                $name = 'Textbooks';
            } else if ($actionParam == 'ImportTextbooks') {
                $name = 'Import Textbooks';
            }
        } else if ($controllerParam == 'Meals') {
            if ($actionParam == 'programme') {
                $name = 'Meals Programme';
            }
        } else if ($controllerParam == 'Workflows') {
            if ($actionParam == 'Workflows') {
                $name = 'Workflows';
            } else if ($actionParam == 'Steps') {
                $name = 'Steps';
            } else if ($actionParam == 'Statuses') {
                $name = 'Statuses';
            } else if ($actionParam == 'Actions') {
                $name = 'Actions';
            } else if ($actionParam == 'Rules') {
                $name = 'Rules';
            }
        } else if ($controllerParam == 'Systems') {
            if ($actionParam == 'Updates') {
                $name = 'Updates';
            }
        } else if ($controllerParam == 'Calendars') {
            if ($actionParam == 'add' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'Calendars';
            }
        } else if ($controllerParam == 'MoodleApiLog') {
            if ($actionParam == 'add' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                $name = 'MoodleApi Log';
            }
        } else if ($controllerParam == 'Archives') {
            if ($superAdmin == 0) {
                if ($actionParam == 'add' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete') {
                    $name = 'Archive';
                }
                if ($actionParam == 'CopyData') { //POCOR-7562
                    $name = 'Copy';
                } else if ($actionParam == 'BackupLog') {
                    $name = 'Backup';
                } else if ($actionParam == 'Transfer') {
                    $name = 'Archive';
                }
            }
        }
        $module = 'Administration';
        $SecurityFunctionsTbl = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $SecurityFunctionsData = $SecurityFunctionsTbl->find()->where([
            $SecurityFunctionsTbl->aliasField('name') => $name,
            $SecurityFunctionsTbl->aliasField('controller') => $controllerParam,
            $SecurityFunctionsTbl->aliasField('module') => $module
        ])->toArray();
        $SecurityFunctionIds = [];
        if (!empty($SecurityFunctionsData)) {
            foreach ($SecurityFunctionsData as $Function_key => $Function_val) {
                $SecurityFunctionIds[] = $Function_val->id;
            }
        }
        return $SecurityFunctionIds;
    }

    public function checkAuthorizationForRoles($securityFunctionsId, $roleId) // POCOR-8534
    {
        $SecurityRoleFunctionsTbl = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $SecurityRoleFunctionsTblData = $SecurityRoleFunctionsTbl->find()->where([
            $SecurityRoleFunctionsTbl->aliasField('security_role_id IN') => $roleId,
            $SecurityRoleFunctionsTbl->aliasField('security_function_id IN') => $securityFunctionsId,
            $SecurityRoleFunctionsTbl->aliasField('_view') => 1
        ])->toArray();
        $dataArray = [];
        if (!empty($SecurityRoleFunctionsTblData)) {
            foreach ($SecurityRoleFunctionsTblData as $key => $value) {
                if ($value->_view == 1) {
                    $dataArray[] = $value->_view;
                }
            }
        }
        return count($dataArray);
    } //POCOR-7534 ends


    private function skipCheckAccessControl($params)
    {


        $skip = true;

        if ($params['controller'] == 'Errors') {
            return $skip;
        }
        //POCOR-7910 start(To not check  permission for sending alert message)
        if (
            $params['controller'] == 'Configurations' &&
            in_array($params['action'], ['setAlert'])
        ) {
            return $skip;
        }
        //POCOR-7910 end

        // POCOR-7841 BIT CLEANER CODE
        if (
            $params['controller'] == 'Users' &&
            in_array($params['action'], [
                'login',
                'logout',
                'postLogin',
                'login_remote',
                'forgotUsername',
                'postForgotUsername',
                'forgotPassword',
                'postForgotPassword',
                'resetPassword', //POCOR-8806
                'postResetPassword',
                'verifyOtp'
            ])
        ) {
            return $skip;
        }
        //        if ($params['controller'] == 'Users' &&
        //            $params['action'] == 'logout') {
        //            return $skip;
        //        }
        //        if ($params['controller'] == 'Users' &&
        //            $params['action'] == 'forgotUsername') {
        //            return $skip;
        //        }
        //        if ($params['controller'] == 'Users' &&
        //            $params['action'] == 'postForgotUsername') {
        //            return $skip;
        //        }
        //        if ($params['controller'] == 'Users' &&
        //            $params['action'] == 'forgotPassword') {
        //            return $skip;
        //        }
        //        if ($params['controller'] == 'Users' &&
        //            $params['action'] == 'postForgotPassword') {
        //            return $skip;
        //        }
        //        if ($params['controller'] == 'Users' &&
        //            $params['action'] == 'login') {
        //            return $skip;
        //        }
        //        if ($params['controller'] == 'Users' &&
        //            $params['action'] == 'postLogin') {
        //            return $skip;
        //        }
        // END POCOR-7841

        if (
            $params['controller'] == 'Dashboard' &&
            $params['action'] == 'index'
        ) {
            return $skip;
        }
        if (
            $params['controller'] == 'Translations' &&
            $params['action'] == 'translate'
        ) {
            return $skip;
        }

        // POCOR-7841 IF NO USER, EXIT
        $session = $this->request->getSession();
        $user_id = $session->read('Auth.User')['id'];
        if (empty($user_id)) {
            $skip = false;
            return $skip;
        }
        // POCOR-7841


        // POCOR-7833 SKIP WORKFLOW AJAX REQUESTS
        // POCOR-7841 BIT CLEANER CODE
        if (
            $params['controller'] == 'Workflows' &&
            in_array($params['action'], [
                'ajaxGetCases',
                'ajaxGetAssignees'
            ])
        ) {
            return $skip;
        }
        //        if ($params['controller'] == 'Workflows' &&
        //            $params['action'] == 'ajaxGetCases') {
        //            return $skip;
        //        }
        //
        //        if ($params['controller'] == 'Workflows' &&
        //            $params['action'] == 'ajaxGetAssignees') {
        //            return $skip;
        //        }
        // END POCOR-7841
        // POCOR-7833

        // POCOR-7841 SKIP INSTITUTION AND DIRECTORY REQUESTS
        if (
            $params['controller'] == 'Institutions' &&
            in_array($params['action'], [
                'Addguardian',
                'checkConfigurationForExternalSearch',
                'checkUserAlreadyExistByIdentity',
                'saveGuardianData',
                'getEducationGrade',
                'getClassOptions',
                'getPositionType',
                'getFTE',
                'getShifts',
                'getPositions',
                'getStaffType',
                'studentCustomFields',
                'staffCustomFields',
                'saveStudentData',
                'saveAssessmentItemExemptions', // POCOR-8224
                'saveStaffData',
                'saveGuardianData',
                'saveDirectoryData',
                'getStudentTransferReason',
                'checkStudentAdmissionAgeValidation',
                'getStartDateFromAcademicPeriod',
                'checkUserAlreadyExistByIdentity',
                'checkConfigurationForExternalSearch',
                'getStaffPosititonGrades',
                'getCspdData',
                'getConfigurationForExternalSourceData', //POCOR-7716
                'getStudentAdmissionStatus' //POCOR-7716
            ])
        ) {
            return $skip;
        }


        if (
            $params['controller'] == 'Directories' &&
            in_array($params['action'], [
                'Addguardian',
                'getContactType',
                'getIdentityTypes',
                'getNationalities',
                'getGenders',
                'getRelationshipType',
                'directoryInternalSearch',
                'directoryExternalSearch',
                'getContactType',
                'getAutoGeneratedPassword',
                'getUniqueOpenemisId',
                'getRedirectToGuardian',
                'getUserType' // POCOR-8998
            ])
        ) {
            return $skip;
        }
        // POCOR-7841

        $skip = false;
        return $skip;
    }

    private function checkAccessControl()
    {

        $params = $this->request->getAttribute('params');
        // POCOR-7833 MOVE ALL SKIP ACCESS TO ONE FUNCTION
        if ($this->skipCheckAccessControl($params)) {
            return;
        }
        // END
        // POCOR-7895 ARCHIVE RIGHTS CHANGE
        if (
            $params['controller'] == 'Institutions' &&
            $params['action'] == 'InstitutionStudentAbsencesArchived'
        ) {
            $params['action'] = 'StudentAttendances';
        }

        if (
            $params['controller'] == 'Institutions' &&
            $params['action'] == 'StaffAttendancesArchived'
        ) {
            $params['action'] = 'InstitutionStaffAttendances';
        }
        // POCOR-8985 start
        if (
            $params['controller'] == 'Institutions' &&
            $params['action'] == 'ScheduleTimetable'
        ) {
            $params['action'] = 'ScheduleTimetableOverview';
        }
        // POCOR-8985 end


        // POCOR-7895 END

        //POCOR-7731 start
        if (
            $params['controller'] == 'ApiSecurities' &&
            $params['action'] == 'index'
        ) {
            return $this->redirect(['controller' => 'Errors', 'action' => 'error404']);
        }
        //POCOR-7731 end

        $check = $this->AccessControl->check($params);
        if (!$check && $params['plugin'] != 'GuardianNav') { //POCOR-8596
// POCOR-8286; POCOR-9100 removed unnecessary logging, may cause merge conflict
            //            $this->log(__FUNCTION__, 'debug');
//            if ($params !== null) {
//                $this->log(print_r($params,true), 'debug');
//            }
            //$this->Alert->warning('general.notAccess'); //tmp solution
            return $this->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
        }
    }

    /**
     * // POCOR-8534
     * @param \Cake\Http\Session $session
     * @param EventInterface $event
     */
    private function oldSecurityCheck(\Cake\Http\Session $session, EventInterface $event)
    {
        $UserData = $session->read('Auth.User')['id'];
        if (!$UserData) {
            return 0;
        }
        $GroupRoles = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $userRole = $GroupRoles->find()
            ->contain('SecurityRoles')
            ->order(['SecurityRoles.order'])
            ->where([
                $GroupRoles->aliasField('security_user_id') => $UserData
            ])
            ->group([$GroupRoles->aliasField('security_role_id')])
            ->toArray();

        if (!empty($this->request->getParam('controller')) && !empty($userRole)) {
            $RoleIds = [];
            foreach ($userRole as $Role_key => $Role_val) {
                $RoleIds[] = $Role_val->security_role_id;
            }
            $SecurityFunctionIds = $this->getIdBySecurityFunctionName(
                $this->request->getParam('action'),
                $this->request->getParam('controller')
            );
            if (!empty($SecurityFunctionIds)) {
                $request = $this->request;
                $controllerName = $request->getParam('controller');
                $plugin = $request->getParam('plugin');
                $action = $request->getParam('action');
                $subAction = 'view';
                $crudActions = ['add', 'delete', 'edit', 'view'];
                if (in_array($action, $crudActions)) {
                    $subAction = $action;
                }
                $passes = $request->getParam('pass');
                if (isset($passes) && isset($passes[0])) {
                    $pass = $passes[0];
                    if (in_array($pass, $crudActions)) {
                        $subAction = $action;
                    }
                }
                $result = $this->checkAuthorizationForRoles($SecurityFunctionIds, $RoleIds, $subAction);
                if ($result == 0) {
                    return 0;
                }
            }
        }
        return 1;
    }

    /**
     * POCOR=8534
     * @param $event
     * @return \Cake\Http\Response|int|null
     */
    private function newSecurityCheck($event)
    {

        $request = $this->request;
        $extra['patchEntity'] = true;
        // POCOR-8543 START
        $editAccess = $this->getEditAccess($request);
        if (!$editAccess) {
            $this->Alert->warning('general.notAccess');
            return $this->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
        }

        return 1;
    }

    /**
     * POCOR=8534
     * @param $request
     * @return int
     */
    private function getEditAccess($request)
    {
        $controllerName = $request->getParam('controller');
        $plugin = $request->getParam('plugin');
        $action = $request->getParam('action');
        $passes = $request->getParam('pass');
        $subAction = 'view';
        $crudActions = ['add', 'delete', 'edit', 'view'];
        if (in_array($action, $crudActions)) {
            $subAction = $action;
        }
        if (isset($passes) && isset($passes[0])) {
            $pass = $passes[0];
            if (in_array($pass, $crudActions)) {
                $subAction = $pass;
            }
        }

        if (!in_array($subAction, ['add', 'edit'])) {
            return 1;
        }
        $toCheck = [
            'controller' => $controllerName,
            'plugin' => $plugin,
            'action' => $action,
            $subAction
        ];
        if ($action == $subAction) {
            unset($toCheck[$subAction]);
        }
        if (!$action) {
            unset($toCheck['action']);
        }
        $editAccess = $this->AccessControl->check($toCheck);
        //        die(print_r([$toCheck, $editAccess], true));
        return $editAccess;
    }

    /**
     * Recursively delete a directory and its contents
     *
     * @param string $dir Directory path to delete
     * @return bool True on success, false on failure
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DS . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}

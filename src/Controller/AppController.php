<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since    0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use ControllerAction\Model\Traits\ControllerActionTrait;
use ControllerAction\Model\Traits\SecurityTrait;
use Cake\Utility\Inflector;
use Cake\Cache\Cache;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    use ControllerActionTrait;
    use SecurityTrait;

    private $productName = 'OpenEMIS Core';
    public $helpers = [
        'Text',

        // Custom Helper
        'ControllerAction.ControllerAction',
        'OpenEmis.Navigation',
        'OpenEmis.Resource'
    ];

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
     * @return void
     */
    public function initialize()
    {
        if (!file_exists(CONFIG . 'datasource.php')) {
            $url = Router::url(['plugin' => 'Installer', 'controller' => 'Installer', 'action' => 'index'], true);
            header('Location: '. $url);
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
                        'hashers' => ['Default', 'Legacy']
                    ]
                ],
            ],
            'loginAction' => [
                'plugin' => 'User',
                'controller' => 'Users',
                'action' => 'login'
            ],
            'logoutRedirect' => [
                'plugin' => 'User',
                'controller' => 'Users',
                'action' => 'login'
            ]
        ]);

        $this->loadComponent('Paginator');

        $this->Auth->config('authorize', ['Security']);

        // Custom Components
        $this->loadComponent('Navigation');
        $this->productName = $this->getTheme()['application_name'];
        $this->loadComponent('Localization.Localization', [
            'productName' => $this->productName
        ]);
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
            'productName' => $this->productName,
            'productLogo' => $this->getTheme()['logo'],
            'footerText' => $this->getTheme()['copyright_notice_in_footer'],
            'theme' => $theme,
            'lastModified' => $this->getTheme()['timestamp']
        ]);

        $this->loadComponent('OpenEmis.ApplicationSwitcher', [
            'productName' => $this->productName
        ]);

        // Angular initialization
        $this->loadComponent('Angular.Angular', [
            'app' => 'OE_Core',
            'modules' => [
                'bgDirectives', 'ui.bootstrap', 'ui.bootstrap-slider', 'ui.tab.scroll', 'agGrid', 'app.ctrl', 'advanced.search.ctrl', 'kd-elem-sizes', 'kd-angular-checkbox-radio','multi-select-tree', 'kd-angular-tree-dropdown', 'kd-angular-ag-grid', 'sg.tree.ctrl', 'sg.tree.svc'
            ]
        ]);

        $this->loadComponent('ControllerAction.Alert');
        $this->loadComponent('AccessControl');

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
        if ($this->request->action == 'postLogin') {
            $this->eventManager()->off($this->Csrf);
        }
        $this->loadComponent('TabPermission');
    }

    private function darkenColour($rgb, $darker = 2)
    {
        $hash = (strpos($rgb, '#') !== false) ? '#' : '';
        $rgb = (strlen($rgb) == 7) ? str_replace('#', '', $rgb) : ((strlen($rgb) == 6) ? $rgb : false);
        if (strlen($rgb) != 6) {
            return $hash.'000000';
        }
        $darker = ($darker > 1) ? $darker : 1;

        list($R16,$G16,$B16) = str_split($rgb, 2);

        $R = sprintf("%02X", floor(hexdec($R16)/$darker));
        $G = sprintf("%02X", floor(hexdec($G16)/$darker));
        $B = sprintf("%02X", floor(hexdec($B16)/$darker));

        return $hash.$R.$G.$B;
    }

    public function getTheme()
    {
        $themes = Cache::read('themes');
        if (!$themes) {
            $folder = new Folder();
            $folder->delete(WWW_ROOT . 'img' . DS . 'themes');
            $themes = TableRegistry::get('Themes')->find()
                ->formatResults(function ($results) {
                    $res = [];
                    foreach ($results as $r) {
                        if ($r->content) {
                            $file = new File(WWW_ROOT . 'img' . DS . 'themes' . DS . $r->value, true);
                            $file->write(stream_get_contents($r->content));
                            $file->close();
                        }
                        $code = Inflector::underscore(str_replace(' ', '', $r->name));
                        if ($code == 'login_page_image' || $code == 'favicon') {
                            $res[$code] = !empty($r->value) ? 'themes/' . $r->value : 'default_images/' . $r->default_value;
                        } elseif ($code == 'copyright_notice_in_footer' || $code == 'logo') {
                            $res[$code] = !empty($r->value) ? 'themes/' . $r->value : null;
                        } else {
                            $res[$code] = !empty($r->value) ? $r->value : $r->default_value;
                        }
                    }
                    return $res;
                })
                ->toArray();
            $colour = $themes['colour'];
            $secondaryColour = $this->darkenColour($colour);
            $customPath = ROOT . DS . 'plugins' . DS . 'OpenEmis' . DS . 'webroot' . DS . 'css' . DS . 'themes' . DS . 'custom' . DS;
            $basePath = Router::url(['controller' => false, 'action' => 'index', 'plugin' => false]) === '/' ? '/' : Router::url(['controller' => false, 'action' => 'index', 'plugin' => false]) . '/';
            $loginBackground = $basePath . Configure::read('App.imageBaseUrl') . $themes['login_page_image'];
            $file = new File($customPath . 'layout.core.template.css');
            $template = $file->read();
            $file->close();
            $template = str_replace('${bgImg}', "'$loginBackground'", $template);
            $template = str_replace('${secondColor}', $secondaryColour, $template);
            $template = str_replace('${prodColor}', "#$colour", $template);
            $customPath = WWW_ROOT . 'css' . DS . 'themes' . DS;
            $file = new File($customPath . 'layout.min.css', true);
            $file->write($template);
            $file->close();
            $themes['timestamp'] = TableRegistry::get('Configuration.ConfigItems')->value('themes');
            Cache::write('themes', $themes);
        }
        return $themes;
    }

    /**
     * Before render callback.
     *
     * @param \Cake\Event\Event $event The beforeRender event.
     * @return void
     */
    public function beforeRender(Event $event)
    {
        if (!array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
            $this->set('_serialize', true);
        }
    }

    // Triggered from LocalizationComponent
    // Controller.Localization.getLanguageOptions
    public function getLanguageOptions(Event $event)
    {
        $ConfigItemsTable = TableRegistry::get('Configuration.ConfigItems');
        $languageArr = $ConfigItemsTable->getSystemLanguageOptions();
        $systemLanguage = $languageArr['language'];
        $showLanguage = $languageArr['language_menu'];
        $session = $this->request->session();
        if (!$session->check('System.language_menu')) {
            $session->write('System.language', $systemLanguage);
            $session->write('System.language_menu', $showLanguage);
        }
        return [$showLanguage, $systemLanguage];
    }

    // Triggered from Localization component
    // Controller.Localization.updateLoginLanguage
    public function updateLoginLanguage(Event $event, $user, $lang)
    {
        $UsersTable = TableRegistry::get('User.Users');
        $UsersTable->dispatchEvent('Model.Users.updateLoginLanguage', [$user, $lang], $this);
    }
}

<?php
namespace OpenEmis\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;
use Cake\Http\ServerRequest;
use Cake\Http\Session\SessionInterface;

class OpenEmisComponent extends Component
{

    private $controller;
    private $productName;
    private $productLogo;
    protected $_defaultConfig = [
        'theme' => 'auto',
        'homeUrl' => ['controller' => '/'],
        'SystemNotices' =>  ['controller' => '/'],
        'headerMenu' => [
            'About' => [
                'url' => ['plugin' => false, 'controller' => 'About', 'action' => 'index'],
                'icon' => 'fa-info-circle',
                'escapeTitle' => false
            ],
            'Preferences' => [
                'url' => ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index'],
                'icon' => 'fa-cog',
                'escapeTitle' => false
            ],
            'Help' => [
                'url' => 'https://support.openemis.org/',
                'icon' => 'fa-question-circle',
                'target' => '_blank',
                'escapeTitle' => false
            ],
            '0' => '_divider',
            'Logout' => [
                'url' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'logout'],
                'icon' => 'fa-power-off',
                'escapeTitle' => false
            ]
        ]
    ];

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config): void
    {
        $this->productName = $config['productName'];
        $this->productLogo = isset($config['productLogo']) ? $config['productLogo'] : null;
        $this->footerText = isset($config['footerText']) ? $config['footerText'] : null;
        $this->lastModified = isset($config['lastModified']) ? $config['lastModified'] : 0;
        $this->controller = $this->_registry->getController();
    }

    // Is called after the controller's beforeFilter method but before the controller executes the current action handler.
    public function startup(EventInterface $event)
    {
        $controller = $this->controller;
        $session = $this->getController()->getRequest()->getSession();

        $theme = $this->getTheme();
        $controller->set('theme', $theme);
        if (file_exists(CONFIG . 'app_local.php')) { //POCOR-9203
            $controller->set('SystemNotices', $this->SystemNotices());
        }
        $controller->set('homeUrl', $this->getConfig('homeUrl'));
        $controller->set('headerMenu', $this->getHeaderMenu());
        $controller->set('SystemVersion', $this->getCodeVersion());
        $controller->set('footerText', $this->footerText);
        $controller->set('_productName', $this->productName);
        $controller->set('productLogo', $this->productLogo);
        $controller->set('lastModified', $this->lastModified);
        $brand = Configure::read('schoolMode') ? 'OpenSMIS' : 'OpenEMIS';
        $controller->set('footerBrand', $brand);
        //$controller->set('dateLanguage', I18n::locale());
        $controller->set('dateLanguage', I18n::getLocale());
        // POCOR-8563 start
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $systemDateFormat = $ConfigItems->value('date_format');

// Map PHP format (e.g., 'd-m-Y') to Bootstrap Datepicker format (e.g., 'dd-mm-yyyy')
        $phpToDatepickerFormat = [
            'd' => 'dd',
            'j' => 'd',
            'D' => 'D',        // Mon, Tue (short day)
            'l' => 'DD',       // Monday, Tuesday (full day)
            'm' => 'mm',
            'n' => 'm',
            'M' => 'M',        // Jan, Feb (short month)
            'F' => 'MM',       // January, February (full month)
            'y' => 'yy',       // 2-digit year
            'Y' => 'yyyy'      // 4-digit year
        ];

// Convert format safely using regex
        $datepickerFormat = preg_replace_callback('/[a-zA-Z]/', function ($matches) use ($phpToDatepickerFormat) {
            return $phpToDatepickerFormat[$matches[0]] ?? $matches[0];
        }, $systemDateFormat);
        $controller->set('datepickerFormat', $datepickerFormat);
        $phpToAngularFormat = [
            'd' => 'dd',
            'j' => 'd',
            'm' => 'MM',
            'n' => 'M',
            'M' => 'MMM',   // Jan, Feb
            'F' => 'MMMM',  // January, February
            'y' => 'yy',
            'Y' => 'yyyy',
        ];

        $angularFormat = preg_replace_callback('/[a-zA-Z]/', function ($matches) use ($phpToAngularFormat) {
            return $phpToAngularFormat[$matches[0]] ?? $matches[0];
        }, $systemDateFormat);
        $controller->set('angularFormat', $angularFormat);
        // POCOR-8563 end
        //Retriving the panel width size from session
        if ($session->check('System.layout')) {

            $layout = $session->read('System.layout');
            $controller->set('SystemLayout_leftPanel', 'width:'.$layout['panelLeft'].'px');
            $controller->set('SystemLayout_rightPanel', 'width:'.$layout['panelRight'].'px');
        } else {

            $controller->set('SystemLayout_leftPanel', 'width: 10%');
            $controller->set('SystemLayout_rightPanel', 'width: 90%');
        }
        if (file_exists(CONFIG . 'app_local.php')) {
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $footer = $ConfigItems->value('footer');
            $controller->set('footerText', $footer);
        }

    }

    private function getTheme()
    {
        $controller = $this->controller;
        $session = $this->getController()->getRequest()->getSession();

        $theme = 'OpenEmis.themes/';
        $product = '';
        $css = Configure::read('debug') ? '/layout' : '/layout.min';
        if ($this->getConfig('theme') == 'auto') {
            $query = $this->request->getQuery();

            if (isset($query['theme'])) {
                $product = $query['theme'];
                $theme .= $product . $css;
                $session->write('theme.layout', $theme);

                $session->write('theme.product', $product);
            } else {
                $theme = $session->read('theme.layout');
                $product = $session->read('theme.product');
            }
            if (!empty($theme)) {
                $this->productName .= ' ' . Inflector::camelize($product);
            }
        } else {
            $theme .= $this->getConfig('theme') . $css;
        }
        return $theme;
    }

    private function getHeaderMenu()
    {
        $headerMenu = $this->getConfig('headerMenu');
        return $headerMenu;
    }

    public function getCodeVersion()
    {
        $path = 'version';
        $session = $this->getController()->getRequest()->getSession();
        $version = '';
        if (file_exists($path)) {
            $version = file_get_contents($path);
            $session->write('System.version', $version);
        } else if ($session->check('System.version')) {
            $version = $session->read('System.version');
        }

        return $version;

    }

    public function getLoggedInUserRoles($userId = null)
    {
        $roles = [];
        $usersGroup = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $userRoles = $usersGroup
                    ->find()
                    ->where([$usersGroup->aliasField('security_user_id') => $userId ])
                    ->toArray();
        if (!empty($userRoles)) {
            foreach ($userRoles as $role) {
                $roles[] = $role->security_role_id;
            }
        }
        return (!empty($roles))? $roles: null;
    }

    //POCOR-7210
    private function SystemNotices($userId = null)
    {
        $sessionId =  $this->getController()->getRequest()->getData('session_id');
        $username =  $this->getController()->getRequest()->getData('username');
        $url =  $this->getController()->getRequest()->getData('url');
        if (!empty($url) && !empty($sessionId) && !empty($username)) {
            $userId  = $this->controller->Auth->user('id');
            $isAdmin = $this->controller->AccessControl->isAdmin();

            if(!$isAdmin && $userId != null){
                $usersGroup   = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
                $userNotices  = TableRegistry::getTableLocator()->get('Alert.SecurityUserNotices');

                // 1. Get user role IDs
                $userRoleIdsQuery = $usersGroup->find()
                    ->select(['security_role_id'])
                    ->where(['security_user_id' => $userId])
                    ->enableHydration(false);
                $userRoleIds = array_column($userRoleIdsQuery->toArray(), 'security_role_id');

                // 2. Check permission to view notices
                $havePermissionToView = TableRegistry::getTableLocator()
                    ->get('Security.SecurityRoleFunctions')
                    ->find()
                    ->leftJoin(
                        ['SecurityFunctions' => 'security_functions'],
                        ['SecurityFunctions.id = SecurityRoleFunctions.security_function_id']
                    )
                    ->where([
                        'SecurityFunctions.controller' => 'Systems',
                        'SecurityFunctions.name' => 'Notice Message',
                        'SecurityRoleFunctions.security_role_id IN' => $userRoleIds,
                        'SecurityRoleFunctions._view' => 1
                    ])
                    ->toArray();

                if (empty($havePermissionToView)) {
                    // User has no permission to view notices → no red dot
                    return true;
                }

                // 3. Get notice IDs assigned to user's roles
                $assignedNoticeIdsQuery = $usersGroup->find()
                    ->select(['notice_id' => 'NoticeRoles.notice_id'])
                    ->innerJoin(
                        ['NoticeRoles' => 'notice_roles'],
                        ['SecurityGroupUsers.security_role_id = NoticeRoles.security_role_id']
                    )
                    ->innerJoin(
                        ['Notices' => 'notices'],
                        ['Notices.id = NoticeRoles.notice_id']
                    )
                    ->where([
                        'SecurityGroupUsers.security_user_id IS' => $userId,
                        'Notices.status' => 1
                    ])
                    ->enableHydration(false);

                $assignedNoticeIds = array_column($assignedNoticeIdsQuery->toArray(), 'notice_id');

                // 4. Admins always return true (no red dot)
                if ($isAdmin) {
                    return true;
                }

                // 5. If no assigned notices, return true (no red dot)
                if (empty($assignedNoticeIds)) {
                    return true;
                }

                // 6. Get seen notices
                $seenNoticesQuery = $userNotices->find()
                    ->select(['notice_id'])
                    ->where([
                        'SecurityUserNotices.security_user_id IS' => $userId,
                        'SecurityUserNotices.notice_id IN' => $assignedNoticeIds
                    ])
                    ->enableHydration(false);

                $seenNoticeIds = array_column($seenNoticesQuery->toArray(), 'notice_id');

                // 7. Return true if all assigned notices are seen (no red dot), false if any unseen
                $unseen = array_diff($assignedNoticeIds, $seenNoticeIds);
                return empty($unseen);
            }else{
                return true;
            }
        }
    }
}

?>

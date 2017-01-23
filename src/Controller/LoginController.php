<?php
namespace App\Controller;

use Cake\Event\Event;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use SSO\Controller\LoginController as Controller;

class LoginController extends Controller
{
    private $sso = false;
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('Auth', [
            'loginAction' => [
                'plugin' => 'User',
                'controller' => 'Users',
                'action' => 'login'
            ],
            'logoutRedirect' => [
                'plugin' => null,
                'controller' => 'Login',
                'action' => 'login'
            ]
        ]);
        $this->loadComponent('OpenEmis.OpenEmis', [
            'homeUrl' => ['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index'],
            'headerMenu' => [
                'Preferences' => [
                    'url' => ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']
                ]
            ],
            'theme' => 'core'
        ]);

        $this->Auth->allow('login');
        $this->loadComponent('Localization.Localization');
    }

    public function beforeFilter(Event $event)
    {
        $ssoType = TableRegistry::get('Configuration.ConfigItems')->value('authentication_type');
        $this->sso = $ssoType != 'Local';
        $this->set('_sso', $this->sso);
    }

    public function login()
    {
        $this->viewBuilder()->layout(false);
        if ($this->sso) {
            parent::login();
        }
        if ($this->Auth->user()) {
            return $this->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
        }
        $username = '';
        $password = '';
        $session = $this->request->session();

        $this->set('username', $username);
        $this->set('password', $password);
    }

    // Triggered from LocalizationComponent
    // Controller.Localization.getLanguageOptions
    public function getLanguageOptions(Event $event)
    {
        $dir = new Folder(TMP . 'cache'. DS . 'language_menu', true);
        $filesAndFolders = $dir->read();
        $files = $filesAndFolders[1];
        $languagePath = TMP . 'cache'. DS . 'language_menu' . DS . 'language';
        $languageFile = new File($languagePath, true);
        if (!in_array('language', $files)) {
            $ConfigItemsTable = TableRegistry::get('Configuration.ConfigItems');
            $showLanguage = $ConfigItemsTable->value('language_menu');
            $systemLanguage = $ConfigItemsTable->value('language');
            $languageArr = ['language_menu' => $showLanguage, 'language' => $systemLanguage];
            $status = $languageFile->write(json_encode($languageArr));
        }
        $languageArr = json_decode($languageFile->read(), true);
        $systemLanguage = $languageArr['language'];
        $showLanguage = $languageArr['language_menu'];
        $session = $event->subject()->request->session();

        if ($session->check('System.language_menu')) {
            $session->write('System.language', $systemLanguage);
            $session->write('System.language_menu', $showLanguage);
        }

        return [$showLanguage, $systemLanguage];
    }
}

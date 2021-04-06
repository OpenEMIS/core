<?php
namespace OpenEmis\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;

class OpenEmisComponent extends Component
{

    private $controller;
    private $productName;
    private $productLogo;
    protected $_defaultConfig = [
        'theme' => 'auto',
        'homeUrl' => ['controller' => '/'],
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
    public function initialize(array $config)
    {
        $this->productName = $config['productName'];
        $this->productLogo = isset($config['productLogo']) ? $config['productLogo'] : null;
        $this->footerText = isset($config['footerText']) ? $config['footerText'] : null;
        $this->lastModified = isset($config['lastModified']) ? $config['lastModified'] : 0;
        $this->controller = $this->_registry->getController();
    }

    // Is called after the controller's beforeFilter method but before the controller executes the current action handler.
    public function startup(Event $event)
    {
        $controller = $this->controller;
        $session = $this->request->session();

        $theme = $this->getTheme();
        $controller->set('theme', $theme);
        $controller->set('homeUrl', $this->config('homeUrl'));
        $controller->set('headerMenu', $this->getHeaderMenu());
        $controller->set('SystemVersion', $this->getCodeVersion());
        $controller->set('footerText', $this->footerText);
        $controller->set('_productName', $this->productName);
        $controller->set('productLogo', $this->productLogo);
        $controller->set('lastModified', $this->lastModified);
        $brand = Configure::read('schoolMode') ? 'OpenSMIS' : 'OpenEMIS';
        $controller->set('footerBrand', $brand);
        $controller->set('dateLanguage', I18n::locale());

        //Retriving the panel width size from session
        if ($session->check('System.layout')) {
            $layout = $session->read('System.layout');
            $controller->set('SystemLayout_leftPanel', 'width:'.$layout['panelLeft'].'px');
            $controller->set('SystemLayout_rightPanel', 'width:'.$layout['panelRight'].'px');
        } else {
            $controller->set('SystemLayout_leftPanel', 'width: 10%');
            $controller->set('SystemLayout_rightPanel', 'width: 90%');
        }
        if (file_exists(CONFIG . 'datasource.php')) {
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $footer = $ConfigItems->value('footer');
            $controller->set('footerText', $footer);
        }
        
    }

    private function getTheme()
    {
        $controller = $this->controller;
        $session = $this->request->session();

        $theme = 'OpenEmis.themes/';
        $product = '';
        $css = Configure::read('debug') ? '/layout' : '/layout.min';
        if ($this->config('theme') == 'auto') {
            $query = $this->request->query;

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
            $theme .= $this->config('theme') . $css;
        }
        return $theme;
    }

    private function getHeaderMenu()
    {
        $headerMenu = $this->config('headerMenu');
        return $headerMenu;
    }

    public function getCodeVersion()
    {
        $path = 'version';
        $session = $this->request->session();
        $version = '';

        if (file_exists($path)) {
            $version = file_get_contents($path);
            $session->write('System.version', $version);
        } else if ($session->check('System.version')) {
            $version = $session->read('System.version');
        }
        return $version;
    }
}

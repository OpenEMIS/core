<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Inflector;
use Cake\Core\Configure;

class TabPermissionComponent extends Component
{
    // The other component your component uses
    public $components = ['AccessControl'];

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
    }

    public function checkTabPermission(array $tabArray, array $roleId = [])
    {
        foreach ($tabArray as $key => $content) {
            if (isset($content['url'])) {
                $check = $this->AccessControl->check($content['url'], $roleId);
                if (!$check) {
                    unset($tabArray[$key]);
                }
            }

            if (isset($tabArray[$key]) && isset($content['url']['plugin']) && isset(Configure::read('plugins')[$content['url']['plugin']])) {
                $path = Configure::read('plugins')[$content['url']['plugin']];
                if (!file_exists($path)) {
                    unset($tabArray[$key]);
                }
            }

            $excludedPlugins = (array) Configure::read('School.excludedPlugins');
            if (isset($tabArray[$key]) && (array_key_exists($key, $excludedPlugins) || array_key_exists(Inflector::singularize($key), $excludedPlugins))) {
                unset($tabArray[$key]);
            }
        }

        return $tabArray;
    }
}

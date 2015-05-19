<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace DebugKit\Panel;

use Cake\Controller\Controller;
use Cake\Core\Plugin;
use Cake\Event\Event;
use DebugKit\DebugPanel;

/**
 * Provides a list of included files for the current request
 *
 */
class IncludePanel extends DebugPanel
{

    /**
     * The list of plugins within the application
     *
     * @var <type>
     */
    protected $_pluginPaths = [];

    /**
     * File Types
     *
     * @var array
     */
    protected $_fileTypes = [
        'Cache', 'Config', 'Configure', 'Console', 'Component', 'Controller',
        'Behavior', 'Datasource', 'Model', 'Plugin', 'Test', 'View', 'Utility',
        'Network', 'Routing', 'I18n', 'Log', 'Error'
    ];

    /**
     * Get a list of plugins on construct for later use
     */
    public function __construct()
    {
        foreach (Plugin::loaded() as $plugin) {
            $this->_pluginPaths[$plugin] = Plugin::path($plugin);
        }
    }

    /**
     * Get a list of files that were included and split them out into the various parts of the app
     *
     * @param Controller $controller The controller.
     * @return array
     */
    protected function _prepare(Controller $controller)
    {
        $return = ['core' => [], 'app' => [], 'plugins' => []];

        foreach (get_included_files() as $file) {
            $pluginName = $this->_isPluginFile($file);

            if ($pluginName) {
                $return['plugins'][$pluginName][$this->_getFileType($file)][] = $this->_niceFileName($file, $pluginName);
            } elseif ($this->_isAppFile($file)) {
                $return['app'][$this->_getFileType($file)][] = $this->_niceFileName($file, 'app');
            } elseif ($this->_isCoreFile($file)) {
                $return['core'][$this->_getFileType($file)][] = $this->_niceFileName($file, 'core');
            }
        }

        $return['paths'] = $this->_includePaths();

        ksort($return['core']);
        ksort($return['plugins']);
        ksort($return['app']);
        return $return;
    }

    /**
     * Get the possible include paths
     *
     * @return array
     */
    protected function _includePaths()
    {
        $paths = array_flip(array_filter(explode(PATH_SEPARATOR, get_include_path())));

        unset($paths['.']);
        return array_flip($paths);
    }

    /**
     * Check if a path is part of cake core
     *
     * @param string $file File to check
     * @return bool
     */
    protected function _isCoreFile($file)
    {
        return strstr($file, CAKE);
    }

    /**
     * Check if a path is from APP but not a plugin
     *
     * @param string $file File to check
     * @return bool
     */
    protected function _isAppFile($file)
    {
        return strstr($file, APP);
    }

    /**
     * Check if a path is from a plugin
     *
     * @param string $file File to check
     * @return bool
     */
    protected function _isPluginFile($file)
    {
        foreach ($this->_pluginPaths as $plugin => $path) {
            if (strstr($file, $path)) {
                return $plugin;
            }
        }
        return false;
    }

    /**
     * Replace the path with APP, CORE or the plugin name
     *
     * @param string $file File to check
     * @param string $type The file type
     *  - app for app files
     *  - core for core files
     *  - PluginName for the name of a plugin
     * @return bool
     */
    protected function _niceFileName($file, $type)
    {
        switch ($type) {
            case 'app':
                return str_replace(APP, 'APP/', $file);

            case 'core':
                return str_replace(CAKE, 'CORE/', $file);

            default:
                return str_replace($this->_pluginPaths[$type], $type . '/', $file);
        }
    }

    /**
     * Get the type of file (model, controller etc)
     *
     * @param string $file File to check.
     * @return string
     */
    protected function _getFileType($file)
    {
        foreach ($this->_fileTypes as $type) {
            if (stripos($file, '/' . $type . '/') !== false) {
                return $type;
            }
        }

        return 'Other';
    }

    /**
     * Shutdown callback
     *
     * @param \Cake\Event\Event $event Event
     * @return void
     */
    public function shutdown(Event $event)
    {
        $this->_data = $this->_prepare($event->subject());
    }
}

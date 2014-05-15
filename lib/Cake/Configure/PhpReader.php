<?php
/**
 * PhpReader file
 *
 * PHP 5
 *
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/configuration.html#loading-configuration-files CakePHP(tm) Configuration
 * @package       Cake.Configure
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * PHP Reader allows Configure to load configuration values from
 * files containing simple PHP arrays.
 *
 * Files compatible with PhpReader should define a `$config` variable, that
 * contains all of the configuration data contained in the file.
 *
 * @package       Cake.Configure
 */
class PhpReader implements ConfigReaderInterface {

/**
 * The path this reader finds files on.
 *
 * @var string
 */
	protected $_path = null;

/**
 * Constructor for PHP Config file reading.
 *
 * @param string $path The path to read config files from.  Defaults to APP . 'Config' . DS
 */
	public function __construct($path = null) {
		if (!$path) {
			$path = APP . 'Config' . DS;
		}
		$this->_path = $path;
	}

/**
 * Read a config file and return its contents.
 *
 * Files with `.` in the name will be treated as values in plugins.  Instead of reading from
 * the initialized path, plugin keys will be located using App::pluginPath().
 *
 * @param string $key The identifier to read from.  If the key has a . it will be treated
 *  as a plugin prefix.
 * @return array Parsed configuration values.
 * @throws ConfigureException when files don't exist or they don't contain `$config`.
 *  Or when files contain '..' as this could lead to abusive reads.
 */
	public function read($key) {
		if (strpos($key, '..') !== false) {
			throw new ConfigureException(__d('cake_dev', 'Cannot load configuration files with ../ in them.'));
		}
		if (substr($key, -4) === '.php') {
			$key = substr($key, 0, -4);
		}
		list($plugin, $key) = pluginSplit($key);

		if ($plugin) {
			$file = App::pluginPath($plugin) . 'Config' . DS . $key;
		} else {
			$file = $this->_path . $key;
		}
		$file .= '.php';
		if (!is_file($file)) {
			if (!is_file(substr($file, 0, -4))) {
				throw new ConfigureException(__d('cake_dev', 'Could not load configuration files: %s or %s', $file, substr($file, 0, -4)));
			}
		}
		include $file;
		if (!isset($config)) {
			throw new ConfigureException(
				sprintf(__d('cake_dev', 'No variable $config found in %s.php'), $file)
			);
		}
		return $config;
	}

}

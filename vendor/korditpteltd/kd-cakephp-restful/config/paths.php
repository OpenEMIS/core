<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(dirname(__DIR__)))));
}

define('RESTFUL_PLUGIN_PATH', ROOT . DS . 'vendor' . DS . 'korditpteltd' . DS . 'kd-cakephp-restful');


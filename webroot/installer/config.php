<?php
/*
@OPENEMIS SCHOOL LICENSE LAST UPDATED ON 2014-01-30

OpenEMIS School
Open School Management Information System

Copyright © 2014 KORD IT. This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or any later version. This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please email contact@openemis.org.
*/

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('DIR_ROOT', rtrim(dirname(__DIR__), 'webroot'));
define('CONFIG_DIR', DIR_ROOT . 'config' . DS);

// setting up the web root and server root
$thisFile = str_replace('\\', '/', __FILE__);
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$webRoot  = str_replace(array($docRoot, 'config.php'), '', $thisFile);
$srvRoot  = str_replace('config.php', '', $thisFile);
$app = getRoot();
$configTemplate = "<?php
return [
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => {host},
            'port' => {port},
            'username' => {user},
            'password' => {pass},
            'database' => {database},
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => true,
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ],
    ],
    'schoolMode' => true,
    'debug' => true
];
";
define('WEBROOT', 'http://'.$_SERVER['HTTP_HOST'].$webRoot);
define('CONFIG_FILE', CONFIG_DIR . 'datasource.php');
define('CONFIG_TEMPLATE', $configTemplate);
define('ABSPATH', $srvRoot);

function pr($a)
{
    echo '<pre>';
    print_r($a);
    echo '</pre>';
}
function getHostURL()
{
    return 'http://'.$_SERVER['HTTP_HOST'];
}
function getRoot()
{
    $tmp = explode('/', $_SERVER['SCRIPT_NAME']);
    $tmp = array_reverse($tmp);
    $webroot_array = array();
    $installWordFound = false;
    foreach ($tmp as $t) {
        if ($t == 'installer' && !$installWordFound) {
            $installWordFound = true;
            continue;
        }
        if ($installWordFound && $t!='' && $t != 'webroot') {
            array_push($webroot_array, $t);
        }
    }
    $webroot_array = array_reverse($webroot_array);
    $webroot = implode('/', $webroot_array);
    if ($webroot == '') {
        return $webroot;
    } else {
        return '/'.$webroot;
    }
}

function getSalt()
{
    $salt = "openemis_school_salt";
    return $salt;
}

function password($pass)
{
 // following CakePHP hash method
    return sha1(getSalt().$pass);
}

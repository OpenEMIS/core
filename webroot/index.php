<?php
/**
 * The Front Controller for handling every request
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
// for built-in server
if (php_sapi_name() === 'cli-server') {
    $_SERVER['PHP_SELF'] = '/' . basename(__FILE__);

    $url = parse_url(urldecode($_SERVER['REQUEST_URI']));
    $file = __DIR__ . $url['path'];
    if (strpos($url['path'], '..') === false && strpos($url['path'], '.') !== false && is_file($file)) {
        return false;
    }
}
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

// Bind your application to the server.
$server = new Server(new Application(dirname(__DIR__) . '/config'));

// Run the request/response through the application
// and emit the response.

//POCOR-8676
try 
{
    $response = $server->run();
    $statusCode = $response->getStatusCode();
    if ($statusCode == 500) {
        $ErrorTable = TableRegistry::getTableLocator()->get('System.SystemErrors');
        if (isset($ex) && $ex instanceof Exception) {
            $ErrorTable->insertError($ex);
        } else {
            $ErrorTable->insertError(new Exception('An unknown error occurred'));
        }
        Log::write('error', $ex); // Log the exception
    } else {
        $server->emit($response); 
    }
}catch (Exception $ex) {
    //If an exception occurs, log it and insert it into the error table
    $ErrorTable = TableRegistry::getTableLocator()->get('System.SystemErrors');
    $ErrorTable->insertError($ex);  
    Log::write('error', $ex);     
    throw $ex;                   
}


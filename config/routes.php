<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Core\Plugin;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

/**
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `Router::defaultRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 *
 */
Router::defaultRouteClass('Route');

Router::scope('/', function (RouteBuilder $routes) {
    /**
     * Here, we are connecting '/' (base path) to a controller called 'Pages',
     * its action called 'display', and we pass a param to select the view file
     * to use (in this case, src/Template/Pages/home.ctp)...
     */
    $routes->connect('/', ['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);

    /**
     * ...and connect the rest of 'Pages' controller's URLs.
     */
    $routes->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);

    /**
     * Connect catchall routes for all controllers.
     *
     * Using the argument `DashedRoute`, the `fallbacks` method is a shortcut for
     *    `$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => 'DashedRoute']);`
     *    `$routes->connect('/:controller/:action/*', [], ['routeClass' => 'DashedRoute']);`
     *
     * Any route class can be used with this method, such as:
     * - DashedRoute
     * - InflectedRoute
     * - Route
     * - Or your own route class
     *
     * You can remove these routes once you've connected the
     * routes you want in your application.
     */
    $routes->fallbacks('Route');
});


// For restful controller
Router::scope('/restful', [], function ($routes) {

    $routes->scope('/doc', ['plugin' => 'Restful', 'controller' => 'Doc'], function ($routes) {
        $routes->connect( '/', ['action' => 'index']);
        $routes->connect( '/index', ['action' => 'index']);
        $routes->connect( '/listing', ['action' => 'listing']);
        $routes->connect( '/viewing', ['action' => 'viewing']);
        $routes->connect( '/adding', ['action' => 'adding']);
        $routes->connect( '/editing', ['action' => 'editing']);
        $routes->connect( '/deleting', ['action' => 'deleting']);
        $routes->connect( '/curl', ['action' => 'curl']);
    });

    $routes->scope('/', ['controller' => 'Restful'], function ($routes) {
        $routes->extensions(['json', 'xml']);
        $routes->connect('/', ['action' => 'nothing']);
        $routes->connect('/token', ['action' => 'token', '_method' => 'GET']);
        $routes->connect('/translate', ['action' => 'translate', '_method' => 'GET']);
        $routes->connect('/:model',
            ['action' => 'index', '_method' => 'GET'],
            ['pass' => ['model']]
        );
        $routes->connect( '/:model',
            ['action' => 'add', '_method' => 'POST'],
            ['pass' => ['model']]
        );
        $routes->connect( '/:model/:id',
            ['action' => 'view', '_method' => 'GET'],
            ['pass' => ['model', 'id']]
        );
        $routes->connect( '/:model/:id',
            ['action' => 'edit', '_method' => 'PUT'],
            ['pass' => ['model', 'id']]
        );
        $routes->connect( '/:model/:id',
            ['action' => 'delete', '_method' => 'DELETE'],
            ['pass' => ['model', 'id']]
        );
    });
});

// For restful session
Router::scope('/session', ['plugin' => 'Restful'], function ($routes) {
    $routes->scope('/', ['controller' => 'Session'], function ($routes) {
        $routes->extensions(['json']);

        $routes->connect('/:key',   ['action' => 'check', '_method' => 'CHECK'], ['pass' => ['key']]);
        $routes->connect('/:key',   ['action' => 'read', '_method' => 'GET'], ['pass' => ['key']]);
        $routes->connect('/',       ['action' => 'write', '_method' => 'POST']);
        $routes->connect('/:key',   ['action' => 'delete', '_method' => 'DELETE'], ['pass' => ['key']]);
    });
});

/**
 * Load all plugin routes.  See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
Plugin::routes();

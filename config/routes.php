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
use Cake\Routing\Route\DashedRoute;

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
return function (RouteBuilder $routes) {
    $routes->setRouteClass('Route');
    
    $routes->scope('/', function (RouteBuilder $routes) {
    /**
     * Here, we are connecting '/' (base path) to a controller called 'Pages',
     * its action called 'display', and we pass a param to select the view file
     * to use (in this case, src/Template/Pages/home.ctp)...
     */

    // For SSO Redirection (Critical route added just in case, do not modify)
    $routes->connect('/Users/postLogin/*', ['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin']);

    // For SSO Logout (Critical route added just in case, do not modify)
    $routes->connect('/Users/logout/*', ['plugin' => 'User', 'controller' => 'Users', 'action' => 'logout']);

    // For landing page
    $routes->connect('/', ['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
    $routes->connect('/ResetPassword', ['plugin' => 'User', 'controller' => 'Users', 'action' => 'resetPassword']);
    $routes->connect('/ForgotPassword', ['plugin' => 'User', 'controller' => 'Users', 'action' => 'forgotPassword']);
    $routes->connect('/ForgotUsername', ['plugin' => 'User', 'controller' => 'Users', 'action' => 'forgotUsername']);

    // Standardised login route
    $routes->connect('/Login', ['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
    //$routes->connect('/', ['plugin' => 'System', 'controller' => 'Systems', 'action' => 'Updates']);
    $routes->connect('/Dashboard/*', ['controller' => 'Dashboard', 'action' => 'index']);
    $routes->connect('/Notices/*', ['controller' => 'Notices', 'action' => 'Notices']);
    $routes->connect('/Credentials/*', ['controller' => 'Credentials', 'action' => 'Credentials']);
    $routes->connect('/MoodleApi/*', ['controller' => 'MoodleApi', 'action' => 'MoodleApi']);
    $routes->connect('/Labels/*', ['controller' => 'Labels', 'action' => 'Labels']);
    $routes->connect('/Calendars/*', ['controller' => 'Calendars', 'action' => 'Calendars']);
    $routes->connect('/ScholarshipRecipients/*', ['controller' => 'Scholarship', 'action' => 'ScholarshipRecipients']);
    $routes->connect('/Themes/*', ['controller' => 'Themes', 'action' => 'Themes']);
    $routes->connect('/Profiles/ScholarshipsDirectory/*', ['controller' => 'ScholarshipsDirectory', 'action' => 'ScholarshipsDirectory']);
    $routes->connect('/Locales/*', ['controller' => 'Locales', 'action' => 'Locales']);
    $routes->connect('/LocaleContents/*', ['controller' => 'LocaleContents', 'action' => 'LocaleContents']);

    // Redirect /Systems/* to System plugin (controller is SystemsController in plugin System)
    $routes->connect('/Systems/StaffPolicies/*', ['plugin' => 'System', 'controller' => 'Systems', 'action' => 'StaffPolicies']);
    $routes->connect('/Systems/StaffEntitlements/*', ['plugin' => 'System', 'controller' => 'Systems', 'action' => 'StaffEntitlements']);
    $routes->connect('/Systems/Updates/*', ['plugin' => 'System', 'controller' => 'Systems', 'action' => 'Updates']);

    $routes->connect('/ProfileTemplates/Students/*', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates', 'action' => 'Students']);
    $routes->connect('/ProfileTemplates/Staff/*', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates', 'action' => 'Staff']);
    $routes->connect('/ProfileTemplates/Classes/*', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates', 'action' => 'Classes']);
    $routes->connect('/ProfileTemplates/Institutions/*', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates', 'action' => 'Institutions']);
    $routes->connect('/:controller/:action/*', ['action' => 'Healths', '_method' => 'GET'], ['pass' => ['key']]);

    /**
     * ...and connect the rest of 'Pages' controller's URLs.
     */
    $routes->connect('', ['controller' => '','action' => 'profiles']);
    $routes->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
    $routes->scope('/institutions', function ($routes) {
        $routes->setExtensions(['json']);
        $routes->connect('/class-details/:id', ['controller' => 'InstitutionClasses', 'action' => 'classDetails'])
            ->setPass(['id'])
            ->setMethods(['GET']);
    });


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

    $routes->scope('/Installer', ['plugin' => 'Installer', 'controller' => 'Installer'], function (RouteBuilder $route) {
        $route->setExtensions(['json']);
        $route->connect('/:action');
    });


    // For restful controller
    $routes->scope('/restful', [], function (RouteBuilder $routes) {

        $routes->scope('/doc', ['controller' => 'Doc'], function (RouteBuilder $routes) {
        $routes->connect( '/', ['action' => 'index']);
        $routes->connect( '/index', ['action' => 'index']);
        $routes->connect( '/listing', ['action' => 'listing']);
        $routes->connect( '/viewing', ['action' => 'viewing']);
        $routes->connect( '/adding', ['action' => 'adding']);
        $routes->connect( '/editing', ['action' => 'editing']);
        $routes->connect( '/deleting', ['action' => 'deleting']);
        $routes->connect( '/curl', ['action' => 'curl']);
        });

        $routes->scope('/', ['controller' => 'Restful'], function (RouteBuilder $routes) {
        $routes->setExtensions(['json', 'xml']);
        $routes->connect( '/', ['action' => 'nothing']);

        // Regex ([v][\d+]|[v][\d+][.\d]+|latest), start with a lowercase v followed by the following format (v1 or v1.1 or v1.1.1 ..) or latest
        // Regex reference: https://www.tutorialspoint.com/php/php_regular_expression.htm

        // Preflight Options
        $routes->connect('/*',
            ['action' => 'options', '_method' => 'OPTIONS']
        );

        $routes->connect('/', ['action' => 'nothing']);
        $routes->connect('/token', ['action' => 'token', '_method' => 'GET']);
        $routes->connect('/:version/ajax/:component/:method',
            ['action' => 'ajax', '_method' => 'GET'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)', 'pass' => ['component', 'method']]
        );

        // Translate
        $routes->connect( '/:version/translate',
            ['action' => 'translate', '_method' => 'POST'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)']
        );

        $routes->connect( '/translate',
            ['action' => 'translate', '_method' => 'POST']
        );

        // Index
        $routes->connect( '/:version/:model',
            ['action' => 'index', '_method' => 'GET'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)']
        );

        $routes->connect( '/:model',
            ['action' => 'index', '_method' => 'GET']
        );

        // View
        $routes->connect( '/:version/:model/:id',
            ['action' => 'view', '_method' => 'GET'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)', 'pass' => ['id']]
        );

        $routes->connect( '/:model/:id',
            ['action' => 'view', '_method' => 'GET'],
            ['pass' => ['id']]
        );

        // Download
        $routes->connect( '/:version/:model/download/:id/:fileName/:fileContent',
            ['action' => 'download', '_method' => 'GET'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)', 'pass' => ['id', 'fileName', 'fileContent']]
        );

        // Image
        $routes->connect( '/:version/:model/image/:id/:fileName/:fileContent',
            ['action' => 'image', '_method' => 'GET'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)', 'pass' => ['id', 'fileName', 'fileContent']]
        );

        // Add
        $routes->connect( '/:version/:model',
            ['action' => 'add', '_method' => 'POST'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)']
        );

        $routes->connect( '/:model',
            ['action' => 'add', '_method' => 'POST']
        );

        // Edit
        $routes->connect( '/:version/:model',
            ['action' => 'edit', '_method' => 'PATCH'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)']
        );

        $routes->connect( '/:model',
            ['action' => 'edit', '_method' => 'PATCH']
        );

        // Delete
        $routes->connect( '/:version/:model',
            ['action' => 'delete', '_method' => 'DELETE'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)']
        );

        $routes->connect( '/:model',
            ['action' => 'delete', '_method' => 'DELETE']
        );
    });
    });

    // For restful session
    $routes->scope('/session', ['plugin' => 'Restful'], function (RouteBuilder $routes) {
        $routes->scope('/', ['controller' => 'Session'], function (RouteBuilder $routes) {
        $routes->setExtensions(['json']);

        $routes->connect('/:key', ['action' => 'check', '_method' => 'GET'], ['pass' => ['key']]);
        $routes->connect('/:key', ['action' => 'read', '_method' => 'GET'], ['pass' => ['key']]);
        $routes->connect('/', ['action' => 'write', '_method' => 'POST']);
        $routes->connect('/:key', ['action' => 'delete', '_method' => 'DELETE'], ['pass' => ['key']]);
        });
    });

    // Router::scope('/Areas', ['plugin' => 'Area'], function ($routes) {
    //     $routes->scope('/', ['controller' => 'Areas'], function ($routes) {
    //         $routes->setExtensions(['json']);

    //         $routes->connect('/:key', ['action' => 'index', '_method' => 'GET'], ['pass' => ['key']]);
    //     });
    // });

    $routes->scope('/', function (RouteBuilder $routes) {
        $routes->connect('/Profile', ['controller' => 'Profiles', 'action' => 'Healths']);
    });
};

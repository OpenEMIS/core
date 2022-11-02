<?php
use Cake\Routing\Router;

Router::scope('/restful', [], function ($routes) {

    $routes->scope('/doc', ['controller' => 'Doc'], function ($routes) {
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
        $routes->connect( '/', ['action' => 'nothing']);

        // Regex ([v][\d+]|[v][\d+][.\d]+|latest), start with a lowercase v followed by the following format (v1 or v1.1 or v1.1.1 ..) or latest
        // Regex reference: https://www.tutorialspoint.com/php/php_regular_expression.htm

        // Preflight Options
        $routes->connect('/*',
            ['action' => 'options', '_method' => 'OPTIONS']
        );

        // Translate
        $routes->connect( '/:version/translate',
            ['action' => 'translate', '_method' => 'POST'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)']
        );

        $routes->connect( '/translate',
            ['action' => 'translate', '_method' => 'POST']
        );

        // Schema
        $routes->connect('/:version/:model/schema',
            ['action' => 'schema', '_method' => 'GET'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)']
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
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)', 'pass' => ['model', 'id']]
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
        $routes->connect( '/:version/:model/:id',
            ['action' => 'edit', '_method' => 'PATCH'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)', 'pass' => ['id']]
        );

        $routes->connect( '/:model/:id',
            ['action' => 'edit', '_method' => 'PATCH'],
            ['pass' => ['id']]
        );

        // Delete
        $routes->connect( '/:version/:model/:id',
            ['action' => 'delete', '_method' => 'DELETE'],
            ['version' => '([v][\d+]|[v][\d+][.\d]+|latest)', 'pass' => ['id']]
        );

        $routes->connect( '/:model/:id',
            ['action' => 'delete', '_method' => 'DELETE'],
            ['pass' => ['id']]
        );
    });
});

Router::scope('/session', ['plugin' => 'Restful'], function ($routes) {
    $routes->scope('/', ['controller' => 'Session'], function ($routes) {
        $routes->extensions(['json']);

        $routes->connect('/:key', ['action' => 'check', '_method' => 'CHECK'], ['pass' => ['key']]);
        $routes->connect('/:key', ['action' => 'read', '_method' => 'GET'], ['pass' => ['key']]);
        $routes->connect('/', ['action' => 'write', '_method' => 'POST']);
        $routes->connect('/:key', ['action' => 'delete', '_method' => 'DELETE'], ['pass' => ['key']]);
    });
});

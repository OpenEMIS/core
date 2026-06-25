<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App;

use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Google\Auth\Middleware\SimpleMiddleware;
use Google\Auth\Middleware\AuthTokenMiddleware;


/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        } else {
            FactoryLocator::add(
                'Table',
                (new TableLocator())->allowFallbackClass(false)
            );
        }

        /*
         * Only try to load DebugKit in development mode
         * Debug Kit should not be installed on a production system
         */
        if (Configure::read('debug')) {
            // $this->addPlugin('DebugKit');
        }
        //$this->addTableAlias('security_users', UsersTable::class);
        $this->addPlugin('Migrations');

        // Custom Plugins

        // Essential Plugins
        $this->addPlugin('OpenEmis', ['autoload' => true]);
        $this->addPlugin('ControllerAction', ['autoload' => true]);
        $this->addPlugin('Angular', ['routes' => true, 'autoload' => true]);
        // Register Page plugin from vendor directory
        $this->addPlugin('Page', [
            'path' => ROOT . DS . 'vendor' . DS . 'korditpteltd' . DS . 'ikpge-cakephp-page' . DS,
            'routes' => true, 
            'autoload' => true
        ]); //POCOR-8074

        // Localizations
        $this->addPlugin('Localization', ['routes' => true, 'autoload' => true]);

        // Main Modules
        $this->addPlugin('Area', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Manuals', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Alert', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('AcademicPeriod', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Directory', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('FieldOption', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Institution', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('User', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Student', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Staff', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Education', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Assessment', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Textbook', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Security', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Survey', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Rest', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Report', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Rubric', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Workflow', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('CustomField', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Risk', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('InstitutionCustomField', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('StudentCustomField', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('StaffCustomField', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Infrastructure', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Error', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Import', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('API', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Log', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Training', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Map', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Health', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Cache', ['routes' => true, 'autoload' => true]);
        // Register Restful plugin from vendor directory
        $this->addPlugin('Restful', [
            'path' => ROOT . DS . 'vendor' . DS . 'korditpteltd' . DS . 'ikrst-cakephp-restful' . DS,
            'routes' => true,
            'autoload' => true
        ]);
        // $this->addPlugin('ADmad/JwtAuth');
        // Register SSO plugin from vendor directory
        $this->addPlugin('SSO', [
            'path' => ROOT . DS . 'vendor' . DS . 'korditpteltd' . DS . 'iksso-cakephp-sso' . DS,
            'routes' => true,
            'autoload' => true
        ]);
        // Register Webhook plugin from vendor directory (commented out, uncomment if needed)
        // $this->addPlugin('Webhook', [
        //     'path' => ROOT . DS . 'vendor' . DS . 'korditpteltd' . DS . 'kd-cakephp-webhooks' . DS,
        //     'routes' => true,
        //     'autoload' => true
        // ]);
        $this->addPlugin('System', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('InstitutionRepeater', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Examination', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Configuration', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('CustomExcel', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Competency', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('ReportCard', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Profile', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Transport', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Installer', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Quality', ['autoload' => true]);
        $this->addPlugin('Cases', ['autoload' => true]);
        $this->addPlugin('Counselling', ['autoload' => true]);
        $this->addPlugin('Outcome', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Theme', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('StaffAppraisal', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Scholarship', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Attendance', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Guardian', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Email', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('SpecialNeeds', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('MoodleApi', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Historical', ['autoload' => true]);
        $this->addPlugin('Schedule', ['autoload' => true]);
        $this->addPlugin('ProfileTemplate', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('Meal', ['routes' => true, 'autoload' => true]);
        $this->addPlugin('GuardianNav', ['routes' => true, 'autoload' => true]);

        $this->addPlugin('OAuth', ['routes' => true]);

        $this->addPlugin('Archive', ['bootstrap' => false, 'routes' => true]);
        $this->addPlugin('Gpa', ['routes' => true, 'autoload' => true]);

        // Load more plugins here
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            /* ->add(new CsrfProtectionMiddleware([
                 'httpOnly' => true,
             ]))*/
            //->add(new CsrfProtectionMiddleware())
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))
            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))
            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance. For that when
            // creating the middleware instance specify the cache config name by
            // using it's second constructor argument:
            // `new RoutingMiddleware($this, '_cake_routes_')`
            ->add(new RoutingMiddleware($this))
            // Parse various types of encoded request bodies so that they are
            // available as array through $request->getData()
            // https://book.cakephp.org/4/en/controllers/middleware.html#body-parser-middleware
            ->add(new BodyParserMiddleware());

            // Cross Site Request Forgery (CSRF) Protection Middleware
            // https://book.cakephp.org/4/en/security/csrf.html#cross-site-request-forgery-csrf-middleware
            /*->add(new CsrfProtectionMiddleware([
                'httponly' => true,
            ]));*/

        return $middlewareQueue;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
    }

    /**
     * Bootstrapping for CLI application.
     *
     * That is when running commands.
     *
     * @return void
     */
    protected function bootstrapCli(): void
    {
        $this->addOptionalPlugin('Cake/Repl');
        $this->addOptionalPlugin('Bake');

        $this->addPlugin('Migrations');

        // Load more plugins here
    }
}

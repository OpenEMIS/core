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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TestApp\Core\TestApp;

/**
 * AppTest class
 *
 */
class AppTest extends TestCase
{

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload();
    }

    /**
     * testClassname
     *
     * $checkCake and $existsInCake are derived from the input parameters
     *
     * @param string $class Class name
     * @param string $type Class type
     * @param string $suffix Class suffix
     * @param bool $existsInBase Whether class exists in base.
     * @param mixed $expected Expected value.
     * @return void
     * @dataProvider classnameProvider
     */
    public function testClassname($class, $type, $suffix = '', $existsInBase = false, $expected = false)
    {
        Configure::write('App.namespace', 'TestApp');
        $i = 0;
        TestApp::$existsInBaseCallback = function ($name, $namespace) use ($existsInBase, $class, $expected, &$i) {
            if ($i++ === 0) {
                return $existsInBase;
            }
            $checkCake = (!$existsInBase || strpos('.', $class));
            if ($checkCake) {
                return (bool)$expected;
            }
            return false;
        };
        $return = TestApp::classname($class, $type, $suffix);
        $this->assertSame($expected, $return);
    }

    /**
     * classnameProvider
     *
     * Return test permutations for testClassname method. Format:
     *  classname
     *  type
     *  suffix
     *  existsInBase (Base meaning App or plugin namespace)
     *  expected return value
     *
     * @return void
     */
    public function classnameProvider()
    {
        return [
            ['Does', 'Not', 'Exist'],

            ['Exists', 'In', 'App', true, 'TestApp\In\ExistsApp'],
            ['Also/Exists', 'In', 'App', true, 'TestApp\In\Also\ExistsApp'],
            ['Also', 'Exists/In', 'App', true, 'TestApp\Exists\In\AlsoApp'],
            ['Also', 'Exists/In/Subfolder', 'App', true, 'TestApp\Exists\In\Subfolder\AlsoApp'],
            ['No', 'Suffix', '', true, 'TestApp\Suffix\No'],

            ['MyPlugin.Exists', 'In', 'Suffix', true, 'MyPlugin\In\ExistsSuffix'],
            ['MyPlugin.Also/Exists', 'In', 'Suffix', true, 'MyPlugin\In\Also\ExistsSuffix'],
            ['MyPlugin.Also', 'Exists/In', 'Suffix', true, 'MyPlugin\Exists\In\AlsoSuffix'],
            ['MyPlugin.Also', 'Exists/In/Subfolder', 'Suffix', true, 'MyPlugin\Exists\In\Subfolder\AlsoSuffix'],
            ['MyPlugin.No', 'Suffix', '', true, 'MyPlugin\Suffix\No'],

            ['Vend/MPlugin.Exists', 'In', 'Suffix', true, 'Vend\MPlugin\In\ExistsSuffix'],
            ['Vend/MPlugin.Also/Exists', 'In', 'Suffix', true, 'Vend\MPlugin\In\Also\ExistsSuffix'],
            ['Vend/MPlugin.Also', 'Exists/In', 'Suffix', true, 'Vend\MPlugin\Exists\In\AlsoSuffix'],
            ['Vend/MPlugin.Also', 'Exists/In/Subfolder', 'Suffix', true, 'Vend\MPlugin\Exists\In\Subfolder\AlsoSuffix'],
            ['Vend/MPlugin.No', 'Suffix', '', true, 'Vend\MPlugin\Suffix\No'],

            ['Exists', 'In', 'Cake', false, 'Cake\In\ExistsCake'],
            ['Also/Exists', 'In', 'Cake', false, 'Cake\In\Also\ExistsCake'],
            ['Also', 'Exists/In', 'Cake', false, 'Cake\Exists\In\AlsoCake'],
            ['Also', 'Exists/In/Subfolder', 'Cake', false, 'Cake\Exists\In\Subfolder\AlsoCake'],
            ['No', 'Suffix', '', false, 'Cake\Suffix\No'],

            // Realistic examples returning nothing
            ['App', 'Core', 'Suffix'],
            ['Auth', 'Controller/Component'],
            ['Unknown', 'Controller', 'Controller'],

            // Real examples returning classnames
            ['App', 'Core', '', false, 'Cake\Core\App'],
            ['Auth', 'Controller/Component', 'Component', false, 'Cake\Controller\Component\AuthComponent'],
            ['File', 'Cache/Engine', 'Engine', false, 'Cake\Cache\Engine\FileEngine'],
            ['Command', 'Shell/Task', 'Task', false, 'Cake\Shell\Task\CommandTask'],
            ['Upgrade/Locations', 'Shell/Task', 'Task', false, 'Cake\Shell\Task\Upgrade\LocationsTask'],
            ['Pages', 'Controller', 'Controller', true, 'TestApp\Controller\PagesController'],
        ];
    }

    /**
     * test path() with a plugin.
     *
     * @return void
     */
    public function testPathWithPlugins()
    {
        $basepath = TEST_APP . 'Plugin' . DS;
        Plugin::load('TestPlugin');

        $result = App::path('Controller', 'TestPlugin');
        $this->assertPathEquals($basepath . 'TestPlugin' . DS . 'src' . DS . 'Controller' . DS, $result[0]);

        Plugin::load('Company/TestPluginThree');
        $result = App::path('Controller', 'Company/TestPluginThree');
        $expected = $basepath . 'Company' . DS . 'TestPluginThree' . DS . 'src' . DS . 'Controller' . DS;
        $this->assertPathEquals($expected, $result[0]);
    }

    /**
     * testCore method
     *
     * @return void
     */
    public function testCore()
    {
        $model = App::core('Model');
        $this->assertEquals([CAKE . 'Model' . DS], $model);

        $view = App::core('View');
        $this->assertEquals([CAKE . 'View' . DS], $view);

        $controller = App::core('Controller');
        $this->assertEquals([CAKE . 'Controller' . DS], $controller);

        $component = App::core('Controller/Component');
        $this->assertEquals([CAKE . 'Controller' . DS . 'Component' . DS], str_replace('/', DS, $component));

        $auth = App::core('Controller/Component/Auth');
        $this->assertEquals([CAKE . 'Controller' . DS . 'Component' . DS . 'Auth' . DS], str_replace('/', DS, $auth));

        $datasource = App::core('Model/Datasource');
        $this->assertEquals([CAKE . 'Model' . DS . 'Datasource' . DS], str_replace('/', DS, $datasource));
    }
}

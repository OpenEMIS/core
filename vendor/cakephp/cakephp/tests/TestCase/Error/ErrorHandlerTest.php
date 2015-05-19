<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Error;
use Cake\Error\ErrorHandler;
use Cake\Error\ExceptionRenderer;
use Cake\Log\Log;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Testing stub.
 */
class TestErrorHandler extends ErrorHandler
{

    /**
     * Access the response used.
     *
     * @var \Cake\Network\Response
     */
    public $response;

    /**
     * Stub output clearing in tests.
     *
     * @return void
     */
    protected function _clearOutput()
    {
        // noop
    }

    /**
     * Stub sending responses
     *
     * @return void
     */
    protected function _sendResponse($response)
    {
        $this->response = $response;
    }
}

/**
 * ErrorHandlerTest class
 */
class ErrorHandlerTest extends TestCase
{

    protected $_restoreError = false;

    /**
     * setup create a request object to get out of router later.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Router::reload();

        $request = new Request();
        $request->base = '';
        Router::setRequestInfo($request);
        Configure::write('debug', true);

        $this->_logger = $this->getMock('Psr\Log\LoggerInterface');

        Log::reset();
        Log::config('error_test', [
            'engine' => $this->_logger
        ]);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Log::reset();
        if ($this->_restoreError) {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    /**
     * test error handling when debug is on, an error should be printed from Debugger.
     *
     * @return void
     */
    public function testHandleErrorDebugOn()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();
        $this->_restoreError = true;

        ob_start();
        $wrong = $wrong + 1;
        $result = ob_get_clean();

        $this->assertRegExp('/<pre class="cake-error">/', $result);
        $this->assertRegExp('/<b>Notice<\/b>/', $result);
        $this->assertRegExp('/variable:\s+wrong/', $result);
    }

    /**
     * provides errors for mapping tests.
     *
     * @return void
     */
    public static function errorProvider()
    {
        return [
            [E_USER_NOTICE, 'Notice'],
            [E_USER_WARNING, 'Warning'],
        ];
    }

    /**
     * test error mappings
     *
     * @dataProvider errorProvider
     * @return void
     */
    public function testErrorMapping($error, $expected)
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();
        $this->_restoreError = true;

        ob_start();
        trigger_error('Test error', $error);
        $result = ob_get_clean();

        $this->assertContains('<b>' . $expected . '</b>', $result);
    }

    /**
     * test error prepended by @
     *
     * @return void
     */
    public function testErrorSuppressed()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();
        $this->_restoreError = true;

        ob_start();
        //@codingStandardsIgnoreStart
        @include 'invalid.file';
        //@codingStandardsIgnoreEnd
        $result = ob_get_clean();
        $this->assertTrue(empty($result));
    }

    /**
     * Test that errors go into Cake Log when debug = 0.
     *
     * @return void
     */
    public function testHandleErrorDebugOff()
    {
        Configure::write('debug', false);
        $errorHandler = new ErrorHandler();
        $errorHandler->register();
        $this->_restoreError = true;

        $this->_logger->expects($this->once())
            ->method('log')
            ->with(
                $this->matchesRegularExpression('(notice|debug)'),
                'Notice (8): Undefined variable: out in [' . __FILE__ . ', line ' . (__LINE__ + 3) . ']' . "\n\n"
            );

        $out = $out + 1;
    }

    /**
     * Test that errors going into Cake Log include traces.
     *
     * @return void
     */
    public function testHandleErrorLoggingTrace()
    {
        Configure::write('debug', false);
        $errorHandler = new ErrorHandler(['trace' => true]);
        $errorHandler->register();
        $this->_restoreError = true;

        $this->_logger->expects($this->once())
            ->method('log')
            ->with(
                $this->matchesRegularExpression('(notice|debug)'),
                $this->logicalAnd(
                    $this->stringContains('Notice (8): Undefined variable: out in '),
                    $this->stringContains('Trace:'),
                    $this->stringContains(__NAMESPACE__ . '\ErrorHandlerTest::testHandleErrorLoggingTrace()')
                )
            );

        $out = $out + 1;
    }

    /**
     * test handleException generating a page.
     *
     * @return void
     */
    public function testHandleException()
    {
        $error = new NotFoundException('Kaboom!');
        $errorHandler = new TestErrorHandler();

        $errorHandler->handleException($error);
        $this->assertContains('Kaboom!', $errorHandler->response->body(), 'message missing.');
    }

    /**
     * test handleException generating log.
     *
     * @return void
     */
    public function testHandleExceptionLog()
    {
        $errorHandler = new TestErrorHandler([
            'log' => true,
        ]);

        $error = new NotFoundException('Kaboom!');

        $this->_logger->expects($this->once())
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains('[Cake\Network\Exception\NotFoundException] Kaboom!'),
                $this->stringContains('ErrorHandlerTest->testHandleExceptionLog')
            ));

        $errorHandler->handleException($error);
        $this->assertContains('Kaboom!', $errorHandler->response->body(), 'message missing.');
    }

    /**
     * test handleException generating log.
     *
     * @return void
     */
    public function testHandleExceptionLogSkipping()
    {
        $notFound = new NotFoundException('Kaboom!');
        $forbidden = new ForbiddenException('Fooled you!');

        $this->_logger->expects($this->once())
            ->method('log')
            ->with(
                'error',
                $this->stringContains('[Cake\Network\Exception\ForbiddenException] Fooled you!')
            );

        $errorHandler = new TestErrorHandler([
            'log' => true,
            'skipLog' => ['Cake\Network\Exception\NotFoundException'],
        ]);

        $errorHandler->handleException($notFound);
        $this->assertContains('Kaboom!', $errorHandler->response->body(), 'message missing.');

        $errorHandler->handleException($forbidden);
        $this->assertContains('Fooled you!', $errorHandler->response->body(), 'message missing.');
    }

    /**
     * tests it is possible to load a plugin exception renderer
     *
     * @return void
     */
    public function testLoadPluginHandler()
    {
        Plugin::load('TestPlugin');
        $errorHandler = new TestErrorHandler([
            'exceptionRenderer' => 'TestPlugin.TestPluginExceptionRenderer',
        ]);

        $error = new NotFoundException('Kaboom!');
        $errorHandler->handleException($error);

        $result = $errorHandler->response;
        $this->assertEquals('Rendered by test plugin', $result);
    }

    /**
     * test handleFatalError generating a page.
     *
     * These tests start two buffers as handleFatalError blows the outer one up.
     *
     * @return void
     */
    public function testHandleFatalErrorPage()
    {
        $line = __LINE__;
        $errorHandler = new TestErrorHandler();
        Configure::write('debug', true);

        $errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
        $result = $errorHandler->response->body();
        $this->assertContains('Something wrong', $result, 'message missing.');
        $this->assertContains(__FILE__, $result, 'filename missing.');
        $this->assertContains((string)$line, $result, 'line missing.');

        Configure::write('debug', false);
        $errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
        $result = $errorHandler->response->body();
        $this->assertNotContains('Something wrong', $result, 'message must not appear.');
        $this->assertNotContains(__FILE__, $result, 'filename must not appear.');
        $this->assertContains('An Internal Error Has Occurred.', $result);
    }

    /**
     * test handleFatalError generating log.
     *
     * @return void
     */
    public function testHandleFatalErrorLog()
    {
        $this->_logger->expects($this->at(0))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains(__FILE__ . ', line ' . (__LINE__ + 9)),
                $this->stringContains('Fatal Error (1)'),
                $this->stringContains('Something wrong')
            ));
        $this->_logger->expects($this->at(1))
            ->method('log')
            ->with('error', $this->stringContains('[Cake\Error\FatalErrorException] Something wrong'));

        $errorHandler = new TestErrorHandler(['log' => true]);
        $errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, __LINE__);
    }
}

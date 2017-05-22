<?php
/**
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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Configure;
use Cake\Error\Debugger;

    /**
     * Basic defines for timing functions.
     */
    define('SECOND', 1);
    define('MINUTE', 60);
    define('HOUR', 3600);
    define('DAY', 86400);
    define('WEEK', 604800);
    define('MONTH', 2592000);
    define('YEAR', 31536000);

if (!function_exists('debug')) {
    /**
     * Prints out debug information about given variable and returns the
     * variable that was passed.
     *
     * Only runs if debug mode is enabled.
     *
     * @param mixed $var Variable to show debug information for.
     * @param bool|null $showHtml If set to true, the method prints the debug data in a browser-friendly way.
     * @param bool $showFrom If set to true, the method prints from where the function was called.
     * @return mixed The same $var that was passed
     * @link http://book.cakephp.org/3.0/en/development/debugging.html#basic-debugging
     * @link http://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#debug
     */
    function debug($var, $showHtml = null, $showFrom = true)
    {
        if (!Configure::read('debug')) {
            return $var;
        }

        $location = [];
        if ($showFrom) {
            $trace = Debugger::trace(['start' => 1, 'depth' => 2, 'format' => 'array']);
            $location = [
                'line' => $trace[0]['line'],
                'file' => $trace[0]['file']
            ];
        }

        Debugger::printVar($var, $location, $showHtml);

        return $var;
    }

}

if (!function_exists('stackTrace')) {
    /**
     * Outputs a stack trace based on the supplied options.
     *
     * ### Options
     *
     * - `depth` - The number of stack frames to return. Defaults to 999
     * - `args` - Should arguments for functions be shown? If true, the arguments for each method call
     *   will be displayed.
     * - `start` - The stack frame to start generating a trace from. Defaults to 1
     *
     * @param array $options Format for outputting stack trace
     * @return mixed Formatted stack trace
     */
    function stackTrace(array $options = [])
    {
        if (!Configure::read('debug')) {
            return;
        }

        $options += ['start' => 0];
        $options['start']++;
        echo Debugger::trace($options);
    }

}

if (!function_exists('breakpoint')) {
    /**
     * Command to return the eval-able code to startup PsySH in interactive debugger
     * Works the same way as eval(\Psy\sh());
     * psy/psysh must be loaded in your project
     * @link http://psysh.org/
     * ```
     * eval(breakpoint());
     * ```
     * @return string
     */
    function breakpoint()
    {
        if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') && class_exists('\Psy\Shell')) {
            return 'extract(\Psy\Shell::debug(get_defined_vars(), isset($this) ? $this : null));';
        }
        trigger_error(
            "psy/psysh must be installed and you must be in a CLI environment to use the breakpoint function",
            E_USER_WARNING
        );
    }
}

if (!function_exists('dd')) {
    /**
     * Prints out debug information about given variable and dies.
     *
     * Only runs if debug mode is enabled.
     * It will otherwise just continue code execution and ignore this function.
     *
     * @param mixed $var Variable to show debug information for.
     * @param bool|null $showHtml If set to true, the method prints the debug data in a browser-friendly way.
     * @return void
     * @link http://book.cakephp.org/3.0/en/development/debugging.html#basic-debugging
     */
    function dd($var, $showHtml = null)
    {
        if (!Configure::read('debug')) {
            return;
        }

        $trace = Debugger::trace(['start' => 1, 'depth' => 2, 'format' => 'array']);
        $location = [
            'line' => $trace[0]['line'],
            'file' => $trace[0]['file']
        ];

        Debugger::printVar($var, $location);
        die(1);
    }
}

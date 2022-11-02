<?php
/**
 * PHP Version 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://pear.php.net/package/PHP_CodeSniffer_CakePHP
 * @since         CakePHP CodeSniffer 0.1.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Ensures method names are correct depending on whether they are public
 * or private, and that functions are named correctly.
 *
 */
class CakePHP_Sniffs_NamingConventions_ValidFunctionNameSniff extends PHP_CodeSniffer_Standards_AbstractScopeSniff
{

/**
 * A list of all PHP magic methods.
 *
 * @var array
 */
    protected $_magicMethods = [
        'construct',
        'destruct',
        'call',
        'callStatic',
        'debugInfo',
        'get',
        'set',
        'isset',
        'unset',
        'sleep',
        'wakeup',
        'toString',
        'set_state',
        'clone',
        'invoke',
    ];

/**
 * Constructs a PEAR_Sniffs_NamingConventions_ValidFunctionNameSniff.
 */
    public function __construct()
    {
        parent::__construct([T_CLASS, T_INTERFACE], [T_FUNCTION], true);
    }

/**
 * Processes the tokens within the scope.
 *
 * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
 * @param integer $stackPtr The position where this token was found.
 * @param integer $currScope The position of the current scope.
 * @return void
 */
    protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
    {
        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($methodName === null) {
            // Ignore closures.
            return;
        }

        $className = $phpcsFile->getDeclarationName($currScope);
        $errorData = [$className . '::' . $methodName];

        // PHP4 constructors are allowed to break our rules.
        if ($methodName === $className) {
            return;
        }

        // PHP4 destructors are allowed to break our rules.
        if ($methodName === '_' . $className) {
            return;
        }

        // Ignore magic methods
        if (preg_match('/^__(' . implode('|', $this->_magicMethods) . ')$/', $methodName)) {
            return;
        }

        $methodProps = $phpcsFile->getMethodProperties($stackPtr);
        if ($methodProps['scope_specified'] === false) {
            // Let another sniffer take care of that
            return;
        }

        $isPublic = $methodProps['scope'] === 'public';

        if ($isPublic === true) {
            if ($methodName[0] === '_') {
                $error = 'Public method name "%s" must not be prefixed with underscore';
                $phpcsFile->addError($error, $stackPtr, 'PublicWithUnderscore', $errorData);
                return;
            }
            // Underscored public methods in controller are allowed to break our rules.
            if (substr($className, -10) === 'Controller') {
                return;
            }
            // Underscored public methods in shells are allowed to break our rules.
            if (substr($className, -5) === 'Shell') {
                return;
            }
            // Underscored public methods in tasks are allowed to break our rules.
            if (substr($className, -4) === 'Task') {
                return;
            }
        }
    }

/**
 * Processes the tokens outside the scope.
 *
 * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
 * @param integer $stackPtr  The position where this token was found.
 * @return void
 */
    protected function processTokenOutsideScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
    }
}

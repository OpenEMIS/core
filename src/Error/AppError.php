<?php
namespace App\Error;

use Cake\Error\ErrorHandler;

class AppError extends ErrorHandler
{
	protected function _displayException($exception) {
        $this->_options['exceptionRenderer'] = 'App\Error\AppExceptionRenderer';
        parent::_displayException($exception);
    }

    protected function _displayError($error, $debug) {
        //The parent will check if the debug mode is set to true, if it is true it will render the debug
        //parent::_displayError($error, $debug);
    }

}
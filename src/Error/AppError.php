<?php
namespace App\Error;

use Cake\Routing\Exception\MissingControllerException;
use Cake\Network\Exception\NotFoundException;
use Cake\Error\ErrorHandler;

class AppError extends ErrorHandler
{
	protected function _displayException($exception) {
		$renderer = App::className($this->_options['exceptionRenderer'], 'Error');
    }

    protected function _displayError($error, $debug) {

    }

}
<?php
namespace App\Error;

use Cake\Routing\Exception\MissingControllerException;
use Cake\Network\Exception\NotFoundException;
use Cake\Error\ErrorHandler;

class AppError extends ErrorHandler
{
    public function _displayException($exception)
    {
        if ($exception instanceof NotFoundException) {
            // Here handle MissingControllerException by yourself
        } else {
            parent::_displayException($exception);
        }
    }
}
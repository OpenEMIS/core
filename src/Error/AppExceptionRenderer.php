<?php

namespace App\Error;

use Cake\Error\ExceptionRenderer;
use Exception;

class AppExceptionRenderer extends ExceptionRenderer
{

    public function forbidden($error){
    	return $this->controller->redirect(['controller' => 'Errors', 'action' => 'error403', 'plugin'=>'Error']);
    }

    public function _template(Exception $exception, $method, $code){
    	if ($code!=403) {
    		$this->controller->redirect(['controller' => 'Errors', 'action' => 'error404', 'plugin'=>'Error']);
    		return 'default';
    	}
    	return false;
    }
}

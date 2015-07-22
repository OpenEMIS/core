<?php

namespace App\Error;

use Cake\Controller\Controller;
use Cake\Error\ExceptionRenderer;
use Cake\Network\Exception\HttpException;

class AppExceptionRenderer extends ExceptionRenderer
{

    public function forbidden($error){
    	return $this->controller->redirect(['controller' => 'Errors', 'action' => 'error403', 'plugin'=>'Error']);
    }

    public function _template(\Exception $exception, $method, $code){

    	if ($code!=403) {
    		    	// pr($code);die;
    		return $this->controller->redirect(['controller' => 'Errors', 'action' => 'error404', 'plugin'=>'Error']);
    	}
    	return false;
    }
}

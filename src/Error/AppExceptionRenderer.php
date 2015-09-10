<?php

namespace App\Error;

use Cake\Error\ExceptionRenderer;

class AppExceptionRenderer extends ExceptionRenderer
{

    // public function forbidden($error){
    // 	return $this->controller->redirect(['controller' => 'Errors', 'action' => 'error403', 'plugin'=>'Error']);
    // }

    // public function _template(\Exception $exception, $method, $code){
    // 	if ($code!=403) {
    // 		return $this->controller->redirect(['controller' => 'Errors', 'action' => 'error404', 'plugin'=>'Error']);
    // 	}
    // 	return false;
    // }
}

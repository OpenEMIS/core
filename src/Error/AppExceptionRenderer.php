<?php

namespace App\Error;

use Cake\Error\Renderer\WebExceptionRenderer;
use Throwable;

class AppExceptionRenderer extends WebExceptionRenderer
{

    public function forbidden(Throwable $error){
    	return $this->controller->redirect(['controller' => 'Errors', 'action' => 'error403', 'plugin'=>'Error']);
    }

    protected function _template(Throwable $exception, string $method, int $code): string
    {
    	if ($code != 403) {
    		$this->controller->redirect(['controller' => 'Errors', 'action' => 'error404', 'plugin'=>'Error']);
    		return 'default';
    	}
    	return parent::_template($exception, $method, $code);
    }
}

<?php
namespace CustomExcel\Controller;

use ArrayObject;

use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Event\Event;

use App\Controller\AppController;

class CustomExcelsController extends AppController
{
	public function initialize()
	{
        parent::initialize();
        $this->loadComponent('CustomExcel.ExcelReport');
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $this->Navigation->addCrumb('Custom Excel', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

    	$header = __('Custom Excel');
    	$header .= ' - ' . $model->getHeader($model->alias);
    	$this->set('contentHeader', $header);
    }

    // CAv4
    public function ExcelTemplates() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'CustomExcel.ExcelTemplates']); }
    // End

    public function export($model) { $this->ExcelReport->renderExcel(['className' => "$this->plugin.$model"]); }
}

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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        if (in_array($action, ['export', 'viewVars', 'exportPDF'])) {
            return true;
        }
    }

    public function export($model) { $this->ExcelReport->renderExcel(['className' => "$this->plugin.$model"]); }
    public function viewVars($model) { $this->ExcelReport->viewVars(['className' => "$this->plugin.$model"]); }

    public function exportPDF($model) { 
        $this->ExcelReport->renderExcel(['className' => "$this->plugin.$model", 'format' => 'pdf']); 
    }
}

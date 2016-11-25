<?php
namespace CustomReport\Controller;

use ArrayObject;

use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Event\Event;

use App\Controller\AppController;

class CustomReportsController extends AppController
{
	public function initialize()
	{
        parent::initialize();
        $this->loadComponent('CustomReport.CustomReport');
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $this->Navigation->addCrumb('Custom Report', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

    	$header = __('Custom Report');
    	$header .= ' - ' . $model->getHeader($model->alias);
    	$this->set('contentHeader', $header);
    }

    public function ReportTemplates() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'CustomReport.ReportTemplates']); }
}

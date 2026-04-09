<?php
namespace Configuration\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\ORM\Table;

//POCOR-9257: start - standalone Webhooks controller, no ConfigItemsBehavior dependency
class WebhooksController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function Webhooks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.Webhooks']); //POCOR-9257
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event); //POCOR-9257
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $header = __('Webhooks'); //POCOR-9257
        $header .= ' - ' . $model->getHeader($model->alias); //POCOR-9257
        $this->Navigation->addCrumb('Webhooks', ['plugin' => 'Configuration', 'controller' => 'Webhooks', 'action' => 'Webhooks']); //POCOR-9257
        $this->Navigation->addCrumb($model->getHeader($model->alias)); //POCOR-9257
        $this->set('contentHeader', $header); //POCOR-9257
    }
}
//POCOR-9257: end

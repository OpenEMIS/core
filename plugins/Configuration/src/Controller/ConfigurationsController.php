<?php
namespace Configuration\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;

class ConfigurationsController extends AppController {
    public function initialize()
    {
        parent::initialize();
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $alias = $model->alias;
        $header = __('Configuration') . ' - ' . $model->getHeader($alias);

        $this->Navigation->addCrumb($model->getHeader($alias));

        $this->set('contentHeader', $header);
    }

    public function ConfigProductLists()                       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Configuration.ConfigProductLists']); }
}

<?php
namespace Cache\Controller;

use Cache\Controller\AppController;
use Cake\Event\Event;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;

class CachesController extends AppController
{
    public function initialize()
    {
        parent::initialize();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['clear', 'server']);
    }

    public function clear()
    {
        $this->autoRender = false;
        Cache::clear(false, 'labels');
        Cache::clear(false, '_cake_core_');
        Cache::clear(false, '_cake_model_');
        $Labels = TableRegistry::get('Labels');
        $Labels->storeLabelsInCache();

        return $this->redirect($this->referer());
    }

    public function server()
    {
        $this->autoRender = false;
        echo gethostname();
    }
}

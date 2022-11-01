<?php
namespace Cache\Controller;

use Cache\Controller\AppController;
use Cake\Event\Event;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;

class CachesController extends AppController
{
    public function initialize()
    {
        parent::initialize();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['clear', 'server', 'pull']);
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

    public function pull()
    {
        $this->autoRender = false;
        if (Configure::read('debug')) {
            $output = [];
            exec('git pull', $output);
            pr($output);
        }
    }
}

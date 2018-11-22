<?php
namespace Historial\Model\Behavior;

use ArrayObject;
use Cake\Core\Exception\Exception;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Log\Log;

class HistorialBehavior extends Behavior
{
    protected $_defaultConfig = [
        'historialUrl' => [
            'plugin' => '',
            'controller' => '',
            'action' => ''
        ],
        'model' => '',
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction', 'priority' => 50];
        $events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 50];
        return $events;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $model = $this->_table;
        $HistorialModelTable = TableRegistry::get($this->config('model'));
        $historialQuery = $HistorialModelTable->find();
        $model->dispatchEvent('Historial.index.beforeQuery', [$historialQuery, $HistorialModelTable], $model);

        try {
            $query
                ->union($historialQuery)
                ->order(['start_date' => 'ASC']);
        } catch (Exception $e) {
            Log::write('error', 'Union historial query failed');
            Log::write('error', $e);
        }

        $extra['auto_contain'] = false;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        $historialUrl = $this->config('historialUrl');
        $historialUrl[] = 'add';

        $toolbarButtonsArray['historialAdd']['attr'] = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $toolbarButtonsArray['historialAdd']['type'] = 'button';
        $toolbarButtonsArray['historialAdd']['label'] = '<i class="fa kd-add"></i>';
        $toolbarButtonsArray['historialAdd']['attr']['title'] = __('Historial Data Add');
        $toolbarButtonsArray['historialAdd']['url'] = $historialUrl;
        
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }
}

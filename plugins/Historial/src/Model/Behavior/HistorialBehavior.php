<?php
namespace Historial\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class HistorialBehavior extends Behavior
{
    protected $_defaultConfig = [
        'plugin' => '',
        'controller' => '',
        'action' => ''
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        $historialUrl = [
            'plugin' => $this->config('plugin'),
            'controller' => $this->config('controller'),
            'action' => $this->config('action'),
            'add'
        ];

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

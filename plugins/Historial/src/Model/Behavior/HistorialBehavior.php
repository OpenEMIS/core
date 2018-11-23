<?php
namespace Historial\Model\Behavior;

use ArrayObject;
use Cake\Core\Exception\Exception;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;

class HistorialBehavior extends Behavior
{
    private $_queryUnionResults = [];

    protected $_defaultConfig = [
        'historialUrl' => [
            'plugin' => '',
            'controller' => '',
            'action' => ''
        ],
        'model' => '',
        'allowedController' => ['Directories']
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 50];
        return $events;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        try {
            $model = $this->_table;
            $mainQuery = $model->find();
            $HistorialModelTable = TableRegistry::get($this->config('model'));
            $historialQuery = $HistorialModelTable->find();

            $selectList = new ArrayObject([]);
            $defaultOrder = new ArrayObject([]);

            $model->dispatchEvent('Behavior.Historial.index.beforeQuery', [$mainQuery, $historialQuery, $selectList, $defaultOrder, $extra], $model);

            $mainQuery->union($historialQuery);
            $tempResult = $mainQuery
                ->toArray();

            foreach ($tempResult as $entity) {
                $historial = $entity->is_historial;
                $entityId = $entity->id;

                $this->_queryUnionResults[$historial][$entityId] = $entity;
            }

            if (empty($selectList)) {
                $selectedFields = [
                    $model->aliasField('id'),
                    $model->aliasField('is_historial')
                ];
            } else {
                $selectedFields = $selectList->getArrayCopy();
            }

            $query
                ->select($selectedFields, true)
                ->from([$model->alias() => $mainQuery])
                ->where(['1 = 1'], [], true);

            $request = $this->_table->request;
            if (is_null($request->query('sort')) && !empty($defaultOrder)) {
                // default display sort
                $order = $defaultOrder->getArrayCopy();
                $query->order($order);
            }
        } catch (Exception $e) {
            Log::write('error', 'Union historial query failed');
            Log::write('error', $e);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $controller = $this->_table->controller->name;

        if (in_array($controller, $this->config('allowedController'))) {
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

    public function getFieldEntity($historial, $entityId, $field)
    {
        return $this->_queryUnionResults[$historial][$entityId]->$field;
    }
}

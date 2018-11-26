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
use Cake\Utility\Inflector;

class HistorialBehavior extends Behavior
{
    private $_queryUnionResults = [];

    protected $_defaultConfig = [
        'historialUrl' => [
            'plugin' => '',
            'controller' => '',
            'action' => ''
        ],
        'originUrl' => [
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
        $events['ControllerAction.Model.delete.beforeAction'] = 'deleteBeforeAction';
        $events['ControllerAction.Model.view.beforeAction'] = 'viewBeforeAction';
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
        $events['ControllerAction.Model.addEdit.beforeAction'] = 'addEditBeforeAction';
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 50];
        return $events;
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->updateBreadcrumbAndPageTitle();
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->updateBackButton($extra);
        $extra['redirect'] = $this->getOriginUrl();
    }

    public function deleteBeforeAction(Event $event, ArrayObject $extra)
    {
        $extra['redirect'] = $this->getOriginUrl();
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->updateBreadcrumbAndPageTitle();
        $this->updateBackButton($extra);
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

    private function updateBreadcrumbAndPageTitle()
    {
        $model = $this->_table;

        // breadcrumb update
        $NavigationComponent = $model->controller->Navigation;
        $currentCrumb = Inflector::humanize(Inflector::underscore($model->alias()));
        $newCrumb = Inflector::humanize(Inflector::underscore(str_replace('Historical', '', $model->alias())));
        $NavigationComponent->substituteCrumb($currentCrumb, $newCrumb);

        // page title update
        $session = $model->request->session();
        $userName = $this->getStaffName();

        if (!is_null($userName)) {
            $model->controller->set('contentHeader', $userName . ' - ' . __($newCrumb));
        }
    }

    private function updateBackButton(ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarButtonsArray['back']['url'] = $this->getOriginUrl();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    private function getOriginUrl()
    {
        $originUrl = $this->config('originUrl');

        $model = $this->_table;
        if ($model->controller->name === 'Directories') {
            $originUrl['plugin'] = 'Directory';
            $originUrl['controller'] = $model->controller->name;
        } elseif ($model->controller->name === 'Institutions') {
            $originUrl['plugin'] = 'Institution';
            $originUrl['controller'] = $model->controller->name;
        } elseif ($model->controller->name === 'Profiles') {
            // no logic
        }
        return $originUrl;
    }

    private function getStaffName()
    {
        $model = $this->_table;
        $session = $model->request->session();

        if ($model->controller->name === 'Directories') {
            if ($session->check('Directory.Directories.name')) {
                return $session->read('Directory.Directories.name');
            }
        } elseif ($model->controller->name === 'Institutions') {
            if ($session->check('Staff.Staff.name')) {
                return $session->read('Staff.Staff.name');
            }
        } elseif ($model->controller->name === 'Profiles') {
            // no logic
        }
        return null;
    }
}

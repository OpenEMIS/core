<?php
namespace Historical\Model\Behavior;

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

class HistoricalBehavior extends Behavior
{
    private $_queryUnionResults = [];

    protected $_defaultConfig = [
        'historicalUrl' => [
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = 'beforeAction';
        $events['ControllerAction.Model.delete.beforeAction'] = 'deleteBeforeAction';
        $events['ControllerAction.Model.view.beforeAction'] = 'viewBeforeAction';
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
        $events['ControllerAction.Model.addEdit.beforeAction'] = 'addEditBeforeAction';
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 50];
        $events['Excel.Historical.beforeQuery'] = 'indexBeforeQuery';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $controller = $this->_table->controller->name;

        // To only show the historical edit/remove button if the current plugin is the allowedController
        if (!in_array($controller, $this->config('allowedController')) && $this->_table->registryAlias() == $this->config('model')) {
            $this->_table->toggle('edit', false);
            $this->_table->toggle('remove', false);
        }

        // pr($this->config('originUrl'));die;
    }

    // logic should only trigger if the current model is historical behavior
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->isHistorialModel()) {
            $this->updateBreadcrumbAndPageTitle();
        }
    }

    // logic should only trigger if the current model is historical behavior
    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->isHistorialModel()) {
            $this->updateBackButton($extra);
            $extra['redirect'] = $this->getOriginUrl();
        }
    }

    // logic should only trigger if the current model is historical behavior
    public function deleteBeforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->isHistorialModel()) {
            $extra['redirect'] = $this->getOriginUrl();
        }
    }

    // logic should only trigger if the current model is historical behavior
    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->isHistorialModel()) {
            $this->updateBreadcrumbAndPageTitle();
            $this->updateBackButton($extra);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        try {
            $model = $this->_table;
            $mainQuery = $model->find();
            $HistoricalModelTable = TableRegistry::get($this->config('model'));
            $historicalQuery = $HistoricalModelTable->find();

            $selectList = new ArrayObject([]);
            $defaultOrder = new ArrayObject([]);

            $model->dispatchEvent('Behavior.Historical.index.beforeQuery', [$mainQuery, $historicalQuery, $selectList, $defaultOrder, $extra], $model);

            $mainQuery->union($historicalQuery);
            $tempResult = $mainQuery
                ->toArray();

            foreach ($tempResult as $entity) {
                $historical = $entity->is_historical;
                $entityId = $entity->id;

                $this->_queryUnionResults[$historical][$entityId] = $entity;
            }

            if (empty($selectList)) {
                $selectedFields = [
                    $model->aliasField('id'),
                    $model->aliasField('is_historical')
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
            Log::write('error', 'Union historical query failed');
            Log::write('error', $e);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $controller = $this->_table->controller->name;

        if (in_array($controller, $this->config('allowedController'))) {
            $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

            $historicalUrl = $this->config('historicalUrl');
            $historicalUrl[] = 'add';

            $toolbarButtonsArray['HistoricalAdd']['attr'] = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ];
            $toolbarButtonsArray['HistoricalAdd']['type'] = 'button';
            $toolbarButtonsArray['HistoricalAdd']['label'] = '<i class="fa kd-add"></i>';
            $toolbarButtonsArray['HistoricalAdd']['attr']['title'] = __('Add Historical Data');
            $toolbarButtonsArray['HistoricalAdd']['url'] = $historicalUrl;

            $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        }
    }

    public function getFieldEntity($historical, $entityId, $field)
    {
        return $this->_queryUnionResults[$historical][$entityId]->$field;
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
        } elseif ($model->controller->name === 'Staff') {
            $originUrl['plugin'] = 'Staff';
            $originUrl['controller'] = $model->controller->name;
        } elseif ($model->controller->name === 'Profiles') {
            $originUrl['plugin'] = 'Profile';
            $originUrl['controller'] = $model->controller->name;
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
            return $model->Auth->user('name');
        }
        return null;
    }

    private function isHistorialModel()
    {
        return $this->_table->registryAlias() === $this->config('model');   
    }
}
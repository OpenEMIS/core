<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;
use Cake\Core\Configure;

class ConfigItemsBehavior extends Behavior
{
    private $model;
    private $selectedType;

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction'];
        if ($this->isCAv4()) {
            $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
        }
        return $events;
    }

    public function initialize(array $config)
    {
        $this->model = $this->_table;
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function buildSystemConfigFilters()
    {

        $toolbarElements = [
            ['name' => 'Configuration.controls', 'data' => [], 'options' => []]
        ];
        $this->model->controller->set('toolbarElements', $toolbarElements);
        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');

        $typeList = $ConfigItem
            ->find('list', [
                'keyField' => 'type',
                'valueField' => 'type'
            ])
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1])
            ->toArray();
            //echo"<pre>";print_r($typeList); die;
             $typeLists = $ConfigItem
            ->find('all', [
                // 'fields' => 'label','type'

            ])
            ->order('label')
            ->where([$ConfigItem->aliasField('visible') => 1,'type' => 'Coordinates'])
            ->toArray();
            //echo"<pre>";print_r($typeLists); die;
        $typeOptions = array_keys($typeList);
        foreach ($typeOptions as $key => $value) {

            $value = $value != 'Authentication' ? $value : 'Sso';
            // echo"<pre>";print_r($value); die;
            if (in_array($value, (array) Configure::read('School.excludedPlugins'))) {
                unset($typeOptions[$key]);
            }
        }
        $selectedType = $this->model->queryString('type', $typeOptions);

        $this->selectedType = $selectedType;
        $this->model->request->query['type_value'] = $typeOptions[$selectedType];
        $this->model->advancedSelectOptions($typeOptions, $selectedType);
        $this->model->controller->set('typeOptions', $typeOptions);
        $controlElement = $toolbarElements[0];
        $controlElement['data'] = ['typeOptions' => $typeOptions];
        $controlElement['order'] = 1;

        return $controlElement;
    }

    public function checkController()
    {
        //print_r('hi'); die;
        $typeValue = $this->model->request->query['type_value'];

        $typeValue = Inflector::camelize($typeValue, ' ');
        $action = '';
        if ($this->isCAv4()) {
            $url = $this->model->url('index');
            $action = $this->model->request->params['action'];
        } else {
            $url = $this->model->controller->ControllerAction->url('index');
            $action = $this->model->action;
        }

        // echo '<pre>';
        // print_r($url);
        // print_r($action);
        // echo '<br/>';
        // echo $typeValue;

        // die;

        // Start POCOR-7507
        if($typeValue == 'ExternalDataSource-Identity'){
            $typeValue = 'ExternalDataSourceIdentity';
        }
        //POCOR-7531 start
        if($typeValue == 'ExternalDataSource-Exams'){
            $typeValue = 'ExternalDataSourceExams';
        }
        //POCOR-7531 start
         // End POCOR-7507

        if (method_exists($this->model->controller, $typeValue) && $action != $typeValue) {
            $url['action'] = $typeValue;
            $url['type_value'] = $typeValue;  // POCOR-7507
            $this->model->controller->redirect($url);
        } elseif ($action != $typeValue && $action != 'index') {
            $this->model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'index',
                'type' => $this->selectedType]);
        }
    }
    public function beforeAction(Event $event, $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Configurations', 'action' => 'index'];
    }

    public function indexBeforeAction(Event $event, $extra)
    {
        if ($this->isCAv4()) {
            $extra['elements']['controls'] = $this->buildSystemConfigFilters();
            $this->checkController();
        } else {
            $this->buildSystemConfigFilters();
            $this->checkController();
        }
    }
}

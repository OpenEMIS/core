<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\Core\Configure;

class AuthenticationBehavior extends Behavior
{
    private $alias;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->model = $this->_table;
    }

    public function implementedEvents()
    {
        $events = [
            'ControllerAction.Model.afterAction' => 'afterAction'
        ];
        return $events;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $authenticationType = $event->subject()->request->query('authentication_type');
        $type = $event->subject()->request->query('type');
        //echo"<pre>"; print_r($this->_table->action); die; 
        $typeValue = 'Authentication';
        $model = $this->_table;
        $alias = str_replace('Config', '', $model->alias());
        $getData =  $event->data[0];
        $arrayData = (array)$getData['entity'];
        $unset_val = array_shift($arrayData);
        $fieldType = $unset_val['code'];
        if ($authenticationType && $authenticationType != $alias) {
            return $model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'Auth'.$authenticationType,
                'authentication_type' => $authenticationType,
                'type_value' => 'Authentication',
                'type' => $type, 
                'field_type' => $fieldType
            ]);
        } elseif ($model->table() != 'config_items' && !$authenticationType) {
            return $model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'Authentication',
                'view',
                'type_value' => 'Authentication',
                'type' => $type, 
                'field_type' => $fieldType
            ]);
        }
       
        /*if($this->_table->action == 'view' || $this->_table->action == 'edit'){
            $authenticationType = $event->subject()->request->query('authentication_type');
            $type = $event->subject()->request->query('type');
            //echo"<pre>"; print_r($this->_table->action); die; 
            $typeValue = 'Authentication';
            $model = $this->_table;
            $alias = str_replace('Config', '', $model->alias());
            $getData =  $event->data[0];
            $arrayData = (array)$getData['entity'];
            $unset_val = array_shift($arrayData);
            $fieldType = $unset_val['code'];
            return $model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'Authentication',
                'view',
                'type_value' => 'Authentication',
                'type' => $type//, 
                //'field_type' => $fieldType
            ]);
        }*/
    }

    public function buildSystemConfigFilters($action = null)
    {
        $toolbarElements = [
            ['name' => 'Configuration.idp_controls', 'data' => [], 'options' => []]
        ];
        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
        $typeList = $ConfigItem
            ->find('list', [
                'keyField' => 'type',
                'valueField' => 'type'
            ])
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1])
            ->toArray();
        $typeOptions = array_keys($typeList);
        foreach ($typeOptions as $key => $value) {
            $value = $value != 'Authentication' ? $value : 'Sso';
            if (in_array($value, (array) Configure::read('School.excludedPlugins'))) {
                unset($typeOptions[$key]);
            }
        }
        $selectedType = $this->model->queryString('type', $typeOptions);
        $this->selectedType = $selectedType;
        $this->model->request->query['type_value'] = $typeOptions[$selectedType];
        $this->model->advancedSelectOptions($typeOptions, $selectedType);
        $this->model->controller->set('typeOptions', $typeOptions);
        $authenticationTypeOptions = [];
        //POCOR-7156 Starts add condition $action == 'view' || $action == 'edit'
        if($action == 'view' || $action == 'edit'){
        //echo"<pre>"; print_r($this->model->request); die;
            $authenticationTypeOptions = [0 => 'Local', 'SystemAuthentications' => __('Other Identity Providers')];
            foreach ($authenticationTypeOptions as &$options) {
                $options = __($options);
            }
            $authenticationType = $this->model->queryString('authentication_type', $authenticationTypeOptions);
            $this->model->advancedSelectOptions($authenticationTypeOptions, $authenticationType);
            $authenticationTypeOptions = array_values($authenticationTypeOptions);
        }//POCOR-7156 Ends
        
        $this->model->controller->set('authenticationTypeOptions', $authenticationTypeOptions);
        $controlElement = $toolbarElements[0];
        $controlElement['data'] = ['typeOptions' => $typeOptions];
        $controlElement['order'] = 1;

        return $controlElement;
    }

    public function checkController()
    {
        $typeValue = $this->model->request->query['type_value'];
        $typeValue = Inflector::camelize($typeValue, ' ');
        $url = $this->model->url('index');
        unset($url['authentication_type']);
        $action = $this->model->request->params['action'];
        if (method_exists($this->model->controller, $typeValue) && $action != $typeValue && $typeValue != 'Authentication') {
            $url['action'] = $typeValue;
            $this->model->controller->redirect($url);
        } elseif ($action != $typeValue && $action != 'index' && $typeValue != 'Authentication') {
            $this->model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'index',
                'type' => $this->selectedType]);
        }
    }
}

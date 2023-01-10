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

class ExternalDataSourceBehavior extends Behavior
{

    private $alias;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->alias = $this->_table->alias();
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'ControllerAction.Model.beforeAction' => 'beforeAction',
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'ControllerAction.Model.edit.afterSave'     => 'editAfterSave',
            'ControllerAction.Model.afterAction'    => 'afterAction',
            'ControllerAction.Model.edit.beforeAction'  => 'editBeforeAction',
            'ControllerAction.Model.view.beforeAction'  => 'viewBeforeAction',
            'ControllerAction.Model.index.beforeAction'     => ['callable' => 'indexBeforeAction', 'priority' => 100],
            'ControllerAction.Model.edit.beforePatch'   => 'editBeforePatch'


        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($this->_table->action == 'view') {
            $key = $this->_table->id;
            if (!empty($key)) {
                $configItem = $this->_table->get($key);
                if ($configItem->type == 'External Data Source' && $configItem->code == 'external_data_source_type') {
                    if (isset($toolbarButtons['back'])) {
                        unset($toolbarButtons['back']);
                    }
                }
            }
        }
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['list'])) {
            unset($extra['toolbarButtons']['list']);
        }
    }

    public function indexBeforeAction(Event $event)
    {
        if ($this->_table->request->query['type_value'] == 'External Data Source') {
            $urlParams = $this->_table->url('view');
            $externalDataSourceId = $this->_table->find()
                ->where([
                    $this->_table->aliasField('type') => 'External Data Source',
                    $this->_table->aliasField('code') => 'external_data_source_type'])
                ->first()
                ->id;
            $urlParams[0] = $externalDataSourceId;
            if (isset($this->_table->request->pass[0]) && $this->_table->request->pass[0] == $externalDataSourceId) {
            } else {
                $this->_table->controller->redirect($urlParams);
            }
        }
    }

    public function beforeAction(Event $event)
    {
        if ($this->_table->action == 'view' || $this->_table->action == 'edit') {
            $key = $this->_table->id;
            if (!empty($key)) {
                $configItem = $this->_table->get($key);
                if ($configItem->type == 'External Data Source' && $configItem->code == 'external_data_source_type') {
                    if (isset($this->_table->request->data[$this->alias]['value']) && !empty($this->_table->request->data[$this->alias]['value'])) {
                        $value = $this->_table->request->data[$this->alias]['value'];
                    } else {
                        $value = $configItem->value;
                        $this->_table->request->data[$this->alias]['value'] = $value;
                    }
                    if ($value != 'None') {
                        $this->_table->field('custom_data_source', ['type' => 'external_data_source_type', 'valueClass' => 'table-full-width', 'visible' => [ 'edit' => true, 'view' => true ]]);
                    }
                }
            }
        }
    }

    public function afterAction(Event $event)
    {
        if ($this->_table->action == 'view' || $this->_table->action == 'edit') {
            $key = $this->_table->id;
            if (!empty($key)) {
                $configItem = $this->_table->get($key);
                if ($configItem->type == 'External Data Source' && $configItem->code == 'external_data_source_type') {
                    $this->_table->field('default_value', ['visible' => false]);
                    $value = $this->_table->request->data[$this->alias]['value'];
                    if ($value != 'None') {
                        $this->_table->setFieldOrder(['type', 'label', 'value', 'custom_data_source']);
                    }
                }
            } else {
                if ($this->_table->action == 'view') {
                    $urlParams = $this->_table->url('index');
                    $this->_table->controller->redirect($urlParams);
                }
            }
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['back'])) {
            unset($extra['toolbarButtons']['back']);
        }
        if (isset($this->_table->request->query['type_value']) && $this->_table->request->query['type_value'] == 'External Data Source') {
            $this->_table->buildSystemConfigFilters();
        }
    }

    protected function processAuthentication(&$attribute, $authenticationType)
    {
        $ExternalDataSourceAttributesTable = TableRegistry::get('ExternalDataSourceAttributes');
        $attributesArray = $ExternalDataSourceAttributesTable->find()->where([$ExternalDataSourceAttributesTable->aliasField('external_data_source_type') => $authenticationType])->toArray();
        $attributeFieldsArray = $this->_table->array_column($attributesArray, 'attribute_field');
        foreach ($attribute as $key => $values) {
            $attributeValue = '';
            if (array_search($key, $attributeFieldsArray) !== false) {
                $attributeValue = $attributesArray[array_search($key, $attributeFieldsArray)]['value'];
            }
            if (method_exists($this, lcfirst(Inflector::camelize($authenticationType, ' ')).'ModifyValue')) {
                $method = lcfirst(Inflector::camelize($authenticationType, ' ')).'ModifyValue';
                $result = $this->$method($key, $attributeValue);
                if ($result !== false) {
                    $attributeValue = $result;
                }
            }
            $attribute[$key]['value'] = $attributeValue;
        }
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $configItem = $data[$this->alias];
        if ($configItem['type'] == 'External Data Source') {
            $configItem['value'] = lcfirst(Inflector::camelize($configItem['value'], ' '));

            $methodName = $configItem['value'].'Validation';
            if (method_exists($this, $methodName) && !$this->$methodName($data['ExternalDataSourceTypeAttributes'])) {
                $this->_table->Alert->error('ExternalDataSource.emptyFields', ['reset' => true]);
                ;
                $entity->errors('error', ['There are invalid attributes']);
            }
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $ExternalDataSourceAttributesTable = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
        if ($data[$this->alias]['value'] != 'None' && $data[$this->alias]['type'] == 'External Data Source' && empty($entity->errors())) {
            $externalDataSourceType = $data[$this->alias]['value'];
            $ExternalDataSourceAttributesTable->deleteAll(
                ['external_data_source_type' => $externalDataSourceType]
            );

            foreach ($data['ExternalDataSourceTypeAttributes'] as $key => $value) {
                if (strpos($value['name'], 'URI')) {
                    $value['value'] = rtrim($value['value'], '/');
                }
                $entityData = [
                    'external_data_source_type' => $externalDataSourceType,
                    'attribute_field' => $key,
                    'attribute_name' => $value['name'],
                    'value' => trim($value['value'])
                ];
                $entity = $ExternalDataSourceAttributesTable->newEntity($entityData);
                $ExternalDataSourceAttributesTable->save($entity);
            }

            if (method_exists($this, lcfirst(Inflector::camelize($externalDataSourceType, ' ')).'AfterSave')) {
                $method = lcfirst(Inflector::camelize($externalDataSourceType, ' ')).'AfterSave';
                $this->$method($data['ExternalDataSourceTypeAttributes']);
            }
        }
    }

    public function openEMISIdentityExternalSource(&$attribute)
    {
        $attribute['token_uri'] = ['label' => 'Token URI', 'type' => 'text'];
        $attribute['refresh_token'] = ['label' => 'Refresh Token', 'type' => 'textarea'];
        $attribute['client_id'] = ['label' => 'Client ID', 'type' => 'text'];
        $attribute['client_secret'] = ['label' => 'Client Secret', 'type' => 'text'];
        // $attribute['redirect_uri'] = ['label' => 'Redirect URI', 'type' => 'text', 'readonly' => true];
        // $attribute['hd'] = ['label' => 'Hosted Domain', 'type' => 'text', 'required' => false];
        $attribute['record_uri'] = ['label' => 'Record URI', 'type' => 'text'];
    }

    public function openEMISIdentityValidation($attributes)
    {
        $attribute = [];
        $this->openEMISIdentityExternalSource($attribute);
        foreach ($attribute as $key => $values) {
            if (!isset($values['required'])) {
                if (empty($attributes[$key]['value'])) {
                    return false;
                }
            }
        }
        return true;
    }

    public function onGetExternalDataSourceTypeElement(Event $event, $action, $entity, $attr, $options = [])
    {
        switch ($action) {
            case "view":
                $externalDataSourceType = $this->_table->request->data[$this->alias]['value'];
                $attribute = [];
                $methodName = lcfirst(Inflector::camelize($externalDataSourceType, ' ')).'ExternalSource';
                if (method_exists($this, $methodName)) {
                    $this->$methodName($attribute);
                    $this->processAuthentication($attribute, $externalDataSourceType);
                }

                $tableHeaders = [__('Attribute Name'), __('Value')];
                $tableCells = [];
                foreach ($attribute as $value) {
                    $row = [];
                    $row[] = $value['label'];
                    $row[] = $value['value'];
                    $tableCells[] = $row;
                }
                $attr['tableHeaders'] = $tableHeaders;
                $attr['tableCells'] = $tableCells;
                break;

            case "edit":
                $externalDataSourceType = $this->_table->request->data[$this->alias]['value'];
                $attribute = [];
                $methodName = lcfirst(Inflector::camelize($externalDataSourceType, ' ')).'ExternalSource';
                if (method_exists($this, $methodName)) {
                    $this->$methodName($attribute);
                    $this->processAuthentication($attribute, $externalDataSourceType);
                }

                $attr = $attribute;
                break;
        }
        return $event->subject()->renderElement('Configurations/external_data_source', ['attr' => $attr]);
    }
}

<?php

namespace Configuration\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\Core\Configure;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Log\Log;
use Google\Service\Contentwarehouse\EventIdMessage;
use Cake\Event\EventInterface;

class ConfigExternalDataWebhookTable extends ControllerActionTable
{
    const string OPEN_EMIS_EXAMS = 'OpenEMIS Exams';
    const string OPEN_EMIS_CORE = 'OpenEMIS Core';
    const string EXTERNAL_DATA_WEBHOOK_TYPE = 'External Data Source - Webhook';
    public $id;
    public $authenticationType;

    public function initialize(array $config): void
    {
        $this->setTable('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $requestData = $this->request->getData();
        $alias = $this->getAlias();
        $data = $requestData[$alias];
        $source = $data['label'];
        if ($source == self::OPEN_EMIS_EXAMS) {
            return $validator
                ->requirePresence('api_url')
//                ->requirePresence('api_key')
                ->requirePresence('username')
                ->requirePresence('password')
                ->notEmptyString('api_url', __('Please enter the API URL'))
//                ->notEmptyString('api_key', __('Please enter the API Key'))
                ->notEmptyString('username', __('Please enter the Username'))
                ->notEmptyString('password', __('Please enter the Password'));
        }
        if ($source == self::OPEN_EMIS_CORE) {
            return $validator
                ->requirePresence('api_url')
                ->requirePresence('api_key')
                ->requirePresence('username')
                ->requirePresence('password')
                ->notEmptyString('api_url', __('Please enter the API URL'))
                ->notEmptyString('api_key', __('Please enter the API Key'))
                ->notEmptyString('username', __('Please enter the Username'))
                ->notEmptyString('password', __('Please enter the Password'));
        }
        return $validator;

    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        if ($this->action == 'index') {
            $this->field('visible', ['visible' => false]);
            $this->field('editable', ['visible' => false]);
            $this->field('field_type', ['visible' => false]);
            $this->field('option_type', ['visible' => false]);
            $this->field('code', ['visible' => false]);
            $this->field('name', ['visible' => false]);
            $this->field('value', ['visible' => true]);
            $this->field('value_selection', ['visible' => false]);
            $this->field('default_value', ['visible' => false]);
            $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
            $this->field('label', ['visible' => ['index' => true, 'view' => true, 'edit' => true], 'type' => 'readonly']);
            $this->setFieldOrder([
                'label', 'value'
            ]);
        }
        if ($this->action != 'index') {

            $this->field('visible', ['visible' => false]);
            $this->field('editable', ['visible' => false]);
            $this->field('field_type', ['visible' => false]);
            $this->field('option_type', ['visible' => false]);
            $this->field('code', ['visible' => false]);
            $this->field('name', ['visible' => ['index' => true]]);
            $this->field('default_value', ['visible' => false]);
            $this->field('value_selection', ['visible' => false]);

            $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
            $this->field('label', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);

            if ($this->action == 'view') {
                $extra['elements']['controls'] = $this->buildSystemConfigFilters();
                $this->checkController();
            }
//            dd($this->getFields());
        }

        $is_manual_exist = $this->getManualUrl('Administration', 'External Alert Service - SMS', 'System Configurations');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }

    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('value', ['visible' => true]);
        $source = $entity->name;
        switch ($source) {
            case self::OPEN_EMIS_EXAMS:
                $this->field('attributes', ['type' => 'custom_external_source']);
                break;

            case self::OPEN_EMIS_CORE:
                $this->field('attributes', ['type' => 'custom_external_source']);
                break;

            default:
                break;
        }
    }

    public function onGetCustomExternalSourceElement(EventInterface $event, $action, Entity $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Attribute Name'), __('Value')];
        $tableCells = [];
        $ExternalDataSourceAttributes = self::getDynamicTableInstance('Configuration.ExternalDataSourceAttributes');
        $source = $entity->id . ':'. $entity->name;

        $attributes = $ExternalDataSourceAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->where([
                $ExternalDataSourceAttributes->aliasField('external_data_source_type') => $source
            ])
            ->orderAsc('attribute_field')
            ->toArray();
        $visibleAttributes = [];
        switch ($source) {
            case $entity->id . ':' . self::OPEN_EMIS_EXAMS:
                $visibleAttributes = [
                    'api_url',
                    'username',
                    'password'];
                break;
            case $entity->id . ':' . self::OPEN_EMIS_CORE:
                $visibleAttributes = ['api_url',
                    'username', 'password', 'api_key'];
                break;

            default:
                break;
        }
        // Filter attributes using array_intersect_key
        $attributes = array_intersect_key(
            $attributes,
            array_flip($visibleAttributes)
        );

        if ($action == 'view') {
            if (isset($attributes['password'])) {
                $attributes['password'] = '*****';
            }
            if (isset($attributes['api_key'])) {
                $attributes['api_key'] = '*****';
            }

            foreach ($attributes as $key => $obj) {
                $rowData = [];
                $rowData[] = __(Inflector::humanize($key));
                $rowData[] = nl2br($obj);
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->getSubject()->renderElement('Configuration.external_alert_service_sms', ['attr' => $attr]);

    }

    public function onUpdateFieldValue(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (in_array($action, ['edit'])) {
            $optionTable = self::getDynamicTableInstance('Configuration.ConfigItemOptions');
            $options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                ->where([
                    'ConfigItemOptions.option_type' => 'completeness',
                    'ConfigItemOptions.visible' => 1
                ])
                ->toArray();
            $attr['options'] = $options;
            $attr['onChangeReload'] = true;
            $this->setExternalAttributes($attr['entity']);
        }


        return $attr;
    }
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $buttons = self::fixActionButtons($buttons);
//        dd($buttons);

        return $buttons;
    }

    private static function fixActionButtons($links)
    {
        foreach (['view', 'edit'] as $action) {
            if (!isset($links[$action]['url'])) {
                continue;
            }

            $url = $links[$action]['url'];

            // Extract encoded IDs (keys that are numeric and value starts with 'eyJ')
            $encodedKeys = [];
            foreach ($url as $k => $v) {
                if (is_numeric($k) && is_string($v) && str_starts_with($v, 'eyJ')) {
                    $encodedKeys[] = $k;
                }
            }

            // If there are two encoded IDs: remove first, keep second
            if (count($encodedKeys) === 2) {
                $first  = $encodedKeys[0];
                $second = $encodedKeys[1];

                // Remove the first encoded id
                unset($url[$first]);

                // Move the second encoded id to index 1
                $url[1] = $url[$second];
                unset($url[$second]);
            }

            // Save back
            $links[$action]['url'] = $url;
        }
        return $links;
    }
    public function editBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOption, ArrayObject $extra): void
    {

        $alias = $this->getAlias();
        $data = $requestData[$alias];
        $source = $entity['name'];

        if ($source == 'Twilio') {
            $patchOption['validate'] = true;
            return;
        }

    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $patchOption, ArrayObject $extra)
    {
        $errors = $entity->getErrors();
        $source = $entity->name;
//        $this->field('sms_account_sid', ['type' => 'string', 'required' => 'required']);
//        $this->field('sms_auth_token', ['type' => 'password', 'required' => 'required']);
//        $this->field('sms_number', ['type' => 'string', 'required' => 'required']);

        if(empty($entity->password)){
            $entity->sms_auth_token = $entity->getOriginal('sms_auth_token');
        }
        if (!empty($errors)) {
            $errorMessage = 'Please enter the required details.';
            //POCOR-7981:starts
            $error_prefix = __CLASS__ . ':' . __FILE__ . __FUNCTION__ . __LINE__;
            $this->log($error_prefix);
            $this->log($errorMessage);
            $this->log(print_r($errors, true));
            //POCOR-7981:ends
            $this->Alert->error('general.externalSourceDataErr', ['reset' => true]);
        } else {//POCOR-6930 Ends
            $this->updateAttributes($entity);
        }
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        $source = $entity->name;
        $this->field('value', ['visible' => true, 'entity' => $entity]);
        $this->field('value_selection', ['visible' => false]);


        $this->field('first_name_mapping', ['type' => 'hidden']);
        $this->field('middle_name_mapping', ['type' => 'hidden']);
        $this->field('third_name_mapping', ['type' => 'hidden']);
        $this->field('last_name_mapping', ['type' => 'hidden']);
        $this->field('date_of_birth_mapping', ['type' => 'hidden']);
        $this->field('external_reference_mapping', ['type' => 'hidden']);
        $this->field('gender_mapping', ['type' => 'hidden']);
        $this->field('identity_type_mapping', ['type' => 'hidden']);
        $this->field('identity_number_mapping', ['type' => 'hidden']);
        $this->field('nationality_mapping', ['type' => 'hidden']);
        $this->field('address_mapping', ['type' => 'hidden']);
        $this->field('postal_mapping', ['type' => 'hidden']);
        $this->field('user_endpoint_uri', ['type' => 'hidden']);
        switch ($source) {
            case self::OPEN_EMIS_EXAMS:
                $this->field('api_url', ['type' => 'string', 'required' => 'required']);
                $this->field('username', ['type' => 'string', 'required' => 'required']);
                $this->field('password', ['type' => 'string', 'required' => 'required']);
//                $this->field('api_key', ['type' => 'string', 'required' => 'required']);
                break;

            case self::OPEN_EMIS_CORE:
                $this->field('api_url', ['type' => 'string', 'required' => 'required']);
                $this->field('username', ['type' => 'string', 'required' => 'required']);
                $this->field('password', ['type' => 'string', 'required' => 'required']);
                $this->field('api_key', ['type' => 'string', 'required' => 'required']);
                break;

            default:
                $this->field('api_url', ['type' => 'hidden']);
                $this->field('username', ['type' => 'hidden']);
                $this->field('password', ['type' => 'hidden']);
                $this->field('api_key', ['type' => 'hidden']);
                break;
        }
    }


    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
            ->select(
                [$this->aliasField('id'),
                    $this->aliasField('label'),
                    $this->aliasField('value')]
            )->where([
                $this->aliasField('type') => self::EXTERNAL_DATA_WEBHOOK_TYPE
            ]);
    }

    public function onGetValue(EventInterface $event, Entity $entity)
    {
        $valueField = 'value';
//        return 'Disabled';
        if ($entity->{$valueField} == 0) {
            $value = __('Disabled');
        } else {
            $value = __('Enabled');
        }

        return $value;
    }
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'value') {
            return __('Status');
        } elseif ($field == 'label') {
            return __('Source');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-7981:End

    public function onGetLabel(EventInterface $event, Entity $entity)
    {
        return __($entity->label);
    }


    /**
     * @param $entity
     * @return void
     */

    public function setExternalAttributes($entity)
    {
        $id = $entity->id;
        $source = $id . ':' . $entity->name;
//        dd($source);

        if (!empty($id)) {
            $ExternalDataSourceAttributes = self::getDynamicTableInstance('Configuration.ExternalDataSourceAttributes'); // POCOR-8849
            $attributes = $ExternalDataSourceAttributes
                ->find('list', [
                    'keyField' => 'attribute_field',
                    'valueField' => 'value'
                ])
                ->where([
                    $ExternalDataSourceAttributes->aliasField('external_data_source_type') => $source
                ])
                ->toArray();

            foreach ($attributes as $key => $value) {
                $entity->{$key} = $value; // POCOR-8849
            }

        }
    }


    /** // POCOR-8849
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
            Log::info('Error: ' . $e->getMessage());
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    // POCOR-7981 END

    /**
     * @param mixed $source
     * @param Entity $entity
     * @return void
     */
    private function updateAttributes(Entity $entity): void
    {
        $source = $entity->id . ':' . $entity->name;
        $ExternalDataSourceAttributes = self::getDynamicTableInstance('Configuration.ExternalDataSourceAttributes'); // POCOR-8849
        $existingRecords = $ExternalDataSourceAttributes->find('list', [
            'keyField' => 'attribute_field',
            'valueField' => 'value'
        ])->where(['external_data_source_type' => $source])
            ->toArray();

        $fields = [
            'api_url', 'username', 'password', 'api_key'
        ];

        foreach ($fields as $field) {
            if ($entity->has($field)) {
                $newValue = $entity->{$field};
                $currentValue = $existingRecords[$field] ?? null;

                // Skip update if password or api_key is empty in entity but present in DB
                if (in_array($field, ['password', 'api_key']) && empty($newValue) && !empty($currentValue)) {
                    continue;
                }

                if ($newValue !== $currentValue) {
                    $data = [
                        'external_data_source_type' => $source,
                        'attribute_field' => $field,
                        'attribute_name' => $field,
                        'value' => $newValue
                    ];

                    $existingEntity = $ExternalDataSourceAttributes->find()->where([
                        'external_data_source_type' => $source,
                        'attribute_field' => $field
                    ])->first();

                    if ($existingEntity) {
                        $ExternalDataSourceAttributes->patchEntity($existingEntity, $data);
                        $ExternalDataSourceAttributes->save($existingEntity);
                    } else {
                        $newEntity = $ExternalDataSourceAttributes->newEntity($data);
                        $ExternalDataSourceAttributes->save($newEntity);
                    }
                }
            }
        }


    }
}

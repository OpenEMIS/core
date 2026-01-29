<?php

namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\ORM\Table;
use Cake\Log\Log;
use function Symfony\Component\String\b;

// POCOR-8286

class ConfigPrintingServicePdfTable extends ControllerActionTable
{
    public $id;
    public $authenticationType;
    private $options = [];
    CONST PRINTER_MPDF = 1;
    CONST PRINTER_LIBREOFFICE = 2;
    CONST PRINTER_EXTERNAL = 3;
    public function initialize(array $config): void
    {
        $this->setTable('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->toggle('remove', false);
        $optionTable = self::getDynamicTableInstance('Configuration.ConfigItemOptions');

        $this->options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
            ->where([
                'ConfigItemOptions.option_type' => 'pdf_printer_type',
                'ConfigItemOptions.visible' => 1
            ])
            ->toArray();
    }

    public function validationDefault(Validator $validator): Validator
    {

        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $requestData = $this->request->getData();
        $alias = $this->getAlias();
        $data = $requestData[$alias];
        $source = $data['value'];
        if ($source == self::PRINTER_EXTERNAL) {
            return $validator
                ->requirePresence('username')
                ->requirePresence('password')
                ->requirePresence('api_url')
                ->notEmptyString('username', __('Please enter UserName'))
                ->notEmptyString('api_url', __('Please enter API Url'))
                ->notEmptyString('password', __('Please enter Password'));
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
            $this->field('label', ['visible' => ['view' => false, 'edit' => false], 'type' => 'readonly']);

            if ($this->action == 'view') {
                $extra['elements']['controls'] = $this->buildSystemConfigFilters();
                $this->checkController();
            }
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
        $this->field('value_name', ['visible' => true]);
        $this->field('value', ['visible' => false]);
        if ($entity->value == self::PRINTER_EXTERNAL) {
            $this->field('attributes', ['type' => 'custom_external_source']);
        }
        if ($entity->value == self::PRINTER_LIBREOFFICE) {
            $this->field('attributes', ['type' => 'custom_external_source']);
        }
    }

    public function onGetCustomExternalSourceElement(EventInterface $event, $action, Entity $entity, $attr, $options = [])
    {

        $printer = $entity->value;
        if($printer == self::PRINTER_MPDF){
            return null;
        }
        $tableHeaders = [__('Attribute Name'), __('Value')];
        $tableCells = [];
        $ExternalDataSourceAttributes = self::getDynamicTableInstance('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalDataSourceAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->where([
                $ExternalDataSourceAttributes->aliasField('external_data_source_type') => $entity->name
            ])
            ->orderAsc('attribute_field')
            ->toArray();
        switch ($printer) {
            case self::PRINTER_LIBREOFFICE:
                $visibleAttributes = ['api_params'];
                break;
            case self::PRINTER_EXTERNAL:
                $visibleAttributes = ['username', 'password', 'api_url', 'api_params'];
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

            foreach ($attributes as $key => $obj) {
                $rowData = [];
                $rowData[] = __(Inflector::humanize($key));
                $rowData[] = nl2br($obj);
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->getSubject()->renderElement('Configuration.printing_service_pdf', ['attr' => $attr]);

    }

    public function onUpdateFieldValue(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (in_array($action, ['edit', 'add'])) {
            $options = $this->options;
            $attr['options'] = $options;
            $attr['onChangeReload'] = true;
            $this->setExternalAttributes($attr['entity']);
        }


        return $attr;
    }

    public function editBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOption, ArrayObject $extra): void
    {

        $alias = $this->getAlias();
        $data = $requestData[$alias];
        $source = $entity['name'];

        if ($source == 'PDF Service') {
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
            $entity->password = $entity->getOriginal('password');
        }
        if (!empty($errors)) {
            $errorMessage = 'Please enter the required details.';
            //POCOR-7981:starts
            $error_prefix = __CLASS__ . ':' . __FILE__ . __FUNCTION__ . __LINE__;
            $this->log($error_prefix);
            $this->log($errorMessage);
            $this->log($errors);
            //POCOR-7981:ends
            $this->Alert->error('general.externalSourceDataErr', ['reset' => true]);
        } else {//POCOR-6930 Ends
            $this->updateAttributes($source, $entity);
        }
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        $source = $entity->value;
        $this->field('value', ['visible' => true, 'entity' => $entity]);
        $this->field('value_selection', ['visible' => false]);
        switch ($source) {
            case self::PRINTER_LIBREOFFICE:
                $this->field('username', ['type' => 'hidden']);
                $this->field('password', ['type' => 'hidden']);
                $this->field('api_url', ['type' => 'hidden']);
                $this->field('api_params', ['type' => 'string']);
                $this->field('api_key', ['type' => 'hidden']);
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
                break;
            case self::PRINTER_EXTERNAL:
                $this->field('username', ['type' => 'string', 'required' => 'required']);
                $this->field('password', ['type' => 'password', 'required' => 'required']);
                $this->field('api_url', ['type' => 'string', 'required' => 'required']);
                $this->field('api_params', ['type' => 'string']);
                $this->field('api_key', ['type' => 'hidden']);
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

                break;

            default:
                break;
        }
    }


    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }
                $this->field('value_name', ['visible' => true]);
        $this->field('value', ['visible' => false]);

    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
            ->select(
                [$this->aliasField('id'),
                    $this->aliasField('label'),
                    $this->aliasField('value')]
            )->where([
                $this->aliasField('type') => 'PDF Service'
            ]);
    }

    public function onGetValueName(EventInterface $event, Entity $entity)
    {
            return $this->options[$entity->value];
    }
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'value_name') {
            return __('Printer');
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
        $source = $entity->name;

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
            Log::debug('Error: ' . $e->getMessage());
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
    private function updateAttributes(mixed $source, Entity $entity): void
    {
        $ExternalDataSourceAttributes = self::getDynamicTableInstance('Configuration.ExternalDataSourceAttributes'); // POCOR-8849
        $existingRecords = $ExternalDataSourceAttributes->find('list', [
            'keyField' => 'attribute_field',
            'valueField' => 'value'
        ])->where(['external_data_source_type' => $source])->toArray();

        $fields = [
            'username', 'password', 'api_url', 'api_params'
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

<?php

namespace CustomField\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\Http\ServerRequest;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Table;

class CustomFieldsTable extends ControllerActionTable
{
    use OptionsTrait;
    const MANDATORY_NO = 0;
    const UNIQUE_NO = 0;

    protected $fieldTypeFormat = ['OpenEMIS'];
    // Supported Field Types contain full list by default and can by override in individual model extends CustomFieldsTable
    protected $supportedFieldTypes = ['TEXT', 'NUMBER', 'DECIMAL', 'TEXTAREA', 'DROPDOWN', 'CHECKBOX', 'TABLE', 'DATE', 'TIME', 'STUDENT_LIST', 'STAFF_LIST','FILE', 'COORDINATES', 'REPEATER', 'NOTE', 'PLACEHOLDER_DOB', 'PLACEHOLDER_GENDER'];

    private $fieldTypes = [];
    private $fieldTypeOptions = [];
    private $CustomFieldTypes = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        // belongsTo: CustomFieldTypes is not needed as code is store instead of id
        $this->hasMany('CustomFieldOptions', ['className' => 'CustomField.CustomFieldOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CustomFieldValues', ['className' => 'CustomField.CustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
        // Only add association if TABLE type is supported
        if (in_array('TABLE', $this->supportedFieldTypes)) {
            $this->hasMany('CustomTableColumns', ['className' => 'CustomField.CustomTableColumns', 'dependent' => true, 'cascadeCallbacks' => true]);
            $this->hasMany('CustomTableRows', ['className' => 'CustomField.CustomTableRows', 'dependent' => true, 'cascadeCallbacks' => true]);
            $this->hasMany('CustomTableCells', ['className' => 'CustomField.CustomTableCells', 'dependent' => true, 'cascadeCallbacks' => true]);
        }
        $this->belongsToMany('CustomForms', [
            'className' => 'CustomField.CustomForms',
            'joinTable' => 'custom_forms_fields',
            'foreignKey' => 'custom_field_id',
            'targetForeignKey' => 'custom_form_id'
        ]);

        // Each field type will have one behavior attached
        foreach ($this->supportedFieldTypes as $fieldTypeCode) {
            $fieldType = Inflector::camelize(strtolower($fieldTypeCode));
            // Only attach behavior of Supported Field Types
            $this->addBehavior('CustomField.Setup' . $fieldType);
        }
        // End

        $this->CustomFieldTypes = self::getDynamicTableInstance('CustomField.CustomFieldTypes'); // POCOR-8538
        $this->fieldTypeOptions = $this->CustomFieldTypes->getFieldTypeList($this->fieldTypeFormat, $this->fieldTypes);
    }

    public function onGetIsMandatory(EventInterface $event, Entity $entity)
    {
        $isMandatory = $this->CustomFieldTypes->findByCode($entity->field_type)->first()->is_mandatory;
        return $isMandatory == 1 ? ($entity->is_mandatory == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>') : '<i class="fa fa-minus"></i>';
    }

    public function onGetIsUnique(EventInterface $event, Entity $entity)
    {
        $isUnique = $this->CustomFieldTypes->findByCode($entity->field_type)->first()->is_unique;
        return $isUnique == 1 ? ($entity->is_unique == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>') : '<i class="fa fa-minus"></i>';
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('params', ['visible' => false]);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        // always reset
        $queryParams = $this->request->getQueryParams();
        unset($queryParams['field_type']);
        $this->request = $this->request->withQueryParams($queryParams);

    }

    public function editOnInitialize(EventInterface $event, Entity $entity)
    {
        $this->request = $this->request->withQueryParams(['field_type' => $entity->field_type]);
        return null;

    }

    /**
     * Function to delete related options from option lists
     * @param EventInterface $event
     * @param Entity $entity
     * @param ArrayObject $requestData
     * @param ArrayObject $options
     *
     */
    //public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $options, ArrayObject $extra)
    {

        $paramsPass = $this->request->getAttribute('params')['pass'][1];
        $entity->id = $this->paramsDecode($paramsPass)['id'];
        $url = $this->request->getRequestTarget();
         //POCOR-7872::Start //update student_custom_forms_fields if student_custom_field_id exist in table
         if (strpos($url, "StudentCustomFields")!==false){
            $student_custom_forms_fieldsT = self::getDynamicTableInstance('StudentCustomField.StudentCustomFormsFields'); // POCOR-8538
            $student_custom_fieldsT = self::getDynamicTableInstance('StudentCustomField.StudentCustomFields'); // POCOR-8538
            $student_custom_fields_data = $student_custom_fieldsT->get($entity->id);
            $student_custom_forms_fields_data = $student_custom_forms_fieldsT->find()->where(['student_custom_field_id'=> $entity->id])->first();
            if(!empty($student_custom_forms_fields_data)){
                $student_custom_forms_fields_data->name = $student_custom_fields_data->name;
                $student_custom_forms_fields_data->is_mandatory = $student_custom_fields_data->is_mandatory;
                $student_custom_forms_fields_data->is_unique = $student_custom_fields_data->is_unique;
                $student_custom_forms_fieldsT->save($student_custom_forms_fields_data);
            }
        }
        //POCOR-7872::End
        $no_options = true;
        if ($entity->field_type == "CHECKBOX" ) {
            $no_options = false;
        }
        if ($entity->field_type == "DROPDOWN" ) {
            $no_options = false;
        }
        if($no_options){
            return;
        }
        list($options_table_name, $options_custom_field_id) =
            $this->getCustomFieldDomain($url);
        if($this->controller->getName() =='Infrastructures'){
            //$options_table_name = 'InfrastructureCustomFieldOptions';
            //$CustomFieldOptions = self::getDynamicTableInstance($options_table_name);
            $CustomFieldOptions = self::getDynamicTableInstance('Infrastructure.InfrastructureCustomFieldOptions'); // POCOR-8538
        }else{
            $CustomFieldOptions =
            self::getDynamicTableInstance($options_table_name); // POCOR-8538
        }
        $oldCustomFieldOptions = $CustomFieldOptions->find('all')
                ->where([$options_custom_field_id => $entity->id])
                ->enableHydration(false)
                ->toArray();
        $oldCustomFieldOptionsList = array_column($oldCustomFieldOptions, "id");
        $newCustomFieldOptions = $entity['custom_field_options'];
        $newCustomFieldOptionsList = array_column($newCustomFieldOptions, "id");
        $editedOptionsList = array_intersect($oldCustomFieldOptionsList, $newCustomFieldOptionsList);
        $deletedOptionsList = array_diff($oldCustomFieldOptionsList,
            $editedOptionsList);
        foreach ($oldCustomFieldOptions as $key => $value) {
            if (in_array($value['id'], $deletedOptionsList)) {
                // Fetch the entity by ID
                $entity = $CustomFieldOptions->get($value['id']);
                try {
                    $result = $CustomFieldOptions->delete($entity);
                    if ($result) {
                        // Deletion successful
                    } else {
                        // Deletion failed
                        echo "Deletion failed for entity ID: {$value['id']}";
                    }
                } catch (\Exception $e) {
                    // Handle any exceptions or errors
                    echo 'Error: ' . $e->getMessage();
                }
            }
        }

    }

    /**
     * POCOR-8538 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

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

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldFieldType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add') {
            $fieldTypeOptions = $this->fieldTypeOptions;

            $attr['type'] = 'select';
            $attr['options'] = $fieldTypeOptions;
            $attr['onChangeReload'] = 'changeType';
        } elseif ($action == 'edit') {
            $fieldTypeOptions = $this->fieldTypeOptions;
            $selectedFieldType = $request->getQuery('field_type');

            $attr['type'] = 'readonly';
            $attr['value'] = $selectedFieldType;
            $attr['attr']['value'] = $fieldTypeOptions[$selectedFieldType];
        }

        return $attr;
    }

    // public function onUpdateFieldIsMandatory(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldIsMandatory(EventInterface $event, array $attr, $action)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            $selectedFieldType = $this->request->getQuery('field_type');
            $selectedFieldType = (empty($selectedFieldType) && is_array($this->request->getData()[$this->getAlias()]) && array_key_exists('field_type', $this->request->getData()[$this->getAlias()])) ? $this->request->getData()[$this->getAlias()]['field_type'] : $selectedFieldType; //POCOR-8634
            $mandatoryOptions = $this->getSelectOptions('general.yesno');
            $isMandatory = !is_null($selectedFieldType) ? $this->CustomFieldTypes->findByCode($selectedFieldType)->first()->is_mandatory : 0;

            if ($isMandatory) {
                $attr['type'] = 'select';
                $attr['options'] = $mandatoryOptions;
                $attr['select'] = false;    // turn off automatic adding of '-- Select --'
            } else {
                $attr['type'] = 'hidden';
                $attr['value'] = self::MANDATORY_NO;
            }
        }

        return $attr;
    }

    // public function onUpdateFieldIsUnique(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldIsUnique(EventInterface $event, array $attr, $action)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            $selectedFieldType = $this->request->getQuery('field_type');
            $selectedFieldType = (empty($selectedFieldType) && is_array($this->request->getData()[$this->getAlias()]) && array_key_exists('field_type', $this->request->getData()[$this->getAlias()])) ? $this->request->getData()[$this->getAlias()]['field_type'] : $selectedFieldType;//POCOR-8634
            $uniqueOptions = $this->getSelectOptions('general.yesno');
            $isUnique = !is_null($selectedFieldType) ? $this->CustomFieldTypes->findByCode($selectedFieldType)->first()->is_unique : 0;

            if ($isUnique) {
                $attr['type'] = 'select';
                $attr['options'] = $uniqueOptions;
                $attr['select'] = false;    // turn off automatic adding of '-- Select --'
            } else {
                $attr['type'] = 'hidden';
                $attr['value'] = self::UNIQUE_NO;
            }
        }

        return $attr;
    }

    public function addEditOnChangeType(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        $queryParams = $request->getQueryParams();
        unset($queryParams['field_type']);
        $request = $request->withQueryParams($queryParams);
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('field_type', $request->getData()[$this->getAlias()]) && !empty($request->getData()[$this->getAlias()]['field_type'])) {
                    $queryParams['field_type'] = $request->getData()[$this->getAlias()]['field_type'];
                    $this->request = $request->withQueryParams($queryParams);
                }
            }
        }
    }

    private function setupFields(Entity $entity)
    {
        $this->field('field_type');
        $this->field('is_mandatory');
        $this->field('is_unique');

        // trigger event to add required fields for different field type
        $fieldType = Inflector::camelize(strtolower($entity->field_type));
        $event = $this->dispatchEvent('Setup.set' . $fieldType . 'Elements', [$entity], $this);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        $this->setFieldOrder(['field_type', 'name', 'description', 'is_mandatory', 'is_unique']);
    }

    public function setFieldTypes($type)
    {
        $this->fieldTypes[$type] = $type;
    }

    public function getFieldTypes()
    {
        return $this->fieldTypes;
    }

    public function getSupportedFieldTypesByModel($model)
    {
        $CustomModules = self::getDynamicTableInstance('CustomField.CustomModules');//status save krte time idr ata hai // POCOR-8538
        $supportedFieldTypes = $CustomModules
            ->find()
            ->where([$CustomModules->aliasField('model') => $model])
            ->first()
            ->supported_field_types;

        return $supportedFieldTypes;
    }

    /**
     * @param $url
     * @return array
     */

    private function getCustomFieldDomain($url)
    {
        $arr = explode("/", $url);
        $customFieldsName = 'StudentCustomFields';
        $key = array_search($customFieldsName, $arr); //POCOR-7700
        if (!$key) {
            $customFieldsName = 'InstitutionCustomFields';
            $key = array_search($customFieldsName, $arr); //POCOR-7700
        }
        if (!$key) {
            $customFieldsName = 'StaffCustomFields';
            $key = array_search($customFieldsName, $arr); //POCOR-7700
        }
        if (!$key) {
            $customFieldsName = 'Infrastructures';
            $key = array_search($customFieldsName, $arr); //POCOR-7700
        }
        if ($arr[$key] == $customFieldsName) {
            if ($customFieldsName == 'StudentCustomFields') {
                $options_table_name = 'student_custom_field_options';
                $options_custom_field_id = 'student_custom_field_id';
            }
            if ($customFieldsName == 'InstitutionCustomFields') {
                $options_table_name = 'institution_custom_field_options';
                $options_custom_field_id = 'institution_custom_field_id';
            }
            if ($customFieldsName == 'StaffCustomFields') {
                $options_table_name = 'staff_custom_field_options';
                $options_custom_field_id = 'staff_custom_field_id';
            }
            if ($customFieldsName == 'Infrastructures') {
                $options_table_name = 'infrastructure_custom_field_options';
                $options_custom_field_id = 'infrastructure_custom_field_id';
            }
        }
        return array($options_table_name, $options_custom_field_id);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}

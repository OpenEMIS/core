<?php
namespace CustomField\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class CustomFieldsTable extends ControllerActionTable
{
    use OptionsTrait;
    const MANDATORY_NO = 0;
    const UNIQUE_NO = 0;

    protected $fieldTypeFormat = ['OpenEMIS'];
    // Supported Field Types contain full list by default and can by override in individual model extends CustomFieldsTable
    protected $supportedFieldTypes = ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','CHECKBOX','TABLE','DATE','TIME','STUDENT_LIST','FILE','COORDINATES','REPEATER','NOTE'];

    private $fieldTypes = [];
    private $fieldTypeOptions = [];
    private $CustomFieldTypes = null;

    public function initialize(array $config)
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
            $this->addBehavior('CustomField.Setup'.$fieldType);
        }
        // End

        $this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
        $this->fieldTypeOptions = $this->CustomFieldTypes->getFieldTypeList($this->fieldTypeFormat, $this->fieldTypes);
    }

    public function onGetIsMandatory(Event $event, Entity $entity)
    {
        $isMandatory = $this->CustomFieldTypes->findByCode($entity->field_type)->first()->is_mandatory;
        return $isMandatory == 1 ? ($entity->is_mandatory == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>') : '<i class="fa fa-minus"></i>';
    }

    public function onGetIsUnique(Event $event, Entity $entity)
    {
        $isUnique = $this->CustomFieldTypes->findByCode($entity->field_type)->first()->is_unique;
        return $isUnique == 1 ? ($entity->is_unique == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>') : '<i class="fa fa-minus"></i>';
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('params', ['visible' => false]);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        // always reset
        unset($this->request->query['field_type']);
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $this->request->query['field_type'] = $entity->field_type;
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldFieldType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add') {
            $fieldTypeOptions = $this->fieldTypeOptions;

            $attr['type'] = 'select';
            $attr['options'] = $fieldTypeOptions;
            $attr['onChangeReload'] = 'changeType';
        } elseif ($action == 'edit') {
            $fieldTypeOptions = $this->fieldTypeOptions;
            $selectedFieldType = $request->query('field_type');

            $attr['type'] = 'readonly';
            $attr['value'] = $selectedFieldType;
            $attr['attr']['value'] = $fieldTypeOptions[$selectedFieldType];
        }

        return $attr;
    }

    public function onUpdateFieldIsMandatory(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            $selectedFieldType = $request->query('field_type');
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

    public function onUpdateFieldIsUnique(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            $selectedFieldType = $request->query('field_type');
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

    public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['field_type']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('field_type', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['field_type'])) {
                    $this->request->query['field_type'] = $request->data[$this->alias()]['field_type'];
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
            return $event->result;
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
        $CustomModules = TableRegistry::get('CustomField.CustomModules');

        $supportedFieldTypes = $CustomModules
            ->find()
            ->where([$CustomModules->aliasField('model') => $model])
            ->first()
            ->supported_field_types;

        return $supportedFieldTypes;
    }
}

<?php
namespace InstitutionCustomField\Model\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use ArrayObject;

use CustomField\Model\Table\CustomFieldsTable;

class InstitutionCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config): void {
		$this->supportedFieldTypes = $this->getSupportedFieldTypesByModel('Institution.Institutions');
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'InstitutionCustomField.InstitutionCustomFieldOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'InstitutionCustomField.InstitutionCustomTableColumns', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'InstitutionCustomField.InstitutionCustomTableRows', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'InstitutionCustomField.InstitutionCustomForms',
			'joinTable' => 'institution_custom_forms_fields',
			'foreignKey' => 'institution_custom_field_id',
			'targetForeignKey' => 'institution_custom_form_id',
			'through' => 'InstitutionCustomField.InstitutionCustomFormsFields',
			'dependent' => true
		]);
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'field_type') {
            return __('Field Type');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'is_mandatory') {
            return __('Is Mandatory');
        } elseif ($field == 'is_unique') {
            return __('Is Unique');
        } elseif ($field == 'validation_rule') {
            return __('Validation Rule');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}

<?php
namespace StaffCustomField\Model\Table;
use Cake\Event\Event;
use Cake\Http\ServerRequest;

use CustomField\Model\Table\CustomFieldsTable;

class StaffCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config): void {
		$this->supportedFieldTypes = $this->getSupportedFieldTypesByModel('Staff.Staff');
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'StaffCustomField.StaffCustomFieldOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'StaffCustomField.StaffCustomTableColumns', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'StaffCustomField.StaffCustomTableRows', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'StaffCustomField.StaffCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'StaffCustomField.StaffCustomTableCells', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'StaffCustomField.StaffCustomForms',
			'joinTable' => 'staff_custom_forms_fields',
			'foreignKey' => 'staff_custom_field_id',
			'targetForeignKey' => 'staff_custom_form_id',
			'through' => 'StaffCustomField.StaffCustomFormsFields',
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
}

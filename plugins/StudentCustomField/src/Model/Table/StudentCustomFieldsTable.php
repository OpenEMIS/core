<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;

class StudentCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config): void {
		$this->supportedFieldTypes = $this->getSupportedFieldTypesByModel('Student.Students');
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'StudentCustomField.StudentCustomFieldOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'StudentCustomField.StudentCustomTableColumns', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'StudentCustomField.StudentCustomTableRows', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'StudentCustomField.StudentCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'StudentCustomField.StudentCustomTableCells', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'StudentCustomField.StudentCustomForms',
			'joinTable' => 'student_custom_forms_fields',
			'foreignKey' => 'student_custom_field_id',
			'targetForeignKey' => 'student_custom_form_id',
			'through' => 'StudentCustomField.StudentCustomFormsFields',
			'dependent' => true
		]);
	}

	public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'field_type') {
            return __('Field Type');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'description') {
            return __('Description');
        }elseif ($field == 'is_mandatory') {
            return __('Is Mandatory');
        } elseif ($field == 'is_unique') {
            return __('Is Unique');
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

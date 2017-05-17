<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class InfrastructureCustomFieldsTable extends CustomFieldsTable
{
    public function initialize(array $config)
    {
        // Using InstitutionLands supported field type as all infrastructure modules uses the same types
        $this->supportedFieldTypes = $this->getSupportedFieldTypesByModel('Institution.InstitutionLands');
        parent::initialize($config);
        $this->hasMany('CustomFieldOptions', ['className' => 'Infrastructure.InfrastructureCustomFieldOptions', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CustomFieldValues', ['className' => 'Infrastructure.InfrastructureCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('LandCustomFieldValues', ['className' => 'Infrastructure.LandCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('BuildingCustomFieldValues', ['className' => 'Infrastructure.BuildingCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('FloorCustomFieldValues', ['className' => 'Infrastructure.FloorCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RoomCustomFieldValues', ['className' => 'Infrastructure.RoomCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('CustomForms', [
            'className' => 'Infrastructure.InfrastructureCustomForms',
            'joinTable' => 'infrastructure_custom_forms_fields',
            'foreignKey' => 'infrastructure_custom_field_id',
            'targetForeignKey' => 'infrastructure_custom_form_id',
            'through' => 'Infrastructure.InfrastructureCustomFormsFields',
            'dependent' => true
        ]);
        $this->setDeleteStrategy('restrict');
    }
}

<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class BuildingCustomFieldValuesTable extends CustomFieldValuesTable
{
    protected $extra = ['scope' => 'infrastructure_custom_field_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.BuildingCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
        $this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionBuildings', 'foreignKey' => 'institution_building_id']);
    }
}

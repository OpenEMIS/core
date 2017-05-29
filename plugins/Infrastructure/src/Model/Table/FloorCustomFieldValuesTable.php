<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class FloorCustomFieldValuesTable extends CustomFieldValuesTable
{
    protected $extra = ['scope' => 'infrastructure_custom_field_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.FloorCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
        $this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionFloors', 'foreignKey' => 'institution_floor_id']);
    }
}

<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class LandCustomFieldValuesTable extends CustomFieldValuesTable
{
    protected $extra = ['scope' => 'infrastructure_custom_field_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.LandCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
        $this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'institution_land_id']);
    }
}

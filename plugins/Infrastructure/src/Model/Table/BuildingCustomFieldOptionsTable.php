<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldOptionsTable;

class BuildingCustomFieldOptionsTable extends CustomFieldOptionsTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_custom_field_options');
        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.BuildingCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'infrastructure_custom_field_id',
            ]);
        }
    }
}

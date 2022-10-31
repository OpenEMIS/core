<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldOptionsTable;

class FloorCustomFieldOptionsTable extends CustomFieldOptionsTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_custom_field_options');
        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.FloorCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'infrastructure_custom_field_id',
            ]);
        }
    }
}

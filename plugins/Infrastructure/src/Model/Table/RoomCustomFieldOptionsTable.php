<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldOptionsTable;

class RoomCustomFieldOptionsTable extends CustomFieldOptionsTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_custom_field_options');
        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.RoomCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->setConfig([
                'filter' => 'infrastructure_custom_field_id',
            ]);
        }
    }
}

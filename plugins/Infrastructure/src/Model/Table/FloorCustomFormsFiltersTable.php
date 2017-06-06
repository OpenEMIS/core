<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;

class FloorCustomFormsFiltersTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_custom_forms_filters');
        parent::initialize($config);
        $this->belongsTo('CustomForms', ['className' => 'Infrastructure.FloorCustomForms', 'foreignKey' => 'infrastructure_custom_form_id']);
        $this->belongsTo('CustomFilters', ['className' => 'Infrastructure.FloorTypes', 'foreignKey' => 'infrastructure_custom_filter_id']);
    }
}

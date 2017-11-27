<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;

class QualityVisitTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('quality_visit_types');
        parent::initialize($config);
        $this->hasMany('InstitutionQualityVisits', ['className' => 'Quality.InstitutionQualityVisits', 'foreignKey' => 'quality_visit_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}

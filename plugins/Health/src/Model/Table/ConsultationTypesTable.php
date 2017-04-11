<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class ConsultationTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('health_consultation_types');
        parent::initialize($config);

        $this->hasMany('Consultations', ['className' => 'Health.Consultations', 'foreignKey' => 'health_consultation_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}

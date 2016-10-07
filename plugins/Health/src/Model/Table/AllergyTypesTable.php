<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class AllergyTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('health_allergy_types');
        parent::initialize($config);

        $this->hasMany('Allergies', ['className' => 'Health.Allergies', 'foreignKey' => 'health_allergy_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}

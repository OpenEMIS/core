<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class EquipmentConditionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('Equipment', ['className' => 'Institution.InstitutionEquipment', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
    }
}

<?php
namespace SpecialNeeds\Model\Table;

use App\Model\Table\ControllerActionTable;

class SpecialNeedsDeviceTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('SpecialNeedsDevices', ['className' => 'SpecialNeeds.SpecialNeedsDevices', 'dependent' => true, 'cascadeCallbacks' => true]);
        
        $this->addBehavior('FieldOption.FieldOption');
    }
    // Start POCOR-7286
    public function beforeAction() {
        $this->field('name', ['length' => 75]);
    }
    // End POCOR-7286
}

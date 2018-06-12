<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class AssetTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('InstitutionAssets', ['className' => 'Institution.InstitutionAssets', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
    }
}

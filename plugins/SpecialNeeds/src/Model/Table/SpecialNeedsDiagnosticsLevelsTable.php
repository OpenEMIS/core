<?php
namespace SpecialNeeds\Model\Table;

use App\Model\Table\ControllerActionTable;

class SpecialNeedsDiagnosticsLevelsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
        $this->hasMany('SpecialNeedsDiagnostics', ['className' => 'SpecialNeeds.SpecialNeedsDiagnostics', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'DiagnosticsLevels' => ['index', 'view']
        ]);
    }
}

<?php
namespace SpecialNeeds\Model\Table;

use App\Model\Table\ControllerActionTable;

class SpecialNeedsDiagnosisLevelsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
        $this->hasMany('SpecialNeedsDiagnosis', ['className' => 'SpecialNeeds.SpecialNeedsDiagnosis', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'DiagnosisLevels' => ['index', 'view']
        ]);
    }
}

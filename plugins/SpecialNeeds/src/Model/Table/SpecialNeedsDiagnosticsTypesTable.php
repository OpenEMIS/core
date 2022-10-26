<?php

namespace SpecialNeeds\Model\Table;

use App\Model\Table\ControllerActionTable;

/**
 * Class is to get new tab data in dignosis in Special needs
 * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
 * @ticket POCOR-6873
 */

class SpecialNeedsDiagnosticsTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('FieldOption.FieldOption');
        $this->hasMany('SpecialNeedsDiagnostics', ['className' => 'SpecialNeeds.SpecialNeedsDiagnostics', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'DiagnosticsTypes' => ['index', 'view']
        ]);
    }

    public function getDiagnosticsTypeList()
    {

        $data = $this
            ->find('list')
            // ->where([$this->aliasField('special_needs_diagnostics_types_id') => $degreeId])
            ->toArray();
        return $data;
    }
}

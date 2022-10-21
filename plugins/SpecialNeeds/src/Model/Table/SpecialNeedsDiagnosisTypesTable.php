<?php

namespace SpecialNeeds\Model\Table;

use App\Model\Table\ControllerActionTable;

/**
 * Class is to get new tab data in dignosis in Special needs
 * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
 * @ticket POCOR-6873
 */

class SpecialNeedsDiagnosisTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('FieldOption.FieldOption');
        $this->hasMany('SpecialNeedsDiagnosis', ['className' => 'SpecialNeeds.SpecialNeedsDiagnosis', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'DiagnosisTypes' => ['index', 'view']
        ]);
    }

    public function getDiagnosisTypeList()
    {

        $data = $this
            ->find('list')
            // ->where([$this->aliasField('special_needs_diagnosis_types_id') => $degreeId])
            ->toArray();
        return $data;
    }
}

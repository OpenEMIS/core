<?php
namespace Institution\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionEquipmentTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EquipmentTypes', ['className' => 'Institution.EquipmentTypes']);
        $this->belongsTo('EquipmentPurposes', ['className' => 'Institution.EquipmentPurposes']);
        $this->belongsTo('EquipmentConditions', ['className' => 'Institution.EquipmentConditions']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['academic_period_id', 'institution_id']]],
                'provider' => 'table'
            ]);
    }
}

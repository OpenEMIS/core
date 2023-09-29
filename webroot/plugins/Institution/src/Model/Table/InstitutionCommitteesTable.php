<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionCommitteesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionCommitteeTypes', ['className' => 'Institutions.InstitutionCommitteeTypes']);
        $this->hasMany('InstitutionCommitteeAttachments', [
            'className' => 'Institutions.InstitutionCommitteeAttachments',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('meeting_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->add('end_time', 'ruleCompareTimeReverse', [
                'rule' => ['compareDateReverse', 'start_time', false]
            ]);
    }
}

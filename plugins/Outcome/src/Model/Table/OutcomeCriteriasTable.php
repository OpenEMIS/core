<?php
namespace Outcome\Model\Table;

use App\Model\Table\ControllerActionTable;

class OutcomeCriteriasTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('GradingTypes', ['className' => 'Outcome.OutcomeGradingTypes']);
        $this->belongsTo('Templates', [
            'className' => 'Outcome.OutcomeTemplates',
            'foreignKey' => ['outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id']
        ]);

        $this->hasMany('InstitutionOutcomeResults', [
            'className' => 'Institution.InstitutionOutcomeResults',
            'foreignKey' => ['outcome_criteria_id', 'outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'outcome_template_id', 'academic_period_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }
}

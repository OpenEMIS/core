<?php
namespace Assessment\Model\Table;

use App\Model\Table\ControllerActionTable;

class AssessmentItemGradingTypesTable extends ControllerActionTable {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('AssessmentGradingTypes', ['className' => 'Assessment.AssessmentGradingTypes', 'dependent' => true]);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments', 'dependent' => true]);
        $this->belongsTo('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true]);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'dependent' => true]);
    }
}
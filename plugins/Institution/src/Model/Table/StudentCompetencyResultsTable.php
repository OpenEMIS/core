<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class StudentCompetencyResultsTable extends AppTable {
    public function initialize(array $config) {
        parent::initialize($config);

        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('CompetencyTemplates', ['className' => 'Competency.CompetencyTemplates', 'foreignKey' => ['competency_template_id', 'academic_period_id']]);
        $this->belongsTo('CompetencyItems', ['className' => 'Competency.CompetencyItems', 'foreignKey' => ['competency_item_id', 'academic_period_id']]);
        $this->belongsTo('CompetencyCriterias', ['className' => 'Competency.CompetencyCriterias', 'foreignKey' => ['competency_criteria_id', 'academic_period_id']]);
        $this->belongsTo('CompetencyPeriods', ['className' => 'Competency.CompetencyPeriods', 'foreignKey' => ['competency_period_id', 'academic_period_id']]);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        
        $this->addBehavior('CompositeKey');
    }
}

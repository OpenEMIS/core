<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AssessmentResultsTable extends AppTable {
	public function initialize(array $config) {
		// $this->table('assessment_results');
        parent::initialize($config);
		
		$this->belongsTo('AssessmentResultTypes', ['className' => 'Institution.AssessmentResultTypes']);
		// $this->belongsTo('Students', ['className' => 'Student.Students', 'foreignKey' => 'assessment_result_type_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

		// $this->hasMany('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
	}
}

<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSitePositionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		if ($this->action == 'index') {

		}

		$this->fields['staff_position_title_id']['type'] = 'select';
		$this->fields['staff_position_grade_id']['type'] = 'select';
	}
}

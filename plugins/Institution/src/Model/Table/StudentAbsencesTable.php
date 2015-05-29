<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentAbsencesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_student_absences');
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.Sections']);
	}

	public function validationDefault(Validator $validator) {
		// $validator->add('name', 'notBlank', [
		// 	'rule' => 'notBlank'
		// ]);
		return $validator;
	}

	public function beforeAction() {
		
	}
}

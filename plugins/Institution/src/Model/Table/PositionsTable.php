<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class PositionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_positions');
		parent::initialize($config);
		
		// $this->belongsTo('Titles', ['className' => 'Institution.StaffPositionTitles', 'foreignKey' => 'staff_position_title_id']);
		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		// $this->belongsTo('Grades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator->add('name', 'notBlank', [
			'rule' => 'notBlank'
		]);
		return $validator;
	}

	public function beforeAction() {
		
	}
}

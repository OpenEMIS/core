<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;

class InstitutionSitePositionsTable extends AppTable {
	use OptionsTrait;

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
		$this->fields['staff_position_title_id']['type'] = 'select';
		$this->fields['staff_position_grade_id']['type'] = 'select';

		$this->fields['status']['type'] = 'select';
		$this->fields['status']['options'] = $this->getSelectOptions('general.active');
		$this->fields['type']['type'] = 'select';
		$this->fields['type']['options'] = $this->getSelectOptions('Staff.position_types');
	}
}

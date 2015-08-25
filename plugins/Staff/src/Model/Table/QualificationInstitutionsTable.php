<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;

class QualificationInstitutionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('Qualifications', ['className' => 'Staff.Qualifications', 'dependent' => true]);

		$this->addBehavior('Reorder');
	}
}

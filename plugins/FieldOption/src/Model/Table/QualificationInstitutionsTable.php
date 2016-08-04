<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;

class QualificationInstitutionsTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('FieldOption.FieldOption');
		$this->hasMany('Qualifications', ['className' => 'Staff.Qualifications', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}

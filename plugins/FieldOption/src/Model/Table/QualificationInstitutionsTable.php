<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class QualificationInstitutionsTable extends AppTable {
	public function initialize(array $config)
    {
		$this->addBehavior('FieldOption.FieldOption');
		$this->hasMany('Qualifications', ['className' => 'Staff.Qualifications', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}

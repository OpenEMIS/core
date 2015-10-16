<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class FeeTypesTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        parent::initialize($config);
		$this->hasMany('InstitutionSiteFeeTypes', ['className' => 'Institution.InstitutionSiteFeeTypes', 'foreignKey' => 'fee_type_id']);
	}
}

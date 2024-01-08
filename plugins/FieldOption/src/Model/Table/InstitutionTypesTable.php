<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;

class InstitutionTypesTable extends ControllerActionTable {
	public function initialize(array $config): void
    {
    	$this->setTable('institution_types');
        parent::initialize($config);

		$this->addBehavior('FieldOption.FieldOption');
	}
}

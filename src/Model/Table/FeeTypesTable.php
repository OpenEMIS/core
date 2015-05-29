<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class FeeTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('field_option_values');
        parent::initialize($config);
        $this->addBehavior('FieldOptionValues');
		
	}
}

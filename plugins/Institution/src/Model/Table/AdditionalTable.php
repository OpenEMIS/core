<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AdditionalTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_custom_values');
        parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		
	}

}

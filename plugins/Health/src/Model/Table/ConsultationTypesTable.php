<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class ConsultationTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('health_consultation_types');
		parent::initialize($config);
		$this->hasMany('Consultations', ['className' => 'Health.Consultations', 'foreignKey' => 'health_consultation_type_id', 'dependent' => true]);
	}
}

<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class NetworkConnectivitiesTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        $this->table('institution_network_connectivities');
        parent::initialize($config);
		
		$this->hasMany('Institutions', ['className' => 'Institution.Institutions']);
	}
}

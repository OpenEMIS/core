<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class OwnershipsTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        parent::initialize($config);
		
		$this->hasMany('InstitutionSites', ['className' => 'Institution.InstitutionSites', 'foreignKey' => 'institution_site_locality_id']);
	}
}

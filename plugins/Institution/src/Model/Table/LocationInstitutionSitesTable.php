<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LocationInstitutionSitesTable extends AppTable {
	public function initialize(array $config) {
        // $this->addBehavior('ControllerAction.FieldOption');
		$this->table('institution_sites');
        parent::initialize($config);
				
		$this->hasMany('InstitutionSiteShifts', ['className' => 'Institution.InstitutionSiteShifts', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}

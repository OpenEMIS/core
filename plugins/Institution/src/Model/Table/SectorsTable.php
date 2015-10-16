<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SectorsTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        parent::initialize($config);
		
		$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_sector_id']);
	}
}

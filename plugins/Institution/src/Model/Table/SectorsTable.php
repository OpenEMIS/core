<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class SectorsTable extends ControllerActionTable {
	public function initialize(array $config)
    {
        $this->addBehavior('FieldOption.FieldOption');
        $this->table('institution_sectors');
        parent::initialize($config);

		$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_sector_id']);
        $this->hasMany('Providers', ['className' => 'Institution.Providers', 'foreignKey' => 'institution_sector_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'restrict');
	}
}

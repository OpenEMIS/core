<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;

class ProvidersTable extends ControllerActionTable {
	public function initialize(array $config)
    {
        $this->addBehavior('FieldOption.FieldOption');
        $this->table('institution_providers');
        parent::initialize($config);

		$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Sectors', ['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

    public function addEditBeforeAction(Event $event) {
        $this->field('institution_sector_id', ['type' => 'select', 'after' => 'name']);
    }
}

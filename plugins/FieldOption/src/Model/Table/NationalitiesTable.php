<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class NationalitiesTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('nationalities');
		parent::initialize($config);

        $this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
		$this->hasMany('UserNationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'nationality_id']);

        $this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

    public function afterAction(Event $event) {
        $this->field('identity_type_id', ['type' => 'select', 'after' => 'name']);
    }
}

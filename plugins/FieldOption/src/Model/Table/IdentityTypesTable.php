<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class IdentityTypesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('identity_types');
		parent::initialize($config);
		
		$this->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'identity_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

    public function findDefaultIdentityType(Query $query, array $options) {
        $defaultId = $this->getDefaultValue();
        return $query->where([$this->aliasField('id') => $defaultId]);
    }
}

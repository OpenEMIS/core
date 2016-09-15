<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Query;

class SpecialNeedTypesTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('FieldOption.FieldOption');
		$this->table('special_need_types');
		parent::initialize($config);
		$this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds', 'foreignKey' => 'special_need_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

    public function findVisibleNeedTypes(Query $query, array $options)
    {
        $query
            ->find('visible')
            ->find('order')
            ->select(['special_need_id' => $this->aliasField('id'), 'special_need_name' => $this->aliasField('name')]);
        return $query;
    }
}

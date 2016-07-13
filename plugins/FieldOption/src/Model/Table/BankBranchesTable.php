<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Validation\Validator;

class BankBranchesTable extends ControllerActionTable {
	public function initialize(array $config)
	{
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('bank_branches');
		parent::initialize($config);
		$this->belongsTo('Banks', ['className' => 'FieldOption.Banks']);
		$this->hasMany('UserBankAccounts', ['className' => 'User.BankAccounts', 'foreignKey' => 'bank_branch_id']);
		$this->hasMany('InstitutionBankAccounts', ['className' => 'Institution.InstitutionBankAccounts', 'foreignKey' => 'bank_branch_id']);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'bank_id',
			]);
		}
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);

		$validator
			->notEmpty('name', 'Please enter a name.')
			->notEmpty('code', 'Please enter a code.');;

		return $validator;
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('code', ['after' => 'name']);
		$this->field('bank_id', ['visible' => 'false']);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$parentFieldOptions = $this->Banks->find('list')->toArray();
		$selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

		if (!empty($selectedParentFieldOption)) {
			$query->where([$this->aliasField('bank_id') => $selectedParentFieldOption]);
		}

		$this->controller->set(compact('parentFieldOptions', 'selectedParentFieldOption'));
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('bank_id');
	}

	public function onUpdateFieldBankId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$parentFieldOptions = $this->Banks->find('list')->toArray();
			$selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

			$attr['type'] = 'readonly';
			$attr['value'] = $selectedParentFieldOption;
			$attr['attr']['value'] = $parentFieldOptions[$selectedParentFieldOption];
		}
		return $attr;
	}
}

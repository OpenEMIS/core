<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionBankAccountsTable extends AppTable {
	use OptionsTrait;
	private $_selectedBankId = 1;
	private $_bankOptions = [];


/******************************************************************************************************************
**
** CakePHP default methods
**
******************************************************************************************************************/
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('BankBranches', ['className' => 'FieldOption.BankBranches']);

	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	public function beforeAction($event) {
		$this->ControllerAction->field('account_name', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('account_number', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('active', ['type' => 'select', 'options' => $this->getSelectOptions('general.yesno'), 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('bank_branch_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('remarks', ['type' => 'text', 'visible' => ['view'=>true, 'edit'=>true]]);

		$this->ControllerAction->field('bank', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true], 'onChangeReload' => true]);

		$this->ControllerAction->setFieldOrder([
			'active', 'account_name', 'account_number', 'bank', 'bank_branch_id',
		]);
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['BankBranches.Banks']);
	}

/******************************************************************************************************************
**
** viewEdit action methods
**
******************************************************************************************************************/
	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain('BankBranches.Banks');
	}

/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'bank', 'bank_branch_id', 'account_name', 'account_number', 'active', 'remarks'
		]);
	}

	public function addEditAfterAction(Event $event, Entity $entity)
	{
		if (empty($this->_bankOptions)) {
			$this->_bankOptions = $this->getBankOptions();
		}

		if (($entity->toArray())) {
			if ($entity->has('bank')) {
				$this->_selectedBankId = $entity->bank;
			} else {
				$this->_selectedBankId = $entity->bank_branch->bank->id;
			}
		} else {
			// 1st instance of add
			$this->_selectedBankId = '';
		}

		$bankBranches = $this->BankBranches
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->find('visible')
			->where(['bank_id'=>$this->_selectedBankId])
			->order(['order'])
			->toArray();

		$this->fields['bank_branch_id']['options'] = $bankBranches;
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction(Event $event) {
		$this->fields['bank']['type'] = 'disabled';
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->fields['bank']['attr']['value'] = $entity->bank_branch->bank->name;
	}


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetBank(Event $event, Entity $entity) {
		return $entity->bank_branch->bank->name;
	}

	public function onUpdateFieldBank(Event $event, array $attr, $action, $request) {
		$this->_bankOptions = $this->getBankOptions();
		$attr['options'] = $this->_bankOptions;
		return $attr;
	}

	public function onUpdateFieldBankBranchId(Event $event, array $attr, $action, $request) {
		if (empty($this->_bankOptions)) {
			$this->_bankOptions = $this->getBankOptions();
		}
		$this->_selectedBankId = $this->postString('bank', $this->_bankOptions);
		$bankBranches = $this->BankBranches
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->find('visible')
			->where(['bank_id'=>$this->_selectedBankId])
			->toArray();
		$attr['options'] = $bankBranches;
		if (empty($bankBranches)) {
			$attr['empty'] = 'Select';
		}
		return $attr;
	}

	public function onGetActive(Event $event, Entity $entity) {
		$icons = [
			0 => '<i class="fa kd-cross red"></i>',
			1 => '<i class="fa kd-check green"></i>'
		];

		return $icons[$entity->active];
	}

/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	private function getBankOptions()
	{
		return $this->_bankOptions = $this->BankBranches->Banks
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->find('visible')
			->order(['order'])
			->toArray();
	}
}

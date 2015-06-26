<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

class InstitutionSiteBankAccountsTable extends AppTable {
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
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('BankBranches', ['className' => 'FieldOption.BankBranches']);
	
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction($event) {

    	$this->ControllerAction->field('account_name', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('account_number', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('active', ['type' => 'select', 'options' => $this->getSelectOptions('general.active'), 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
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
	public function indexBeforePaginate($event, $request, $paginateOptions) {
		$paginateOptions['finder'] = ['withBanks' => []];
		return $paginateOptions;
	}

    public function findWithBanks(Query $query, array $options) {
    	return $query->contain(['BankBranches'=>['Banks']]);
    }


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewBeforeQuery(Event $event, Query $query, array $contain) {
		$contain = array_merge($contain, ['BankBranches'=>['Banks']]);
		return compact('query', 'contain');
	}


/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
    public function addEditBeforeAction($event) {
		$this->ControllerAction->setFieldOrder([
			'bank', 'bank_branch_id', 'account_name', 'account_number', 'active', 'remarks'
		]);
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
    public function editBeforeAction($event) {
    	$this->fields['bank']['type'] = 'disabled';
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		$contain = array_merge($contain, ['BankBranches'=>['Banks']]);
		return compact('query', 'contain');
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
		$this->_selectedBankId = $this->postString('bank_id', $this->_bankOptions);
		$bankBranches = $this->BankBranches
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->where(['bank_id'=>$this->_selectedBankId])
			->toArray();
		$attr['options'] = $bankBranches;
		return $attr;
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	private function getBankOptions() {
		return $this->_bankOptions = $this->BankBranches->Banks
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->toArray();
	}
}

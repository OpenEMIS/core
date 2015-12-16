<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;

class BankAccountsTable extends AppTable {
	use OptionsTrait;
	public function initialize(array $config) {
		$this->table('user_bank_accounts');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('BankBranches', ['className' => 'FieldOption.BankBranches']);
	}

	public function beforeAction($event) {
		$bankOptions = TableRegistry::get('FieldOption.Banks')
			->find('list')
			->find('order')
			->toArray();
		$this->ControllerAction->addField('bank_name',['type' => 'select','options'=>$bankOptions]);
	}

	public function indexBeforeAction(Event $event) {
		$order = 0;
		$this->ControllerAction->setFieldOrder('active', $order++);
		$this->ControllerAction->setFieldOrder('account_name', $order++);
		$this->ControllerAction->setFieldOrder('account_number', $order++);
		$this->ControllerAction->setFieldOrder('bank_name', $order++);
		$this->ControllerAction->setFieldOrder('bank_branch_id', $order++);	
	}

	public function addEditBeforeAction(Event $event) {
		$bankOptions = TableRegistry::get('FieldOption.Banks')
			->find('list')
			->find('order')
			->toArray();

		$bankName = key($bankOptions);
		if ($this->request->data($this->aliasField('bank_name'))) {
			$bankName = $this->request->data($this->aliasField('bank_name'));
		}
		$this->ControllerAction->addField('bank_name',['type' => 'select','options'=>$bankOptions]);
		$this->fields['bank_name']['attr'] = ['onchange' => "$('#reload').click()"];

		$this->fields['bank_branch_id']['type'] = 'select';
		$this->fields['bank_branch_id']['options'] = $this->BankBranches
			->find('list')
			->find('visible')
			->where(['bank_id' => $bankName])
			->toArray()
		;

		$this->fields['active']['type'] = 'select';
		$this->fields['active']['options'] = $this->getSelectOptions('general.yesno');;

		$order = 0;
		$this->ControllerAction->setFieldOrder('bank_name', $order++);
		$this->ControllerAction->setFieldOrder('bank_branch_id', $order++);
		$this->ControllerAction->setFieldOrder('account_name', $order++);
		$this->ControllerAction->setFieldOrder('account_number', $order++);
		$this->ControllerAction->setFieldOrder('active', $order++);
		$this->ControllerAction->setFieldOrder('remarks', $order++);
	}

	public function onGetActive(Event $event, Entity $entity) {
		$active = $entity->active;
		if ($active == 1) {
			return __('Yes');
		} else {
			return __('No');
		}
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('bank_name', [
			])
		;
	}

	private function setupTabElements() {
		switch ($this->controller->name) {
			case 'Students':
				$tabElements = $this->controller->getFinanceTabElements();
				$this->controller->set('tabElements', $tabElements);
				$this->controller->set('selectedAction', $this->alias());
				break;
			case 'Staff':
				$tabElements = $this->controller->getFinanceTabElements();
				$this->controller->set('tabElements', $tabElements);
				$this->controller->set('selectedAction', $this->alias());
				break;
			case 'Directories':
				$type = $this->request->query('type');
				$options = [
					'type' => $type
				];
				if ($type == 'student') {
					$tabElements = $this->controller->getFinanceTabElements($options);
				} else {
					$tabElements = $this->controller->getStaffFinanceTabElements($options);
				}
				
				$this->controller->set('tabElements', $tabElements);
				$this->controller->set('selectedAction', $this->alias());
				break;
		}
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
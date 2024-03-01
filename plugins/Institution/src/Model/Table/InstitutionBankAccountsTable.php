<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

use App\Model\Table\ControllerActionTable; //POCOR-6160 change extend class

class InstitutionBankAccountsTable extends ControllerActionTable {
	use OptionsTrait;
	private $_selectedBankId = 1;
	private $_bankOptions = [];


/******************************************************************************************************************
**
** CakePHP default methods
**
******************************************************************************************************************/
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('BankBranches', ['className' => 'FieldOption.BankBranches']);

        $this->addBehavior('Excel', ['pages' => ['index']]);

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['BankAccounts'=>['id']]
        ]);
	}

	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('account_name', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->field('account_number', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->field('active', ['type' => 'select', 'options' => $this->getSelectOptions('general.yesno'), 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->field('bank_branch_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->field('remarks', ['type' => 'text', 'visible' => ['view'=>true, 'edit'=>true]]);

		$this->field('bank', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true], 'onChangeReload' => true]);

		$this->setFieldOrder([
			'active', 'account_name', 'account_number', 'bank', 'bank_branch_id',
		]);



        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Bank Accounts','Finance');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
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

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
    	switch ($field) {
            case 'active':
                return __('Active');
            case 'bank':
                return __('Bank');
            case 'account_name':
                return __('Account Name');
            case 'account_number':
                return __('Account Number');
            case 'remarks':
                return __('Comments');
            case 'bank_branch_id':
                return __('Bank Branch');
            case 'modified_user_id';
                return __('Modified By');
            case 'modified';
                return __('Modified On');
            case 'created_user_id';
                return __('Created By');
            case 'created';
                return __('Created On');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$this->setFieldOrder([
			'bank', 'bank_branch_id', 'account_name', 'account_number', 'active', 'remarks'
		]);
	}

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
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
			// ->order(['order' => 'ASC'])
			->toArray();

		$this->fields['bank_branch_id']['options'] = $bankBranches;
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['bank']['type'] = 'disabled';
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
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
		if(!empty($this->_selectedBankId)){
			$condition = ['bank_id' => $this->_selectedBankId];
		}else{
			$condition = ['bank_id IS' => null];
		}
		$bankBranches = $this->BankBranches
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->find('visible')
			->where($condition) 
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
			// ->order(['order' => 'ASC'])
			->toArray();
	}

	// POCOR-6160 starts
	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
		$banks = TableRegistry::getTableLocator()->get('FieldOption.Banks');

		$query
		->select([
			$this->aliasField('id'),
			'active' => 'InstitutionBankAccounts.active',
			'account_name' => 'InstitutionBankAccounts.account_name',
			'account_number' => 'InstitutionBankAccounts.account_number',
			'bank' => 'Banks.name',
			'bank_branch' => 'BankBranches.name'
		])
		->LeftJoin([$this->BankBranches->getAlias() => $this->BankBranches->getTable()],[
			$this->BankBranches->aliasField('id ='). 'InstitutionBankAccounts.bank_branch_id'
		])
		->LeftJoin([$banks->getAlias() => $banks->getTable()],[
			$banks->aliasField('id ='). 'BankBranches.bank_id'
		]);
    }
	// POCOR-6160 ends

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->getSession();
        //$institutionId = $session->read('Institution.Institutions.id');
        $institutionId  = $this->getInstitutionID();

		$banks = TableRegistry::getTableLocator()->get('FieldOption.Banks');
		$branches = TableRegistry::getTableLocator()->get('FieldOption.BankBranches');
		
		$query
		->select([
			$this->aliasField('id'),
			'active' => 'InstitutionBankAccounts.active',
			'account_name' => 'InstitutionBankAccounts.account_name',
			'account_number' => 'InstitutionBankAccounts.account_number',
			'bank' => 'Banks.name',
			'bank_branch' => 'BankBranches.name'
		])
		->LeftJoin([$this->BankBranches->getAlias() => $this->BankBranches->getTable()],[
			$this->BankBranches->aliasField('id ='). 'InstitutionBankAccounts.bank_branch_id'
		])
		->LeftJoin([$banks->getAlias() => $banks->getTable()],[
			$banks->aliasField('id ='). 'BankBranches.bank_id'
		])
        ->where([
            $this->aliasField('institution_id ='). $institutionId
        ]);

		// POCOR-6160 active inactive case
		$query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                if($row->active == 1){
                    $row['active'] = 'Active';
                }else{
                    $row['active'] = 'Inactive';
                }

                return $row;
            });
        });
    }

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $extraField[] = [
            'key' => 'InstitutionBankAccounts.active',
            'field' => 'active',
            'type' => 'string',
            'label' => __('Active')
        ];

        $extraField[] = [
            'key' => 'InstitutionBankAccounts.account_name',
            'field' => 'account_name',
            'type' => 'string',
            'label' => __('Name')
        ];

        $extraField[] = [
            'key' => 'InstitutionBankAccounts.account_number',
            'field' => 'account_number',
            'type' => 'integer',
            'label' => __('Account Number')
        ];

        $extraField[] = [
            'key' => 'Banks.name',
            'field' => 'bank',
            'type' => 'string',
            'label' => __('Bank')
        ];

        $extraField[] = [
            'key' => 'BankBranches.name',
            'field' => 'bank_branch',
            'type' => 'string',
            'label' => __('Bank Branch')
        ];


        $fields->exchangeArray($extraField);
    }

    /*public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.add.beforeAction'] = 'addDeleteBeforeAction';
        return $events;
    }

	public function addDeleteBeforeAction(Event $event, ArrayObject $extra)
    {

        $model = $this;
        $url = $model->url('index');
        $institutionID = $this->getInstitutionID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        //$queryString['id'] = $institutionID;
        $queryString = $model->getQueryString();


        unset($queryString['id']);

        $queryString['institution_id'] = $institutionID;
        $url[1] = $model->paramsEncode($queryString);
        $extra['redirect'] = $url;
    }*/
}

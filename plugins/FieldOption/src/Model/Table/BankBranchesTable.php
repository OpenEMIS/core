<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;

class BankBranchesTable extends ControllerActionTable
{
	public function initialize(array $config): void
	{
		$this->setTable('bank_branches');
		parent::initialize($config);

		$this->belongsTo('Banks', ['className' => 'FieldOption.Banks']);
		$this->hasMany('UserBankAccounts', ['className' => 'User.BankAccounts', 'foreignKey' => 'bank_branch_id']);
		$this->hasMany('InstitutionBankAccounts', ['className' => 'Institution.InstitutionBankAccounts', 'foreignKey' => 'bank_branch_id']);
		if ($this->behaviors()->has('Reorder')) {
			// $this->behaviors()->get('Reorder')->config([
			// 	'filter' => 'bank_id',
			// ]);
			$reorderBehavior = $this->behaviors()->get('Reorder');
			$reorderBehavior->setConfig('filter', 'bank_id');
		}

        $this->addBehavior('FieldOption.FieldOption');
	}

	public function validationUpdate($validator)
	{
        $validator
            ->add('name', [
                    'ruleUnique' => [
                        'rule' => 'validateUnique',
                        'provider' => 'table',
                        'message' => __('This field has to be unique')
                    ]
                ])
            ->add('code', [
                    'ruleUnique' => [
                        'rule' => 'validateUnique',
                        'provider' => 'table',
                        'message' => __('This field has to be unique')
                    ]
                ]);

        return $validator;
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('code', ['after' => 'name']);
		$this->field('bank_id', ['visible' => 'false']);
		$this->field('default', ['visible' => 'false']);
		$this->field('editable', ['visible' => 'false']);
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
		$this->field('default', ['visible' => 'false']);
		$this->field('bank_id');
	}

	public function onUpdateFieldBankId(Event $event, array $attr, $action, ServerRequest $request)
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

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default': 
                return __('Default');
            case 'bank_id': 
                return __('Bank');
            case 'code': 
                return __('Code');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}

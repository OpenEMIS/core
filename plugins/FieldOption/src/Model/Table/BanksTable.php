<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class BanksTable extends ControllerActionTable {
	public function initialize(array $config)
	{
		$this->addBehavior('FieldOption.FieldOption');
		parent::initialize($config);
		$this->hasMany('BankBranches', ['className' => 'FieldOption.BankBranches', 'foreignKey' => 'bank_id']);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('code', ['after' => 'name']);
		$this->field('default', ['visible' => 'false']);
		$this->field('editable', ['visible' => 'false']);
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('default', ['visible' => 'false']);
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
}
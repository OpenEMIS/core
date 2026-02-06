<?php
namespace CustomField\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class CustomTableCellsTable extends AppTable
{
	public function initialize(array $config): void
    {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'CustomField.CustomRecords']);
	}

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            // TEXT validation
            ->allowEmpty('text_value')
            // NUMBER validation
            ->allowEmpty('number_value')
            ->add('number_value', 'ruleCustomNumber', [
                'rule' => ['validateCustomNumber'],
                'provider' => 'table',
                'on' => function ($context) {
                    if (array_key_exists('params', $context['data'])) {
                        return !empty($context['data']['params']);
                    }
                }
            ])
            // DECIMAL validation
            ->allowEmpty('decimal_value')
            ->add('decimal_value', 'ruleCustomDecimal', [
                'rule' => ['validateCustomDecimal'],
                'provider' => 'table',
                'on' => function ($context) {
                    if (array_key_exists('params', $context['data'])) {
                        return !empty($context['data']['params']);
                    }
                }
            ]);

        return $validator;
    }
}

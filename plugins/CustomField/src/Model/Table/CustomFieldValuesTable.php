<?php
namespace CustomField\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class CustomFieldValuesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'CustomField.CustomRecords']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->allowEmpty('text_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			->allowEmpty('number_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			->allowEmpty('textarea_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			});
	}
}

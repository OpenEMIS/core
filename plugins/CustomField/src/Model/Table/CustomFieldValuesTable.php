<?php
namespace CustomField\Model\Table;

use Cake\Validation\Validator;
use Cake\Log\Log;
use App\Model\Table\AppTable;

class CustomFieldValuesTable extends AppTable
{
	protected $extra = ['scope' => 'custom_field_id'];

	public function initialize(array $config): void
	{
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'CustomField.CustomRecords']);
	}

	public function validationDefault(Validator $validator): Validator
	{
		$validator = parent::validationDefault($validator);
		$scope = $this->extra['scope'];
		$validator->setProvider('custom', $this);
		$validator
			// TEXT validation
			->allowEmpty('text_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			->add('text_value', 'ruleUnique', [
		        'rule' => function ($value, $context) {
		            // POCOR-8202.Check if uniqueness is required
                    // POCOR-8332 fixed
		            $unique = isset($context['data']['unique']) ? (bool)$context['data']['unique'] : false;
		            // If uniqueness is not required (unique = 0), return true
		            if (!$unique) {
		                return true;
		            }
		            $scope = $context['scope'] ?? [];
		            // Query the database to check for existing records with the same 'text_value'
		            $query = $this->find()->where(['text_value' => $value]);
		            foreach ($scope as $field => $val) {
		                $query->andWhere([$field => $val]);
		            }
		            if (!empty($context['data']['id'])) {
		                $query->andWhere(['id !=' => $context['data']['id']]);
		            }

		            return $query->count() === 0;
		        },
		        'message' => __('This field has to be unique')
		    ])
			->add('text_value', 'ruleCustomText', [
				'rule' => ['validateCustomText'],
				'provider' => 'table',
				'on' => function ($context) {
					if (array_key_exists('params', $context['data'])) {
						return !empty($context['data']['params']);
					}
			    }
			])
			->add('text_value', 'invalidUrl', [
				'rule' => ['url', true],
				'message' => __('You have entered an invalid URL.'),
				'on' => function ($context) {
					if (array_key_exists('params', $context['data']) && !empty($context['data']['params'])) {
						$params = json_decode($context['data']['params'], true);
						return isset($params['url']);
					}
				}
			])
			// NUMBER validation
			->allowEmpty('number_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			->add('number_value', 'ruleUnique', [
			    'rule' => function ($value, $context) {
			        // Check if uniqueness is required
                    // POCOR-8332 fixed
			        $unique = isset($context['data']['unique']) ? (bool) $context['data']['unique'] : false;

			        // If uniqueness is not required, return true
			        if (!$unique) {
			            return true;
			        }
			        $values = is_array($value) ? $value : [$value];
			        foreach ($values as $numberValue) {
			            $query = $this->find()->where(['number_value' => $numberValue]);
			            // Exclude the current record if it's being edited
			            if (!empty($context['data']['id'])) {
			                $query->andWhere(['id !=' => $context['data']['id']]);
			            }

			            // If any value is not unique, return false
			            if ($query->count() !== 0) {
			                return false;
			            }
			        }

			        // All values are unique, return true
			        return true;
			    },
			    'message' => __('All values of this field must be unique')
			])
			->add('number_value', 'ruleCustomNumber', [
				'rule' => ['validateCustomNumber'],
				'provider' => 'table',
				'on' => function ($context) {
					if (array_key_exists('params', $context['data'])) {
						return !empty($context['data']['params']);
					}
			    }
			])
			->add('number_value', 'ruleCheckboxMandatory', [
				'rule' => function ($value, $context) {
					if (empty($context['data']['mandatory'])) {
						return true;
					}
					if (!isset($context['data']['field_type'])
						|| strtoupper($context['data']['field_type']) !== 'CHECKBOX') {
						return true;
					}
					// At least one option must be checked (consent-style: unchecked = not acceptable)
					if (is_array($value)) {
						foreach ($value as $checked) {
							if (!empty($checked)) {
								return true;
							}
						}
						return false;
					}
					return !empty($value);
				},
				'message' => __('Please select at least one option for this required field') //POCOR-6233
			])
			// DECIMAL validation
			->allowEmpty('decimal_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			->add('decimal_value', 'ruleCustomDecimal', [
				'rule' => ['validateCustomDecimal'],
				'provider' => 'table',
				'on' => function ($context) {
					if (array_key_exists('params', $context['data'])) {
						return !empty($context['data']['params']);
					}
			    }
			])
			// TEXTAREA validation
			->allowEmpty('textarea_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			// DATE validation
			->allowEmpty('date_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			->add('date_value', 'ruleCheckDateRange', [
				'rule' => ['checkDateRange'],
				'provider' => 'table',
				'on' => function ($context) {
					if (array_key_exists('params', $context['data'])) {
						return !empty($context['data']['params']);
					}
			    }
			])
			// TIME validation
			->allowEmpty('time_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			->add('time_value', 'ruleCheckTimeRange', [
				'rule' => ['checkTimeRange'],
				'provider' => 'table',
				'on' => function ($context) {
					if (array_key_exists('params', $context['data'])) {
						return !empty($context['data']['params']);
					}
			    }
			])
			// COORDINATES validation
			->add('coordinates_value', 'latitude', [
				'rule' => ['latIsValid'],
				'provider' => 'table'
			])
			->add('coordinates_value', 'longitude', [
				'rule' => ['lngIsValid'],
				'provider' => 'table'
			])
			;

		return $validator;
	}
}

<?php
namespace CustomField\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class CustomFieldValuesTable extends AppTable
{
	protected $extra = ['scope' => 'custom_field_id'];

	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'CustomField.CustomRecords']);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		$scope = $this->extra['scope'];

		$validator
			// TEXT validation
			->allowEmpty('text_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			->add('text_value', 'ruleUnique', [
				'rule' => ['validateUnique', ['scope' => $scope]],
				'provider' => 'table',
				'message' => __('This field has to be unique'),
				'on' => function ($context) {
					if (array_key_exists('unique', $context['data'])) {
						return $context['data']['unique'];
					}
			    }
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
						return array_key_exists('url', $params);
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
				'rule' => ['validateUnique', ['scope' => $scope]],
				'provider' => 'table',
				'message' => __('This field has to be unique'),
				'on' => function ($context) {
					if (array_key_exists('unique', $context['data'])) {
						return $context['data']['unique'];
					}
			    }
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

<?php
namespace CustomField\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class CustomFieldValuesTable extends AppTable {
	protected $extra = ['scope' => 'custom_field_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'CustomField.CustomRecords']);
	}

	public function validationDefault(Validator $validator) {
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

			// DECIMAL validation
			->allowEmpty('decimal_value', function ($context) {
				if (array_key_exists('mandatory', $context['data'])) {
					return !$context['data']['mandatory'];
				}

				return true;
			})
			->decimal('decimal_value', 6, ['message' => true], function ($context) {
				$contextData = $context['data'];
				$field = $contextData['decimal_value'];

				$params = json_decode($contextData['params'], true);
				$length = $params['length'];
				$precision = $params['precision'];

				if ($params['precision'] == 0) {
					// check the field between 1 to length set, and all number
					if (preg_match('/^[0-9]{1,'. $length .'}$/', $field)){
					    return false;
					}
				} else if ($params['precision'] > 0 && strlen($field) <= $length) {
					$pattern = '/^[0-9]+(\.[0-9]{1,'.$precision.'}+)?$/';

					// if precision is not 0
					if (preg_match($pattern, $field)){
					    return false;
					}
				}

				return true;
			})
			;

		return $validator;
	}
}

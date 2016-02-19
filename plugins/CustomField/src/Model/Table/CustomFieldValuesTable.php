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
			;

		return $validator;
	}
}

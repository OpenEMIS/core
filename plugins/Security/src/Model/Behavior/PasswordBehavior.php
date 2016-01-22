<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

class PasswordBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();

		// priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
		// $events['ControllerAction.Model.index.beforePaginate'] = 'indexBeforePaginate';
		return $events;
	}

	public function buildValidator(Event $event, Validator $validator, $name) {
		$ConfigItems = TableRegistry::get('ConfigItems');

		$passwordMinLength = $ConfigItems->value('password_min_length');
		$passwordHasUppercase = $ConfigItems->value('password_has_uppercase');
		$passwordHasNumber = $ConfigItems->value('password_has_number');
		$passwordHasNonAlpha = $ConfigItems->value('password_has_non_alpha');

		$validator->allowEmpty('password');

		$validator->add('password' , [
			'ruleCheckLength' => [
				'rule'	=> ['lengthBetween', $passwordMinLength, 50],
				'message' => $this->_table->getMessage('User.Users.password.ruleCheckLength', ['vsprintf' => [$passwordMinLength,50]]),
				'last' => true
			]
		]);

		$validator->add('password' , [
			'ruleNoSpaces' => [
				'rule' => 'checkNoSpaces',
				'message' => $this->_table->getMessage('User.Users.password.ruleNoSpaces'),
				'provider' => 'custom'
			],
		]);

		if ($passwordHasUppercase) {
			$validator->add('password' , [
				'ruleCheckUppercaseExists' => [
					'rule' => 'checkUppercaseExists',
					'message' => $this->_table->getMessage('User.Users.password.ruleCheckUppercaseExists'),
					'provider' => 'custom'
				]
			]);
		}
		if ($passwordHasNumber) {
			$validator->add('password' , [
				'ruleCheckNumberExists' => [
					'rule' => 'checkNumberExists',
					'message' => $this->_table->getMessage('User.Users.password.ruleCheckNumberExists'),
					'provider' => 'custom'
				]
			]);
		}
		if ($passwordHasNonAlpha) {
			$validator->add('password' , [
				'ruleCheckNonAlphaExists' => [
					'rule' => 'checkNonAlphanumericExists',
					'message' => $this->_table->getMessage('User.Users.password.ruleCheckNonAlphaExists'),
					'provider' => 'custom'
				]
			]);
		}
	}

}
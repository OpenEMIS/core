<?php
namespace Security\Model\Behavior;

use ArrayObject;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

class PasswordBehavior extends Behavior {
	private $targetField = null;
	private $checkOwnPassword = false;
	private $passwordAllowEmpty = false;
	private $createRetype = false;

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
        $events['Model.buildValidator'] = ['callable' => 'buildValidator', 'priority' => 5];
		return $events;
	}

	public function initialize(array $config) {
		$this->targetField = $config['field'];
		$this->checkOwnPassword = (array_key_exists('checkOwnPassword', $config))? $config['checkOwnPassword']: $this->checkOwnPassword;
		$this->passwordAllowEmpty = (array_key_exists('passwordAllowEmpty', $config))? $config['passwordAllowEmpty']: $this->passwordAllowEmpty;
		$this->createRetype = (array_key_exists('createRetype', $config))? $config['createRetype']: $this->createRetype;
	}

	public function buildValidator(Event $event, Validator $validator, $name) {
		$ConfigItems = TableRegistry::get('Configuration.ConfigItems');

		$passwordMinLength = $ConfigItems->value('password_min_length');
		$passwordHasUppercase = $ConfigItems->value('password_has_uppercase');
		$passwordHasLowercase = $ConfigItems->value('password_has_lowercase');
		$passwordHasNumber = $ConfigItems->value('password_has_number');
		$passwordHasNonAlpha = $ConfigItems->value('password_has_non_alpha');

		$validator = $validator
			->add('username', [
                'ruleMinLength' => [
                    'rule' => ['minLength', 6],
                    'on' => function ($context) {
						return ($context['data']['username'] != 'admin');
					},
                ],
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				],
				'ruleCheckUsername' => [
					'rule' => 'checkUsername',
					'provider' => 'table',
				]
			])
			// password validation now in behavior
			->add('retype_password' , [
				'ruleCompare' => [
					'rule' => ['comparePasswords', $this->targetField],
					'on' => 'update'
				]
			])
			;
        
        $this->_table->setValidationCode('username.ruleMinLength', 'User.Accounts');
		$this->_table->setValidationCode('username.ruleUnique', 'User.Accounts');
		$this->_table->setValidationCode('username.ruleCheckUsername', 'User.Accounts');
		$this->_table->setValidationCode('retype_password.ruleCompare', 'User.Accounts');
		
		if ($this->passwordAllowEmpty) {
			$validator->allowEmpty($this->targetField);
			$validator->allowEmpty('retype_password');
		}

		$validator->add($this->targetField, [
			'ruleCheckLength' => [
				'rule'	=> ['lengthBetween', $passwordMinLength, 50],
				'message' => $this->_table->getMessage('User.Users.password.ruleCheckLength', ['sprintf' => [$passwordMinLength,50]]),
				'last' => true
			]
		]);

		$validator->add($this->targetField, [
			'ruleNoSpaces' => [
				'rule' => 'checkNoSpaces',
				'message' => $this->_table->getMessage('User.Users.password.ruleNoSpaces'),
				'provider' => 'custom'
			],
		]);

		if ($passwordHasUppercase) {
			$validator->add($this->targetField, [
				'ruleCheckUppercaseExists' => [
					'rule' => 'checkUppercaseExists',
					'message' => $this->_table->getMessage('User.Users.password.ruleCheckUppercaseExists'),
					'provider' => 'custom'
				]
			]);
		}
		if ($passwordHasLowercase) {
			$validator->add($this->targetField, [
				'ruleCheckLowercaseExists' => [
					'rule' => 'checkLowercaseExists',
					'message' => $this->_table->getMessage('User.Users.password.ruleCheckLowercaseExists'),
					'provider' => 'custom'
				]
			]);
		}
		if ($passwordHasNumber) {
			$validator->add($this->targetField, [
				'ruleCheckNumberExists' => [
					'rule' => 'checkNumberExists',
					'message' => $this->_table->getMessage('User.Users.password.ruleCheckNumberExists'),
					'provider' => 'custom'
				]
			]);
		}
		if ($passwordHasNonAlpha) {
			$validator->add($this->targetField, [
				'ruleCheckNonAlphaExists' => [
					'rule' => 'checkNonAlphanumericExists',
					'message' => $this->_table->getMessage('User.Users.password.ruleCheckNonAlphaExists'),
					'provider' => 'custom'
				]
			]);
		}

		if ($this->checkOwnPassword) {
			$validator = $validator
				->add('password', [
					'ruleChangePassword' => [
						'rule' => ['checkUserPassword', $this->_table],
						'provider' => 'table',
					]
				]);
			$this->_table->setValidationCode('password.ruleChangePassword', 'User.Accounts');
		}
	}

	public static function checkUserPassword($field, $model, array $globalData) {
		$Users = TableRegistry::get('User.Users');
		return ((new DefaultPasswordHasher)->check($field, $model->get($model->Auth->user('id'))->password));
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($this->checkOwnPassword) {
			$entity->password = $entity->{$this->targetField};
		}
	}

	public function editAfterAction(Event $event, Entity $entity)  {
		if ($this->checkOwnPassword) {
			$this->_table->ControllerAction->field($this->targetField, ['type' => 'password', 'attr' => ['value' => '']]);
		}
		
		if ($this->createRetype) {
			$this->_table->ControllerAction->field('retype_password', ['type' => 'password', 'attr' => ['value' => '']]);
		}
	}
}
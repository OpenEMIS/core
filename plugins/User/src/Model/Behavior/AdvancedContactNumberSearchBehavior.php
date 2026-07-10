<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\Database\Expression\UnaryExpression;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AdvancedContactNumberSearchBehavior extends Behavior {
	//POCOR-9644
	protected $_defaultConfig = [
		'associatedKey' => null,
	];
	//POCOR-9644

	public function initialize(array $config): void {
		parent::initialize($config);
		$associatedKey = $this->getConfig('associatedKey');
		if ($associatedKey === null || $associatedKey === '') {
			$this->setConfig('associatedKey', $this->_table->aliasField('id')); // POCOR-9500
		}
	}

	/**
	 * Advanced "Contact Number" search checks both:
	 * - user_contacts.value (all contact rows; security_user_id → user)
	 * - security_users.mobile_number when the main model maps to that table (Directory/Users), i.e. schema has mobile_number
	 *
	 * Models whose main table has no mobile_number (e.g. institution_students) only use user_contacts.
	 */
	//POCOR-9644
	public function onBuildQuery(EventInterface $event, Query $query, $advancedSearchHasMany)
	{
		$search = $advancedSearchHasMany['contact_number'] ?? '';
		if (strlen((string) $search) === 0) {
			return $query;
		}

		$searchString = '%' . $search . '%';
		$linkField = $this->getConfig('associatedKey');
		$Contacts = TableRegistry::getTableLocator()->get('User.Contacts');

		// 1) user_contacts: match any contact value (phone, mobile, etc.) for this user
		$userContactsMatchSubquery = $Contacts->find()
			->select([$Contacts->aliasField('id')])
			->where([$Contacts->aliasField('value LIKE') => $searchString])
			->where(function ($exp, $q) use ($Contacts, $linkField) {
				return $exp->equalFields($Contacts->aliasField('security_user_id'), $linkField);
			});

		// UnaryExpression: $exp->exists() mutates $exp; for OR branches we need a standalone EXISTS expression.
		$existsUserContacts = new UnaryExpression('EXISTS', $userContactsMatchSubquery, UnaryExpression::PREFIX);

		$query->where(function ($exp, $q) use ($searchString, $existsUserContacts) {
			// 2) security_users: match denormalised mobile on the user row (same physical table as Directory/Users)
			if ($this->_table->getSchema()->hasColumn('mobile_number')) {
				return $exp->or([
					[$this->_table->aliasField('mobile_number') . ' LIKE' => $searchString],
					$existsUserContacts,
				]);
			}

			return $existsUserContacts;
		});

		return $query;
	}
	//POCOR-9644

	public function implementedEvents(): array {
		$events = parent::implementedEvents();
		$newEvent = [
			'AdvanceSearch.onSetupFormField' => 'onSetupFormField',
			'AdvanceSearch.onBuildQuery' => 'onBuildQuery',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onSetupFormField(EventInterface $event, ArrayObject $searchables, $advanceSearchModelData) {
		$searchables['contact_number'] = [
			'label' => __('Contact Number'),
			'value' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['contact_number'])) ? $advanceSearchModelData['hasMany']['contact_number'] : '',
		];
	}

	public function onGetContactNumbers(EventInterface $event, Entity $entity) {
		$userId = $entity->id;
		$Contacts = TableRegistry::getTableLocator()->get('User.Contacts');
		$studentContacts = $Contacts->find()
			->contain(['ContactTypes'])
			->where([
				$Contacts->aliasField('security_user_id') => $userId
			])
			->toArray();

		if (!empty($studentContacts)) {
			foreach ($studentContacts as $key => $value) {
				$value = $value->value.'<br/>';
			}
		} else {
			$value = '';
		}
		return $value;
	}

}

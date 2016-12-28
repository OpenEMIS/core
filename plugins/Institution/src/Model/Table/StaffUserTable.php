<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Network\Session;
use Staff\Model\Table\StaffTable as UserTable;

class StaffUserTable extends UserTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Staff' => ['index', 'add']
        ]);
    }

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('username', ['visible' => false]);
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ;
        return $validator;
    }

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$sessionKey = 'Institution.Staff.new';
		if ($this->Session->check($sessionKey)) {
			$positionData = $this->Session->read($sessionKey);
			$positionData['staff_id'] = $entity->id;
			$institutionId = $positionData['institution_id'];

			$Staff = TableRegistry::get('Institution.Staff');
			$staffEntity = $Staff->newEntity($positionData, ['validate' => 'AllowEmptyName']);
			if (!$Staff->save($staffEntity)) {
				$errors = $staffEntity->errors();
				if (isset($errors['institution_position_id']['ruleCheckFTE'])) {
					$this->Alert->error('Institution.InstitutionStaff.noFTE', ['reset' => true]);
				} else {
					$this->Alert->error('Institution.InstitutionStaff.error', ['reset' => true]);
				}
			}
			$this->Session->delete($sessionKey);
		}
		$event->stopPropagation();
		$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Staff', 'index'];
		return $this->controller->redirect($action);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		if (!$this->AccessControl->isAdmin()) {
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
		}
		$this->Session->write('Staff.Staff.id', $entity->id);
		$this->Session->write('Staff.Staff.name', $entity->name);
		$this->setupTabElements($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->Session->write('Staff.Staff.id', $entity->id);
		$this->Session->write('Staff.Staff.name', $entity->name);
		$this->setupTabElements($entity);

		$this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
	}

	private function setupTabElements($entity) {
		$id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;
		$options = [
			'userRole' => 'Staff',
			'action' => $this->action,
			'id' => $id,
			'userId' => $entity->id
		];

		$tabElements = $this->controller->getUserTabElements($options);

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			$id = $this->request->query('id');
			$this->Session->write('Institution.Staff.id', $id);
			$session = $this->request->session();
			if ($toolbarButtons->offsetExists('back')) {
				unset($toolbarButtons['back']);
			}
		} else if ($action == 'add') {
			$backAction = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Staff', 'add'];
			$toolbarButtons['back']['url'] = $backAction;

			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}

	//to handle identity_number field that is automatically created by mandatory behaviour.
	public function onUpdateFieldIdentityNumber(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add') {
			$attr['fieldName'] = $this->alias().'.identities.0.number';
			$attr['attr']['label'] = __('Identity Number');
		}
		return $attr;
	}

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        foreach ($fields as $key => $field) {
            //get the value from the table, but change the label to become default identity type.
            if ($field['field'] == 'identity_number') {
                $fields[$key] = [
                    'key' => 'StudentUser.identity_number',
                    'field' => 'identity_number',
                    'type' => 'string',
                    'label' => __($identity->name)
                ];
                break;
            }
        }
    }

    public function findStaff(Query $query, array $options = []) {
        $query->where([$this->aliasField('super_admin').' <> ' => 1]);

        $limit = (array_key_exists('limit', $options))? $options['limit']: null;
        $page = (array_key_exists('page', $options))? $options['page']: null;

        // conditions
        $firstName = (array_key_exists('first_name', $options))? $options['first_name']: null;
        $lastName = (array_key_exists('last_name', $options))? $options['last_name']: null;
        $openemisNo = (array_key_exists('openemis_no', $options))? $options['openemis_no']: null;
        $identityNumber = (array_key_exists('identity_number', $options))? $options['identity_number']: null;
        $dateOfBirth = (array_key_exists('date_of_birth', $options))? $options['date_of_birth']: null;

        if (is_null($firstName) && is_null($lastName) && is_null($openemisNo) && is_null($identityNumber) && is_null($dateOfBirth)) {
            return $query->where(['1 = 0']);
        }

        $conditions = [];
        if (!empty($firstName)) $conditions['first_name LIKE'] = $firstName . '%';
        if (!empty($lastName)) $conditions['last_name LIKE'] = $lastName . '%';
        if (!empty($openemisNo)) $conditions['openemis_no LIKE'] = $openemisNo . '%';
        if (!empty($dateOfBirth)) $conditions['date_of_birth'] = $dateOfBirth;

        $identityConditions = [];
        if (!empty($identityNumber)) $identityConditions['Identities.number LIKE'] = $identityNumber . '%';

        $identityJoinType = (empty($identityNumber))? 'LEFT': 'INNER';
        $default_identity_type = $this->Identities->IdentityTypes->getDefaultValue();
        $query->join([
            [
                'type' => $identityJoinType,
                'table' => 'user_identities',
                'alias' => 'Identities',
                'conditions' => array_merge([
                        'Identities.security_user_id = ' . $this->aliasField('id'),
                        'Identities.identity_type_id' => $default_identity_type
                    ], $identityConditions)
            ]
        ]);

        $query->group([$this->aliasField('id')]);

        if (!empty($conditions)) $query->where($conditions);
        if (!is_null($limit)) $query->limit($limit);
        if (!is_null($page)) $query->page($page);

        return $query;
    }
}

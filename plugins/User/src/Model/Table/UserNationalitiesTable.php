<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\OptionsTrait;

class UserNationalitiesTable extends ControllerActionTable {
    use OptionsTrait;
    use MessagesTrait;

	public function initialize(array $config)
    {
        parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('NationalitiesLookUp', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);

        $this->securityUserId = $this->getQueryString('security_user_id');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);

        $this->addBehavior('CompositeKey');
	}

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.Users.afterSave' => 'afterSaveUsers'
        ];

        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function afterSaveUsers(Event $event, Entity $entity)
    {
        //check whether the combination user and nationality exist
        $query = $this->find()
                ->where([
                    $this->aliasField('security_user_id') => $entity->id,
                    $this->aliasField('nationality_id') => $entity->nationality_id
                ]);

        if ($query->count()) { //if exist then set as preferred.

            //use save instead of update to trigger after save events
            $userNationalityEntity = $this->patchEntity($query->first(), ['preferred' => 1], ['validate' =>false]);
            $this->save($userNationalityEntity);
            
        } else { //not exist then add new record and set as preferred.
            $userNationalityEntity = $this->newEntity([
                'preferred' => 1,
                'nationality_id' => $entity->nationality_id,
                'security_user_id' => $entity->id,
                'created_user_id' => 1,
                'created' => new Time()
            ]);
            $this->save($userNationalityEntity);
        }
    }

	public function beforeAction(Event $event) {
		$this->fields['nationality_id']['type'] = 'select';
        $this->setFieldOrder([
            'nationality_id', 'comment', 'preferred'
        ]);
	}

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator
            ->add('nationality_id', 'notBlank', ['rule' => 'notBlank'])
            ->add('preferred', 'ruleValidatePreferredNationality', [
                'rule' => ['validatePreferredNationality'],
                'provider' => 'table'
            ]);
	}

	public function validationNonMandatory(Validator $validator) {
		$validator = $this->validationDefault($validator);
		return $validator->allowEmpty('nationality_id');
	}

	public function validationAddByAssociation(Validator $validator)
	{
		$validator = $this->validationDefault($validator);
		return $validator->requirePresence('security_user_id', false);
	}

	private function setupTabElements() 
    {
        if ($this->controller->name == 'Scholarships') {
            $tabElements = $this->ScholarshipTabs->getScholarshipApplicationTabs();
        } else {
        	$options = [
    			'userRole' => '',
    		];

    		switch ($this->controller->name) {
    			case 'Students':
    				$options['userRole'] = 'Students';
    				break;
    			case 'Staff':
    				$options['userRole'] = 'Staff';
    				break;
    		}

            $tabElements = $this->controller->getUserTabElements($options);
        }
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'NationalitiesLookUp'
        ]);
    }

	public function afterAction(Event $event) {
		$this->setupTabElements();
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            if (!$this->exists([$this->aliasField('security_user_id') => $entity->security_user_id])) { // user does not have existing nationality record
                $entity->preferred = 1;
            }
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->dirty('preferred')) {
            if ($entity->preferred == 1) { //if set as preferred
                // update the rest of user nationality to not preferred
                $this->updateAll(
                    ['preferred' => 0],
                    [
                        'security_user_id' => $entity->security_user_id,
                        'id <> ' => $entity->id
                    ]
                );

                //update information on security user table
                $listeners = [
                    TableRegistry::get('User.Users')
                ];
                $this->dispatchEventToModels('Model.UserNationalities.onChange', [$entity], $this, $listeners);
            }
        }
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        //check whether has minimum one nationality record.
        $query = $this
                ->find()
                ->where([
                    $this->aliasfield('security_user_id') => $entity->security_user_id,
                    $this->aliasfield('id <> ') => $entity->id
                ])
                ->count();

        if (!$query) {
            $this->Alert->warning('UserNationalities.noRecordRemain', ['reset'=>true]);
            return false;
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->preferred == 1) { //if the preferred nationality deleted

            //get the next latest nationality to be set as preferred
            $query = $this->find()
                    ->where([
                        $this->aliasfield('security_user_id') => $entity->security_user_id
                    ])
                    ->order('created DESC')
                    ->first();

            if (!empty($query)) {
                $query->preferred = 1;
                $this->save($query);
                $entity->nationality_id = $query->nationality_id; //send the new preferred nationality

                //update information on security user table
                $listeners = [
                    TableRegistry::get('User.Users')
                ];
                $this->dispatchEventToModels('Model.UserNationalities.onChange', [$entity], $this, $listeners);
            }
        }
    }

    public function onUpdateFieldNationalityId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {
                $currentNationalities = $this
                                        ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                                        ->matching('NationalitiesLookUp')
                                        ->where([
                                            $this->aliasfield('security_user_id') => $this->securityUserId
                                        ])
                                        ->select([
                                            'id' => $this->NationalitiesLookUp->aliasfield('id')
                                        ])
                                        ->toArray();

                $nationalities = $this->NationalitiesLookUp->find('visible')->find('list');

                if (!empty($currentNationalities)) {
                    $nationalities = $nationalities
                                    ->where([
                                        $this->NationalitiesLookUp->aliasfield('id NOT IN ') => $currentNationalities
                                    ]);
                }

                $nationalities = $nationalities->toArray();
                $attr['options'] = $nationalities;
            } else if ($action == 'edit') {
                $entity = $attr['entity'];
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->nationality_id;
                $attr['attr']['value'] = $entity->nationalities_look_up->name;
            }
        }
        return $attr;
    }

    public function onGetPreferred(Event $event, Entity $entity) {
        $preferredOptions = $this->getSelectOptions('general.yesno');
        return $preferredOptions[$entity->preferred];
    }

    public function onUpdateFieldPreferred(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('nationality_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('preferred', [
            'type' => 'select',
            'select' => false,
            'entity' => $entity
        ]);
    }
}

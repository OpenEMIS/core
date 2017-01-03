<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class UserNationalitiesTable extends ControllerActionTable {
    use MessagesTrait;

	public function initialize(array $config) 
    {
        parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('NationalitiesLookUp', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);

        $this->securityUserId = $this->getQueryString('security_user_id');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add']
        ]);
	}

	public function beforeAction(Event $event) {
		$this->fields['nationality_id']['type'] = 'select';
	}

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);    
    }

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator
            ->add('nationality_id', 'notBlank', ['rule' => 'notBlank'])
            ->add('nationality_id', 'ruleUniqueNationality', [
                'rule' => 'validateUniqueNationality',
                'on' => function ($context) {
                    if ($this->action == 'edit') { //trigger this only during edit
                        $originalNationality = $this->get($context['data']['id'])->nationality_id;
                        $newNationality = $context['data']['nationality_id'];
                        return $originalNationality != $newNationality; //only trigger validation if there is any changes on the code value.
                    } else if ($this->action == 'add') { //during add, then validation always needed.
                        return true;
                    }
                }
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

	private function setupTabElements() {
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
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event) {
		$this->setupTabElements();
    }

    public function onUpdateFieldNationalityId(Event $event, array $attr, $action, Request $request)
    {
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
        
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'edit') { //when edit then include the nationality that is being edited.
                $nationalityId[] = $attr['entity']->nationality_id;
                $currentNationalities = array_diff($currentNationalities, $nationalityId);
            }

            $nationalities = $this->NationalitiesLookUp->find('visible')->find('list');

            if (!empty($currentNationalities)) {
                $nationalities = $nationalities
                                    ->where([
                                        $this->NationalitiesLookUp->aliasfield('id NOT IN ') => $currentNationalities
                                    ]);
            }

            $nationalities = $nationalities->toArray();
            $attr['options'] = $nationalities;
        }
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('nationality_id', [
            'type' => 'select', 
            'entity' => $entity
        ]);
    }
}

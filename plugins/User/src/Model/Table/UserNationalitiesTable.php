<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Session;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

class UserNationalitiesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('NationalitiesLookUp', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
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
		return $validator->add('nationality_id', 'notBlank', ['rule' => 'notBlank']);
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
        //get the current user nationality
        $currentNationalities = $this
                                ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                                ->matching('NationalitiesLookUp')
                                ->where([
                                    $this->aliasfield('security_user_id') => $this->Session->read('Student.Students.id')
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

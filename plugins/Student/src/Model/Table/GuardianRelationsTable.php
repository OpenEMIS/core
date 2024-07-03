<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class GuardianRelationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('guardian_relations');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->hasMany('StudentGuardians', ['className' => 'Student.StudentGuardians']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function validationDefault(Validator $validator): Validator
    {

        $validator = parent::validationDefault($validator);

        return $validator
                ->allowEmpty('gender_id')
                ->add('gender_id', 'ruleCheckGuardianGender', [
    				'rule' => ['checkGuardianGender'],
    				'provider' => 'table',
    				'on' => function ($context) {
    					 //trigger validation only when gender is set and is in edit
                        return ($context['data']['gender_id'] && !$context['newRecord']);
    			   }
                ]);
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$this->field('gender_id', ['after' => 'name']);
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['gender_id']['type'] = 'select';
	}

    public function getAvailableGuardianRelations($guardianGenderId = null)
    {
        $list = [];

        if (!is_null($guardianGenderId)) {
            $list = $this->find('list')
                ->where([
                    'OR' => [
                        $this->aliasField('gender_id') => $guardianGenderId,
                        $this->aliasField('gender_id') . ' IS NULL'
                    ]
                ])
                ->toArray();
        } else {
             $list = $this->find('list')->toArray();
        }

        return $list;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}

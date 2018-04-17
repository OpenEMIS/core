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
    public function initialize(array $config)
    {
        $this->table('guardian_relations');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->hasMany('StudentGuardians', ['className' => 'Student.StudentGuardians']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function validationDefault(Validator $validator)
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
                ->where([$this->aliasField('gender_id') => $guardianGenderId])
                ->orWhere([$this->aliasField('gender_id') . ' IS NULL'])
                ->toArray();
        } else {
             $list = $this->find('list')->toArray();
        }

        return $list;
    }
}

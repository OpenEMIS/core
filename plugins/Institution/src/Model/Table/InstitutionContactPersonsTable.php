<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionContactPersonsTable extends AppTable {
    
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('telephone')
            ->add('telephone', 'ruleCustomTelephone', [
                    'rule' => ['validateCustomPattern', 'institution_contact_person_telephone'],
                    'provider' => 'table',
                    'last' => true
                ])

            ->allowEmpty('mobile_number')
            ->add('mobile_number', 'ruleCustomMobile', [
                    'rule' => ['validateCustomPattern', 'institution_contact_person_mobile'],
                    'provider' => 'table',
                    'last' => true
                ])

            ->allowEmpty('fax')
            ->add('fax', 'ruleCustomFax', [
                    'rule' => ['validateCustomPattern', 'institution_contact_person_fax'],
                    'provider' => 'table',
                    'last' => true
                ])
            ->notEmpty('preferred')
            ->allowEmpty('email')
            ->add('email', [
                    'ruleValidEmail' => [
                        'rule' => 'email'
                    ]
                ]);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->dirty('preferred')) {
            if ($entity->preferred == 1) { 

                $this->updateAll(
                    ['preferred' => 0],
                    ['id <> ' => $entity->id]
                 );

                $this->Institutions->updateAll(
                    ['contact_person' => $entity->contact_person],
                    ['id' => $entity->institution_id]
                 );
            } else {

                $query = $this->find()
                        ->where(['preferred' => 1])
                        ->first();

                if(!$query) {
                    $this->Institutions->updateAll(
                        ['contact_person' => null],
                        ['id' => $entity->institution_id]
                     );
                }
            }
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $query = $this->find()
                        ->where(['preferred' => 1])
                        ->first();

        if(!$query) {
            $this->Institutions->updateAll(
                ['contact_person' => null],
                ['id' => $entity->institution_id]
            );
        }
    }
}
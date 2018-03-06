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
                'provider' => 'table'
            ])
            ->allowEmpty('mobile_number')
            ->add('mobile_number', 'ruleCustomMobile', [
                'rule' => ['validateCustomPattern', 'institution_contact_person_mobile'],
                'provider' => 'table'
            ])
            ->allowEmpty('fax')
            ->add('fax', 'ruleCustomFax', [
                'rule' => ['validateCustomPattern', 'institution_contact_person_fax'],
                'provider' => 'table'
            ])
            ->allowEmpty('email')
            ->add('email', [
                'ruleValidEmail' => [
                    'rule' => 'email'
                ]
            ])
            ->requirePresence('preferred');
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->dirty('preferred')) {
            $institutionId = $entity->institution_id;

            if ($entity->preferred == 1) {
                $this->updateAll(
                    ['preferred' => 0],
                    [
                        'institution_id' => $institutionId,
                        'id <> ' => $entity->id
                    ]
                 );

                $this->Institutions->updateAll(
                    ['contact_person' => $entity->contact_person],
                    ['id' => $institutionId]
                );
            } else {
                $results = $this->find()
                    ->where([
                        'institution_id' => $institutionId,
                        'preferred' => 1
                    ])
                    ->all();

                if ($results->isEmpty()) {
                    $this->Institutions->updateAll(
                        ['contact_person' => null],
                        ['id' => $institutionId]
                    );
                }
            }
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->preferred == 1) {
            $this->Institutions->updateAll(
                ['contact_person' => null],
                ['id' => $entity->institution_id]
            );
        }
    }
}

<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
class Identities extends Entity {

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('issue_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'expiry_date', false]
            ])
            ->add('expiry_date', [
            ])
            ->add('identity_type_id', 'ruleCustomIdentityType', [
                'rule' => ['validateCustomIdentityType'],
                'provider' => 'table',
            ])
            ->add('number', 'ruleCustomIdentityNumber', [
                'rule' => ['validateCustomIdentityNumber'],
                'provider' => 'table',
                'last' => true
            ])
            ->add('number', [
                'ruleUnique' => [
                    'rule' => ['validateUnique', ['scope' => 'identity_type_id']],
                    'provider' => 'table'
                ]
            ])
            //POCOR-5987 starts
            ->notEmpty('nationality_id');
        //POCOR-5987 ends
    }   
}

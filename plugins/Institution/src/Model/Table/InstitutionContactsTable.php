<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionContactsTable extends AppTable {
    
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
                    'rule' => ['validateCustomPattern', 'institution_contact_telephone'],
                    'provider' => 'table',
                    'last' => true
                ])

            ->allowEmpty('mobile_number')
            ->add('mobile_number', 'ruleCustomMobile', [
                    'rule' => ['validateCustomPattern', 'institution_contact_mobile'],
                    'provider' => 'table',
                    'last' => true
                ])

            ->allowEmpty('fax')
            ->add('fax', 'ruleCustomFax', [
                    'rule' => ['validateCustomPattern', 'institution_contact_fax'],
                    'provider' => 'table',
                    'last' => true
                ])
            ->allowEmpty('email')
            ->add('email', [
                    'ruleValidEmail' => [
                        'rule' => 'email'
                    ]
                ]);
    }
}
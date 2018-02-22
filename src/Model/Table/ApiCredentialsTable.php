<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ApiCredentialsTable extends AppTable
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsToMany('ApiScopes', [
            'className' => 'ApiScopes',
            'joinTable' => 'api_credentials_scopes',
            'foreignKey' => 'api_credential_id',
            'targetForeignKey' => 'api_scope_id',
            'through' => 'ApiCredentialsScopes'
        ]);

        // $this->hasMany('ApiCredentialsScopes', [
        //     'className' => 'ApiCredentialsScopes',
        //     'foreignKey' => 'api_credential_id'
        // ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        parent::validationDefault($validator);
        $validator
            ->requirePresence('name', 'create')
            ->notEmpty('name')
            ->requirePresence('public_key', 'create')
            ->notEmpty('public_key');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['client_id']));

        return $rules;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $data['api_scopes'] = [
            '_ids' => array($data['api_scopes'])
        ];
        // $data['scope'] = 'API';
    }
}

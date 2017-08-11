<?php
namespace OAuth\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

/**
 * Credentials Model
 *
 *
 * @method \OAuth\Model\Entity\Credential get($primaryKey, $options = [])
 * @method \OAuth\Model\Entity\Credential newEntity($data = null, array $options = [])
 * @method \OAuth\Model\Entity\Credential[] newEntities(array $data, array $options = [])
 * @method \OAuth\Model\Entity\Credential|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \OAuth\Model\Entity\Credential patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \OAuth\Model\Entity\Credential[] patchEntities($entities, array $data, array $options = [])
 * @method \OAuth\Model\Entity\Credential findOrCreate($search, callable $callback = null, $options = [])
 *
 */
class CredentialsTable extends AppTable
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
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
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
}

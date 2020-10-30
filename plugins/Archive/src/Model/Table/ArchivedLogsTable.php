<?php
namespace Archive\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ArchivedLogs Model
 *
 * @method \Archive\Model\Entity\ArchivedLog get($primaryKey, $options = [])
 * @method \Archive\Model\Entity\ArchivedLog newEntity($data = null, array $options = [])
 * @method \Archive\Model\Entity\ArchivedLog[] newEntities(array $data, array $options = [])
 * @method \Archive\Model\Entity\ArchivedLog|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Archive\Model\Entity\ArchivedLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Archive\Model\Entity\ArchivedLog[] patchEntities($entities, array $data, array $options = [])
 * @method \Archive\Model\Entity\ArchivedLog findOrCreate($search, callable $callback = null, $options = [])
 */class ArchivedLogsTable extends Table
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
        
        $this->table('archived_logs');
        $this->displayField('id');
        $this->primaryKey('id');
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
            ->integer('id')            ->allowEmpty('id', 'create');
        $validator
            ->requirePresence('file', 'create')            ->notEmpty('file');
        $validator
            ->dateTime('generated_on')            ->requirePresence('generated_on', 'create')            ->notEmpty('generated_on');
        $validator
            ->requirePresence('generated_by', 'create')            ->notEmpty('generated_by');
        return $validator;
    }
}

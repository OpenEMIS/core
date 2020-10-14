<?php
namespace Archive\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Deletes Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Archives
 *
 * @method \Archive\Model\Entity\Delete get($primaryKey, $options = [])
 * @method \Archive\Model\Entity\Delete newEntity($data = null, array $options = [])
 * @method \Archive\Model\Entity\Delete[] newEntities(array $data, array $options = [])
 * @method \Archive\Model\Entity\Delete|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Archive\Model\Entity\Delete patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Archive\Model\Entity\Delete[] patchEntities($entities, array $data, array $options = [])
 * @method \Archive\Model\Entity\Delete findOrCreate($search, callable $callback = null, $options = [])
 */class DeletesTable extends Table
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

        $this->table('deletes');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->belongsTo('Archives', [
            'foreignKey' => 'archives_id',
            'joinType' => 'INNER',
            'className' => 'Archive.Archives'
        ]);
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
            ->requirePresence('academic_period', 'create')            ->notEmpty('academic_period');
        $validator
            ->dateTime('generated_on')            ->requirePresence('generated_on', 'create')            ->notEmpty('generated_on');
        $validator
            ->requirePresence('generated_by', 'create')            ->notEmpty('generated_by');
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
        $rules->add($rules->existsIn(['archives_id'], 'Archives'));

        return $rules;
    }
}

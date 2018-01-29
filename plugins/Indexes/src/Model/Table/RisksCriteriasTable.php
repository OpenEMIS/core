<?php
namespace Indexes\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;


class RisksCriteriasTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('indexes');
        
        parent::initialize($config);
        $this->belongsTo('Indexes', ['className' => 'Indexes.Risks', 'foreignKey' =>'index_id']);

        $this->hasMany('StudentIndexesCriterias', ['className' => 'Indexes.StudentIndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('index_value', [
                'ruleRange' => [
                    'rule' => ['range', 1, 99]
                ]
            ])
            ->add('threshold', 'ruleCheckCriteriaThresholdRange', [
                'rule' => ['checkCriteriaThresholdRange']
            ])
            ;
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $indexesCriteriaId = $entity->id;
        $this->StudentIndexesCriterias->deleteAll(['indexes_criteria_id' => $indexesCriteriaId]);
    }

    public function findActiveIndexesCriteria(Query $query, array $options)
    {
        $InstitutionIndexes = TableRegistry::get('Institution.InstitutionIndexes');

        $activeIndexId = [];
        $activeIndexesData = $InstitutionIndexes->find()
            ->where([
                'institution_id' => $options['institution_id'],
                'OR' => [
                    ['status' => 2], // status == processing
                    ['status' => 3]  // status == completed
                ]
            ])
            ->all();

        foreach ($activeIndexesData as $activeIndexes) {
            $activeIndexId [] = $activeIndexes->index_id;
        }

        return $query->contain('Indexes')
            ->where([
                'criteria' => $options['criteria_key'],
                $this->Indexes->aliasField('id') . ' IN ' => $activeIndexId
            ])
            ->all();
    }

    public function getTotalIndex($indexId)
    {
        $indexCriteriasResults = $this->find()
            ->where([$this->aliasField('index_id') => $indexId])
            ->toArray();

        $indexTotal = 0;
        if (!empty($indexCriteriasResults)) {
            foreach ($indexCriteriasResults as $key => $obj) {
                $indexTotal = $indexTotal + $obj->index_value;
            }
        }

        return !empty($indexTotal) ? $indexTotal : ' 0';
    }

    public function getCriteriaKey($indexId)
    {
        $criteriaKeyResult = $this->find()
            ->distinct(['criteria'])
            ->where([$this->aliasField('index_id') => $indexId])
            ->all();

        $criteriaKey = [];
        foreach ($criteriaKeyResult as $key => $obj) {
            $criteriaKey[$obj->criteria] = $obj->criteria;
        }

        return $criteriaKey;
    }
}

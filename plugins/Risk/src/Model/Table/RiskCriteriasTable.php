<?php
namespace Risk\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;


class RiskCriteriasTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Risks', ['className' => 'Risk.Risks', 'foreignKey' =>'risk_id']);

        $this->hasMany('StudentRisksCriterias', ['className' => 'Institution.StudentRisksCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('risk_value', [
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
        $riskCriteriaId = $entity->id;
        $this->StudentRisksCriterias->deleteAll(['risk_criteria_id' => $riskCriteriaId]);
    }

    public function findActiveRiskCriteria(Query $query, array $options)
    {
        $InstitutionRisks = TableRegistry::get('Institution.InstitutionRisks');

        $activeRiskId = [];
        $activeRisksData = $InstitutionRisks->find()
            ->where([
                'institution_id' => $options['institution_id'],
                'OR' => [
                    ['status' => 2], // status == processing
                    ['status' => 3]  // status == completed
                ]
            ])
            ->all();

        foreach ($activeRisksData as $activeRisks) {
            $activeRiskId [] = $activeRisks->risk_id;
        }

        return $query->contain('Risks')
            ->where([
                'criteria' => $options['criteria_key'],
                $this->Risks->aliasField('id') . ' IN ' => $activeRiskId
            ])
            ->all();
    }

    public function getTotalRisk($riskId)
    {
        $riskCriteriasResults = $this->find()
            ->where([$this->aliasField('risk_id') => $riskId])
            ->toArray();

        $riskTotal = 0;
        if (!empty($riskCriteriasResults)) {
            foreach ($riskCriteriasResults as $key => $obj) {
                $riskTotal = $riskTotal + $obj->risk_value;
            }
        }

        return !empty($riskTotal) ? $riskTotal : ' 0';
    }

    public function getCriteriaKey($riskId)
    {
        $criteriaKeyResult = $this->find()
            ->distinct(['criteria'])
            ->where([$this->aliasField('risk_id') => $riskId])
            ->all();

        $criteriaKey = [];
        foreach ($criteriaKeyResult as $key => $obj) {
            $criteriaKey[$obj->criteria] = $obj->criteria;
        }

        return $criteriaKey;
    }
}

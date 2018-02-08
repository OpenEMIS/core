<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\AppTable;

class StudentRisksCriteriasTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionStudentRisks', ['className' => 'Institution.InstitutionStudentRisks', 'foreignKey' => 'institution_student_risk_id']);
        $this->belongsTo('RiskCriterias', ['className' => 'Risk.RiskCriterias', 'foreignKey' => 'risk_criteria_id']);
    }

    public function getValue($institutionStudentRiskId, $riskCriteriaId)
    {
        $valueData = $this->find()
            ->where([
                $this->aliasField('institution_student_risk_id') => $institutionStudentRiskId,
                $this->aliasField('risk_criteria_id') => $riskCriteriaId
            ])
            ->first();

        $value = null;
        if (!empty($valueData)) {
            $value = $valueData->value;
        }

        return $value;
    }

    public function getRiskValue($value, $riskCriteriaId, $institutionId, $studentId, $academicPeriodId)
    {
        $RiskCriteriasData = $this->RiskCriterias->get($riskCriteriaId);
        $operator = $RiskCriteriasData->operator;
        $threshold = $RiskCriteriasData->threshold;

        $riskValue = 0;
        if (!is_null($value)) {
            switch ($operator) {
                case 1: // '<'
                    if ($value <= $threshold) {
                        $riskValue = $RiskCriteriasData->risk_value;
                    } else {
                        $riskValue = 0;
                    }
                    break;

                case 2: // '>'
                    if ($value >= $threshold) {
                        $riskValue = $RiskCriteriasData->risk_value;
                    } else {
                         $riskValue = 0;
                    }
                    break;

                case 3: // '='
                case 11: // for Repeated status
                    $Risks = TableRegistry::get('Risk.Risks');
                    $criteriaName = $RiskCriteriasData->criteria;
                    $criteriaDetails = $Risks->getCriteriasDetails($criteriaName);
                    $criteriaModel = TableRegistry::get($criteriaDetails['model']);

                    $valueRisk = $criteriaModel->getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName);

                    if (array_key_exists($threshold, $valueRisk)) {
                        $riskValue = 0;
                        $riskValue = ($valueRisk[$threshold]) * ($RiskCriteriasData->risk_value);
                    } else {
                        $riskValue = 0;
                    }
                    break;
            }
        }

        return $riskValue;
    }
}

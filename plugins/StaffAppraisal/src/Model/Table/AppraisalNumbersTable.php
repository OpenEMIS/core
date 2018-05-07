<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;

class AppraisalNumbersTable extends AppTable
{
    const NO_VALIDATION = '';
    const GREATER_THAN = 'greaterThan';
    const GREATER_THAN_OR_EQUAL = 'greaterThanEqual';
    const LESS_THAN = 'lessThan';
    const LESS_THAN_OR_EQUAL = 'lessThanEqual';
    const BETWEEN = 'between';

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->notEmpty('min_exclusive')
            ->notEmpty('min_inclusive')
            ->notEmpty('max_exclusive')
            ->notEmpty('max_inclusive')
            ->add('min_inclusive', 'comparison', [
                'rule' => function ($value, $context) {
                    if (isset($context['data']['max_inclusive'])) {
                        return intval($value) <= intval($context['data']['max_inclusive']);
                    }
                    return true;
                },
                'message' => __('Lower Limit cannot be more than the Upper Limit.')
            ]);
    }

    public function getValidationTypeOptions()
    {
        return $ruleOptions = [
            self::NO_VALIDATION => __('No Validation'),
            self::GREATER_THAN => __('Greater than'),
            self::GREATER_THAN_OR_EQUAL => __('Greater than or equal to'),
            self::LESS_THAN => __('Less than'),
            self::LESS_THAN_OR_EQUAL => __('Less than or equal to'),
            self::BETWEEN => __('Between (Inclusive)')
        ];

        return $ruleOptions;
    }
 
    public function getValidationTypeId(Entity $entity)
    {
        if ($entity->has('appraisal_number')) {
            $appraisalNumber = $entity->appraisal_number;
            $minInclusive = $appraisalNumber->min_inclusive;
            $minExclusive = $appraisalNumber->min_exclusive;
            $maxInclusive = $appraisalNumber->max_inclusive;
            $maxExclusive = $appraisalNumber->max_exclusive;

            if (!is_null($minInclusive) && !is_null($maxInclusive)) {
                return self::BETWEEN;
            } elseif (!is_null($minInclusive)) {
                return self::GREATER_THAN_OR_EQUAL;
            } elseif (!is_null($minExclusive)) {
                return self::GREATER_THAN;
            } elseif (!is_null($maxInclusive)) {
                return self::LESS_THAN_OR_EQUAL;
            } elseif (!is_null($maxExclusive)) {
                return self::LESS_THAN;
            }
        }

        return self::NO_VALIDATION;
    }

    public function updateData(ArrayObject $data)
    {
        if (array_key_exists('number_validation_rule', $data)) {
            $data['appraisal_number']['validation_rule'] = $data['number_validation_rule'];
        }
        // pr($data);
        // die;
    }

    public function updateEntity(Entity $entity)
    {
        if ($entity->has('appraisal_number')) {
            $validationType = ($entity->has('number_validation_rule')) ? $entity->number_validation_rule : self::NO_VALIDATION;
            $appraisalNumber = $entity->appraisal_number;

            $minInclusive = '';
            $maxInclusive = '';
            $minExclusive = '';
            $maxExclusive = '';

            switch ($validationType) {
                case self::NO_VALIDATION:
                    $this->delete($appraisalNumber);
                    unset($entity->appraisal_number);
                    break;
                case self::GREATER_THAN:
                    $minExclusive = $appraisalNumber->min_exclusive;
                    break;
                case self::GREATER_THAN_OR_EQUAL:
                    $minInclusive = $appraisalNumber->min_inclusive;
                    break;
                case self::LESS_THAN:
                    $maxExclusive = $appraisalNumber->max_exclusive;
                    break;
                case self::LESS_THAN_OR_EQUAL:
                    $maxInclusive = $appraisalNumber->max_inclusive;
                    break;
                case self::BETWEEN:
                    $minInclusive = $appraisalNumber->min_inclusive;
                    $maxInclusive = $appraisalNumber->max_inclusive;
                    break;
            }

            $appraisalNumber->min_inclusive = $minInclusive;
            $appraisalNumber->max_inclusive = $maxInclusive;
            $appraisalNumber->min_exclusive = $minExclusive;
            $appraisalNumber->max_exclusive = $maxExclusive;
        }

        return $entity;
    }
}

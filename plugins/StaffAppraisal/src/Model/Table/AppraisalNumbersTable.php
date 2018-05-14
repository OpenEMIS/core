<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;

class AppraisalNumbersTable extends AppTable
{
    const NO_VALIDATION = 'no_validation';
    const GREATER_THAN = 'greater_than';
    const GREATER_THAN_OR_EQUAL = 'greater_than_equal';
    const LESS_THAN = 'less_than';
    const LESS_THAN_OR_EQUAL = 'less_than_equal';
    const BETWEEN = 'between';

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias']);
    }

    public function validationDefault(Validator $validator)
    {

        return $validator
            ->notEmpty('min_exclusive', null, function ($context) {
                if (array_key_exists('validation_rule', $context['data']) && $context['data']['validation_rule'] == self::GREATER_THAN) {
                    return empty($context['data']['min_exclusive']);
                }
                return false;
            })
            ->notEmpty('min_inclusive', null, function ($context) {
                if (array_key_exists('validation_rule', $context['data']) && $context['data']['validation_rule'] == self::GREATER_THAN_OR_EQUAL) {
                    return empty($context['data']['min_inclusive']);
                }
                return false;
            })
            ->notEmpty('max_exclusive', null, function ($context) {
                if (array_key_exists('validation_rule', $context['data']) && $context['data']['validation_rule'] == self::LESS_THAN) {
                    return empty($context['data']['max_exclusive']);
                }
                return false;
            })
            ->notEmpty('max_inclusive', null, function ($context) {
                if (array_key_exists('validation_rule', $context['data']) && $context['data']['validation_rule'] == self::LESS_THAN_OR_EQUAL) {
                    return empty($context['data']['max_inclusive']);
                }
                return false;
            })
            ->add('min_inclusive', 'comparison', [
                'rule' => function ($value, $context) {
                    if (array_key_exists('validation_rule', $context['data']) && $context['data']['validation_rule'] == self::BETWEEN) {
                        if (isset($context['data']['max_inclusive'])) {
                            return intval($value) <= intval($context['data']['max_inclusive']);
                        } else {
                            return true;
                        }
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
 
    public function updateData(ArrayObject $data)
    {
        if (array_key_exists('appraisal_number', $data)) {
            $validationRuleType = $data['appraisal_number']['validation_rule'];
            $minInclusive = '';
            $maxInclusive = '';
            $minExclusive = '';
            $maxExclusive = '';

            switch ($validationRuleType) {
                case self::NO_VALIDATION:
                    // dont have any value
                    break;
                case self::GREATER_THAN:
                    $minExclusive = $data['appraisal_number']['min_exclusive'];
                    break;
                case self::GREATER_THAN_OR_EQUAL:
                    $minInclusive = $data['appraisal_number']['min_inclusive'];
                    break;
                case self::LESS_THAN:
                    $maxExclusive = $data['appraisal_number']['max_exclusive'];
                    break;
                case self::LESS_THAN_OR_EQUAL:
                    $maxInclusive = $data['appraisal_number']['max_inclusive'];
                    break;
                case self::BETWEEN:
                    $minInclusive = $data['appraisal_number']['min_inclusive'];
                    $maxInclusive = $data['appraisal_number']['max_inclusive'];
                    break;
            }

            $data['appraisal_number']['min_exclusive'] = $minExclusive;
            $data['appraisal_number']['min_inclusive'] = $minInclusive;
            $data['appraisal_number']['max_exclusive'] = $maxExclusive;
            $data['appraisal_number']['max_inclusive'] = $maxInclusive;
        }
    }
}

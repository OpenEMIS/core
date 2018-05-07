<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use StaffAppraisal\Model\Table\AppraisalNumbersTable as AppraisalNumbers;

class AppraisalNumberAnswersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms', 'foreignKey' => 'appraisal_form_id']);
        $this->belongsTo('AppraisalCriterias', ['className' => 'StaffAppraisal.AppraisalCriterias', 'foreignKey' => 'appraisal_criteria_id']);
        $this->belongsTo('StaffAppraisals', ['className' => 'Institution.StaffAppraisals', 'foreignKey' => 'institution_staff_appraisal_id', 'joinType' => 'INNER']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.buildValidator'] = ['callable' => 'buildValidator', 'priority' => 5];
        return $events;
    }

    // this will be moved to a behaviour when revamping the custom fields
    public function validationDefault(Validator $validator)
    {
        return $validator
            ->notEmpty('answer', __('This field cannot be left empty'), function ($context) {
                if (array_key_exists('is_mandatory', $context['data'])) {
                    return $context['data']['is_mandatory'];
                }
                return false;
            });
    }

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        $AppraisalNumbers = TableRegistry::get('StaffAppraisal.AppraisalNumbers');
        return $validator
            ->add('answer', 'validation_rule', [
                'rule' => function ($value, $context) use ($AppraisalNumbers) {
                    $appraisalNumber = $AppraisalNumbers
                        ->find()
                        ->select([
                            $AppraisalNumbers->aliasField('min_inclusive'),
                            $AppraisalNumbers->aliasField('min_exclusive'),
                            $AppraisalNumbers->aliasField('max_inclusive'),
                            $AppraisalNumbers->aliasField('max_exclusive'),
                            $AppraisalNumbers->aliasField('validation_rule')
                        ])
                        ->where([
                            $AppraisalNumbers->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id']
                        ])
                        ->first();

                    if (!is_null($appraisalNumber)) {
                        $validateRuleType = $appraisalNumber->validation_rule;
                        switch ($validateRuleType) {
                            case AppraisalNumbers::GREATER_THAN:
                                $validateValue = $appraisalNumber->min_exclusive;
                                if ($value > $validateValue) {
                                    return true;
                                }
                                return sprintf(__('This field must be more than %d'), $validateValue);
                            case AppraisalNumbers::GREATER_THAN_OR_EQUAL:
                                $validateValue = $appraisalNumber->min_inclusive;
                                if ($value >= $validateValue) {
                                    return true;
                                }
                                return sprintf(__('This field must be more than or equals to %d'), $validateValue);
                            case AppraisalNumbers::LESS_THAN:
                                $validateValue = $appraisalNumber->max_exclusive;
                                if ($value < $validateValue) {
                                    return true;
                                }
                                return sprintf(__('This field must be less than %d'), $validateValue);
                            case AppraisalNumbers::LESS_THAN_OR_EQUAL:
                                $validateValue = $appraisalNumber->max_inclusive;
                                if ($value <= $validateValue) {
                                    return true;
                                }
                                return sprintf(__('This field must be less than or equals to %d'), $validateValue);
                            case AppraisalNumbers::BETWEEN:
                                $validateLowerLimit = $appraisalNumber->min_inclusive;
                                $validateUpperLimit = $appraisalNumber->max_inclusive;
                                if ($value >= $validateLowerLimit && $value <= $validateUpperLimit) {
                                    return true;
                                }
                                return sprintf(__('This field must be in between %d to %d'), $validateLowerLimit, $validateUpperLimit);
                                break;
                            default:
                                return true;
                        }
                    }

                    return true;
                }
            ]);

        // return $validator
        //     ->add('answer', 'greaterThan', [
        //         'rule' => function ($value, $context) use ($AppraisalNumbers) {
        //             $appraisalNumber = $AppraisalNumbers
        //                 ->find()
        //                 ->select([$AppraisalNumbers->aliasField('min_exclusive')])
        //                 ->where([
        //                     $AppraisalNumbers->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id'],
        //                     $AppraisalNumbers->aliasField('validation_rule') => 'greater_than',
        //                 ])
        //                 ->first();

        //             if (!is_null($appraisalNumber)) {
        //                 $validateValue = $appraisalNumber->min_exclusive;
        //                 return $value > $validateValue;
        //             }

        //             return true;
        //         },
        //         'message' => __('The number should be greater than 50?')
        //     ])
        //     ->add('answer', 'greaterThanEqual', [
        //         'rule' => function ($value, $context) use ($AppraisalNumbers) {
        //             $appraisalNumber = $AppraisalNumbers
        //                 ->find()
        //                 ->select([$AppraisalNumbers->aliasField('min_inclusive')])
        //                 ->where([
        //                     $AppraisalNumbers->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id'],
        //                     $AppraisalNumbers->aliasField('validation_rule') => 'greater_than_equal'
        //                 ])
        //                 ->first();

        //             if (!is_null($appraisalNumber)) {
        //                 $validateValue = $appraisalNumber->min_inclusive;
        //                 return $value >= $validateValue;
        //             }

        //             return true;
        //         }
        //     ])
        //     ->add('answer', 'lessThan', [
        //         'rule' => function ($value, $context) use ($AppraisalNumbers) {
        //             $appraisalNumber = $AppraisalNumbers
        //                 ->find()
        //                 ->select([$AppraisalNumbers->aliasField('max_exclusive')])
        //                 ->where([
        //                     $AppraisalNumbers->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id'],
        //                     $AppraisalNumbers->aliasField('validation_rule') => 'less_than',
        //                 ])
        //                 ->first();

        //             if (!is_null($appraisalNumber)) {
        //                 $validateValue = $appraisalNumber->max_exclusive;
        //                 return 'ms vi';
        //             }

        //             return true;
        //         },
        //     ])
        //     ->add('answer', 'lessThanEqual', [
        //         'rule' => function ($value, $context) use ($AppraisalNumbers) {
        //             $appraisalNumber = $AppraisalNumbers
        //                 ->find()
        //                 ->select([$AppraisalNumbers->aliasField('max_inclusive')])
        //                 ->where([
        //                     $AppraisalNumbers->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id'],
        //                     $AppraisalNumbers->aliasField('validation_rule') => 'less_than_equal',
        //                 ])
        //                 ->first();

        //             if (!is_null($appraisalNumber)) {
        //                 $validateValue = $appraisalNumber->max_inclusive;
        //                 return $value <= $validateValue;
        //             }

        //             return true;
        //         },
        //         'message' => __('test4')
        //     ])
        //     ->add('answer', 'between', [
        //         'rule' => function ($value, $context) use ($AppraisalNumbers) {
        //             $appraisalNumber = $AppraisalNumbers
        //                 ->find()
        //                 ->select([
        //                     $AppraisalNumbers->aliasField('min_inclusive'),
        //                     $AppraisalNumbers->aliasField('max_inclusive')
        //                 ])
        //                 ->where([
        //                     $AppraisalNumbers->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id'],
        //                     $AppraisalNumbers->aliasField('validation_rule') => 'between',

        //                 ])
        //                 ->first();

        //             if (!is_null($appraisalNumber)) {
        //                 $validateValue = $appraisalNumber->max_inclusive;
        //                 return $value <= $validateValue;
        //             }

        //             return true;
        //         },
        //         'message' => __('test5')
        //     ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew() && is_null($entity->answer)) {
            return $event->stopPropagation();
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (is_null($entity->answer)) {
            $this->delete($entity);
        }
    }
}

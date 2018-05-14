<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use StaffAppraisal\Model\Table\AppraisalAnswersTable;
use StaffAppraisal\Model\Table\AppraisalNumbersTable as AppraisalNumbers;

class AppraisalNumberAnswersTable extends AppraisalAnswersTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.buildValidator'] = ['callable' => 'buildValidator', 'priority' => 5];
        return $events;
    }

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        return $validator
            ->add('answer', 'greaterThan', [
                'rule' => function ($value, $context) {
                    if ($context['data']['validation_rule'] == AppraisalNumbers::GREATER_THAN) {
                        $AppraisalNumbersTable = TableRegistry::get('StaffAppraisal.AppraisalNumbers');
                        $appraisalNumber = $AppraisalNumbersTable
                            ->find()
                            ->select([
                                $AppraisalNumbersTable->aliasField('min_exclusive')
                            ])
                            ->where([
                                $AppraisalNumbersTable->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id']
                            ])
                            ->first();


                        if (!is_null($appraisalNumber)) {
                            $validateValue = $appraisalNumber->min_exclusive;
                            if ($value > $validateValue) {
                                return true;
                            }
                            return sprintf(__('This field cannot be less than or equals to %d'), $validateValue);
                            // return sprintf(__('This field must be more than %d'), $validateValue);
                        } else {
                            Log::write('debug', 'Validate ' . $context['data']['validation_rule'] . ' - Entity not found for appraisal_criteria_id: ' . $context['data']['appraisal_criteria_id']);
                        }
                    }
                    return true;
                }
            ])
            ->add('answer', 'greaterThanOrEqual', [
                'rule' => function ($value, $context) {
                    if ($context['data']['validation_rule'] == AppraisalNumbers::GREATER_THAN_OR_EQUAL) {
                        $AppraisalNumbersTable = TableRegistry::get('StaffAppraisal.AppraisalNumbers');
                        $appraisalNumber = $AppraisalNumbersTable
                            ->find()
                            ->select([
                                $AppraisalNumbersTable->aliasField('min_inclusive')
                            ])
                            ->where([
                                $AppraisalNumbersTable->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id']
                            ])
                            ->first();


                        if (!is_null($appraisalNumber)) {
                            $validateValue = $appraisalNumber->min_inclusive;
                            if ($value >= $validateValue) {
                                return true;
                            }
                            return sprintf(__('This field cannot be less than %d'), $validateValue);
                            // return sprintf(__('This field must be more than or equals to %d'), $validateValue);
                        } else {
                            Log::write('debug', 'Validate ' . $context['data']['validation_rule'] . ' - Entity not found for appraisal_criteria_id: ' . $context['data']['appraisal_criteria_id']);
                        }
                    }
                    return true;
                }
            ])
            ->add('answer', 'lessThan', [
                'rule' => function ($value, $context) {
                    if ($context['data']['validation_rule'] == AppraisalNumbers::LESS_THAN) {
                        $AppraisalNumbersTable = TableRegistry::get('StaffAppraisal.AppraisalNumbers');
                        $appraisalNumber = $AppraisalNumbersTable
                            ->find()
                            ->select([
                                $AppraisalNumbersTable->aliasField('max_exclusive')
                            ])
                            ->where([
                                $AppraisalNumbersTable->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id']
                            ])
                            ->first();


                        if (!is_null($appraisalNumber)) {
                            $validateValue = $appraisalNumber->max_exclusive;
                            if ($value < $validateValue) {
                                return true;
                            }
                            return sprintf(__('This field cannot be more than or equals to %d'), $validateValue);
                            // return sprintf(__('This field must be less than %d'), $validateValue);
                        } else {
                            Log::write('debug', 'Validate ' . $context['data']['validation_rule'] . ' - Entity not found for appraisal_criteria_id: ' . $context['data']['appraisal_criteria_id']);
                        }
                    }
                    return true;
                }
            ])
            ->add('answer', 'lessThanOrEqual', [
                'rule' => function ($value, $context) {
                    if ($context['data']['validation_rule'] == AppraisalNumbers::LESS_THAN_OR_EQUAL) {
                        $AppraisalNumbersTable = TableRegistry::get('StaffAppraisal.AppraisalNumbers');
                        $appraisalNumber = $AppraisalNumbersTable
                            ->find()
                            ->select([
                                $AppraisalNumbersTable->aliasField('max_inclusive')
                            ])
                            ->where([
                                $AppraisalNumbersTable->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id']
                            ])
                            ->first();


                        if (!is_null($appraisalNumber)) {
                            $validateValue = $appraisalNumber->max_inclusive;
                            if ($value <= $validateValue) {
                                return true;
                            }
                            return sprintf(__('This field cannot be more than %d'), $validateValue);
                            // return sprintf(__('This field must be less than or equals to %d'), $validateValue);
                        } else {
                            Log::write('debug', 'Validate ' . $context['data']['validation_rule'] . ' - Entity not found for appraisal_criteria_id: ' . $context['data']['appraisal_criteria_id']);
                        }
                    }
                    return true;
                }
            ])
            ->add('answer', 'inBetween', [
                'rule' => function ($value, $context) {
                    if ($context['data']['validation_rule'] == AppraisalNumbers::BETWEEN) {
                        $AppraisalNumbersTable = TableRegistry::get('StaffAppraisal.AppraisalNumbers');
                        $appraisalNumber = $AppraisalNumbersTable
                            ->find()
                            ->select([
                                $AppraisalNumbersTable->aliasField('max_inclusive'),
                                $AppraisalNumbersTable->aliasField('min_inclusive')
                            ])
                            ->where([
                                $AppraisalNumbersTable->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id']
                            ])
                            ->first();


                        if (!is_null($appraisalNumber)) {
                            $validateLowerLimit = $appraisalNumber->min_inclusive;
                            $validateUpperLimit = $appraisalNumber->max_inclusive;

                            if ($value <= $validateUpperLimit && $value >= $validateLowerLimit) {
                                return true;
                            }
                            return sprintf(__('This field should be in between %d to %d'), $validateLowerLimit, $validateUpperLimit);
                        } else {
                            Log::write('debug', 'Validate ' . $context['data']['validation_rule'] . ' - Entity not found for appraisal_criteria_id: ' . $context['data']['appraisal_criteria_id']);
                        }
                    }
                    return true;
                }
            ]);
    }
}

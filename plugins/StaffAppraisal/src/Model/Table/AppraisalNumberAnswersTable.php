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
        return $validator
            ->add('answer', 'validation_rule', [
                'rule' => function ($value, $context) {
                    $AppraisalNumbersTable = TableRegistry::get('StaffAppraisal.AppraisalNumbers');
                    $appraisalNumber = $AppraisalNumbersTable
                        ->find()
                        ->select([
                            $AppraisalNumbersTable->aliasField('min_inclusive'),
                            $AppraisalNumbersTable->aliasField('min_exclusive'),
                            $AppraisalNumbersTable->aliasField('max_inclusive'),
                            $AppraisalNumbersTable->aliasField('max_exclusive'),
                            $AppraisalNumbersTable->aliasField('validation_rule')
                        ])
                        ->where([
                            $AppraisalNumbersTable->aliasField('appraisal_criteria_id') => $context['data']['appraisal_criteria_id']
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
                            default:
                                return true;
                        }
                    }

                    return true;
                }
            ]);
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

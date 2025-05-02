<?php
namespace Institution\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\I18n\Date;

class StaffValidationBehavior extends Behavior
{
    public function buildStaffValidation()
    {
        $validator = new Validator();
        $validator->setProvider('custom', $this->_table); // POCOR-9080
        return $validator
            ->allowEmpty('end_date')
            ->add('end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'start_date', true]
            ])
            ->add('start_date', 'ruleStaffExistWithinPeriod', [
                'rule' => ['checkStaffExistWithinPeriod'],
                'on' => 'update'
            ])
            ->add('institution_position_id', 'ruleCheckFTE', [
                'rule' => ['checkFTE'],
            ])
        ;
    }




}

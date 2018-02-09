<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class AppraisalPeriodsTable extends ControllerActionTable
{
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsToMany('AppraisalTypes', [
            'className' => 'StaffAppraisal.AppraisalTypes',
            'joinTable' => 'appraisal_periods_types',
            'foreignKey' => 'appraisal_period_id',
            'targetForeignKey' => 'appraisal_type_id',
            'through' => 'StaffAppraisal.AppraisalPeriodsTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms', 'foreignKey' => 'appraisal_form_id']);
        $this->hasMany('InstitutionStaffAppraisals', ['className' => 'Institution.InstitutionStaffAppraisals', 'foreignKey' => 'appraisal_period_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->requirePresence('appraisal_types', 'create')
            ->add('appraisal_types', [
                'notEmpty' => [
                    'rule' => function ($value, $context) {
                        return isset($value['_ids']) && !empty($value['_ids']);
                    },
                    'message' => __('This field cannot be left empty')
                ]
            ])
            ->add('end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'start_date', true],
                'message' => __('End Date should not be earlier than Start Date')
            ]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('appraisal_form_id', ['type' => 'select']);
        $typeOptions = $this->AppraisalTypes->find('list')->toArray();
        $this->field('appraisal_types', ['type' => 'chosenSelect', 'options' => $typeOptions, 'attr' => ['required' => true]]);
        $this->field('academic_period_id', ['type' => 'select']);
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AcademicPeriods', 'AppraisalTypes', 'AppraisalForms']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AppraisalTypes']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('appraisal_form_id', ['type' => 'readonly', 'value' => $entity->appraisal_form_id, 'attr' => ['value' => $entity->appraisal_form->name]]);
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $entity->academic_period_id, 'attr' => ['value' => $entity->academic_period->name]]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // $this->field('appraisal_types');
        $this->setFieldOrder(['appraisal_form_id', 'academic_period_id',
            // 'appraisal_types',
            'start_date', 'end_date']);
    }

    // To implement table to show the number of types that are tagged
    // public function onGetCustomTypesElement(Event $event, $action, $entity, $attr, $options = [])
    // {
    //     if ($action == 'view') {

    //     } elseif ($action == 'edit') {

    //     }
    // }
}

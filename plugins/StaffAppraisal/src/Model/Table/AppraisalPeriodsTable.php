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
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms', 'foreignKey' => 'appraisal_form_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsToMany('AppraisalTypes', [
            'className' => 'StaffAppraisal.AppraisalTypes',
            'foreignKey' => 'appraisal_period_id',
            'targetForeignKey' => 'appraisal_type_id',
            'joinTable' => 'appraisal_periods_types',
            'through' => 'StaffAppraisal.AppraisalPeriodsTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('StaffAppraisals', ['className' => 'Institution.StaffAppraisals', 'foreignKey' => 'appraisal_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('AcademicPeriod.Period');
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->add('name', [
                'ruleUnique' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table',
                    'message' => __('Please enter a unique name in the selected academic period')
                ]
            ])
            ->requirePresence('appraisal_types', 'create')
            ->add('appraisal_types', [
                'notEmpty' => [
                    'rule' => function ($value, $context) {
                        return isset($value['_ids']) && !empty($value['_ids']);
                    },
                    'message' => __('This field cannot be left empty')
                ]
            ])
            ->add('date_enabled', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'message' => __('Date range is not within the academic period.')
                ]
            ])
            ->add('date_disabled', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'message' => __('Date range is not within the academic period.')
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'date_enabled', true],
                    'message' => __('Date Disabled should not be earlier than Date Enabled')
                ]
            ]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $typeOptions = $this->AppraisalTypes->find('list')->toArray();
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

        $this->field('name');
        $this->field('appraisal_form_id', ['type' => 'select']);
        $this->field('appraisal_types', ['type' => 'chosenSelect', 'options' => $typeOptions, 'attr' => ['required' => true]]);
        $this->field('academic_period_id', ['type' => 'select', 'options' => $academicPeriodOptions]);
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
        $appraisalTypes = $this->getAppraisalTypes($entity);

        $this->field('name');
        $this->field('appraisal_form_id', ['type' => 'readonly', 'value' => $entity->appraisal_form_id, 'attr' => ['value' => $entity->appraisal_form->name]]);
        $this->field('appraisal_types', ['type' => 'disabled', 'attr' => ['value' => $appraisalTypes]]);
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $entity->academic_period_id, 'attr' => ['value' => $entity->academic_period->name]]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('appraisal_types');
        $this->setFieldOrder(['name', 'appraisal_form_id', 'appraisal_types', 'academic_period_id', 'date_enabled', 'date_disabled']);
    }

    public function onGetAppraisalTypes(Event $event, Entity $entity)
    {
        return $this->getAppraisalTypes($entity);
    }

    public function getAppraisalTypes(Entity $entity)
    {
        $types = [];
        if ($entity->has('appraisal_types')) {
            foreach ($entity->appraisal_types as $type) {
                $types[] = $type->code . ' - ' . $type->name;
            }
        }
        return implode(', ', $types);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->AppraisalTypes->alias()
        ];
    }
}

<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class AppraisalCriteriasTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('FieldTypes', ['className' => 'FieldTypes', 'foreignKey' => 'field_type_id']);
        $this->hasOne('AppraisalSliders', ['className' => 'StaffAppraisal.AppraisalSliders', 'foreignKey' => 'appraisal_criteria_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalDropdownOptions', [
            'className' => 'StaffAppraisal.AppraisalDropdownOptions',
            'foreignKey' => 'appraisal_criteria_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('AppraisalForms', [
            'className' => 'StaffAppraisal.AppraisalForms',
            'foreignKey' => 'appraisal_criteria_id',
            'targetForeignKey' => 'appraisal_form_id',
            'joinTable' => 'appraisal_forms_criterias',
            'through' => 'StaffAppraisal.AppraisalFormsCriterias',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->add('code', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => __('Code must be unique')
            ])
            ->requirePresence('field_type_id', 'create');
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['FieldTypes', 'AppraisalSliders']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $code = $entity->field_type->code;
        switch ($code) {
            case 'TEXTAREA':
                // No implementation
                break;
            case 'SLIDER':
                $this->field('min', ['after' => 'field_type_id']);
                $this->field('max', ['after' => 'min']);
                $this->field('step', ['after' => 'max']);
                break;
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('name');
        $this->field('field_type_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
    }

    public function onUpdateFieldFieldTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $fieldTypeOptions = $this->FieldTypes
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code'
                ])
                ->order([$this->FieldTypes->aliasField('id')])
                ->toArray();

            $entity = $attr['entity'];
            $fieldTypeId = $entity->field_type_id;

            if (!$entity->isNew()) { // edit not allow to change field type
                $attr['type'] = 'readonly';
                $attr['value'] = $fieldTypeId;
                $attr['attr']['value'] = $entity->field_type->name;
            }

            if (isset($fieldTypeOptions[$fieldTypeId])) {
                switch ($fieldTypeOptions[$fieldTypeId]) {
                    case 'TEXTAREA':
                        // No implementation
                        break;
                    case 'SLIDER':
                        $this->field('appraisal_slider.min', [
                            'type' => 'integer',
                            'attr' => ['label' => __('Min'),
                            'required' => true
                        ]]);
                        $this->field('appraisal_slider.max', [
                            'type' => 'integer',
                            'attr' => ['label' => __('Max'),
                            'required' => true]
                        ]);
                        $this->field('appraisal_slider.step', [
                            'type' => 'integer',
                            'attr' => ['label' => __('Step'),
                            'required' => true]
                        ]);
                        break;
                }
            }
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onGetMin(Event $event, Entity $entity)
    {
        return strval($entity->appraisal_slider->min);
    }

    public function onGetMax(Event $event, Entity $entity)
    {
        return strval($entity->appraisal_slider->max);
    }

    public function onGetStep(Event $event, Entity $entity)
    {
        return strval($entity->appraisal_slider->step);
    }
}

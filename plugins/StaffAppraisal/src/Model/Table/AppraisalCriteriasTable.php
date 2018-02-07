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
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        $this->belongsTo('FieldTypes', ['className' => 'FieldTypes', 'foreignKey' => 'field_type_id']);
        $this->hasOne('AppraisalSliders', ['className' => 'StaffAppraisal.AppraisalSliders', 'foreignKey' => 'appraisal_criteria_id']);
        $this->hasOne('AppraisalComments', ['className' => 'StaffAppraisal.AppraisalComments', 'foreignKey' => 'appraisal_criteria_id']);
        $this->hasMany('AppraisalFormsCriterias', ['className' => 'StaffAppraisal.AppraisalFormsCriterias', 'foreignKey' => 'appraisal_criteria_id']);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra) : void
    {
        $this->field('code');
        $this->field('name');
        $this->field('field_type_id', ['type' => 'select']);
    }

    public function validationDefault(Validator $validator)
    {
        return $validator->requirePresence('field_type_id', 'create');
    }

    public function onUpdateFieldFieldTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['onChangeReload'] = true;
            $fieldTypeOptions = $this->FieldTypes
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code'
                ])
                ->order([$this->FieldTypes->aliasField('id')])
                ->toArray();

            $fieldTypeId = $request->data($this->aliasField('field_type_id'));
            if (isset($fieldTypeOptions[$fieldTypeId])) {
                switch ($fieldTypeOptions[$fieldTypeId]) {
                    case 'TEXT':
                        // No implementation
                        break;
                    case 'SLIDER':
                        $this->field('appraisal_slider.min', ['attr' => ['label' => __('Min'), 'required' => true]]);
                        $this->field('appraisal_slider.max', ['attr' => ['label' => __('Max'), 'required' => true]]);
                        break;
                }
            }
            return $attr;
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) : void
    {
        $query->contain(['FieldTypes', 'AppraisalSliders']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) : void
    {
        $code = $entity->field_type->code;
        switch ($code) {
            case 'SLIDER':
                $this->field('min', ['after' => 'field_type_id']);
                $this->field('max', ['after' => 'min']);
                break;
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) : void
    {
        $code = $entity->field_type->code;
        switch ($code) {
            case 'SLIDER':
                $this->field('appraisal_slider.min', ['attr' => ['label' => __('Min'), 'required' => true]]);
                $this->field('appraisal_slider.max', ['attr' => ['label' => __('Max'), 'required' => true]]);
                break;
        }
    }

    public function onGetMin(Event $event, Entity $entity)
    {
        return strval($entity->appraisal_slider->min);
    }

    public function onGetMax(Event $event, Entity $entity)
    {
        return strval($entity->appraisal_slider->max);
    }
}

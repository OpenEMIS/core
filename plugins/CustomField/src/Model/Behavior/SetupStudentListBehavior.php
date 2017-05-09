<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupStudentListBehavior extends SetupBehavior
{
    private $module = 'Student.StudentSurveys';
    private $CustomModules = null;
    private $CustomForms = null;
    private $formOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->CustomModules = TableRegistry::get('CustomField.CustomModules');
        $this->CustomForms = TableRegistry::get('Survey.SurveyForms');

        $this->formOptions = $this->CustomForms
            ->find('list')
            ->innerJoin(
                [$this->CustomModules->alias() => $this->CustomModules->table()],
                [
                    $this->CustomModules->aliasField('id = ') . $this->CustomForms->aliasField('custom_module_id'),
                    $this->CustomModules->aliasField('model') => $this->module
                ]
            )
            ->toArray();
    }

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        $validator->notEmpty('survey_form');
    }

    public function onSetStudentListElements(Event $event, Entity $entity)
    {
        $model = $this->_table;

        if ($model->request->is(['get'])) {
            if (isset($entity->id)) {
                // view / edit
                if ($entity->has('params') && !empty($entity->params)) {
                    $params = json_decode($entity->params, true);
                    if (array_key_exists('survey_form_id', $params)) {
                        $formId = $params['survey_form_id'];
                        $model->request->query['survey_form'] = $formId;
                        $entity->survey_form = $formId;
                    }
                }
            } else {
                // add
                unset($model->request->query['survey_form']);
            }
        }

        $formOptions = ['' => __('-- Select Form --')] + $this->formOptions;
        $selectedForm = $model->queryString('survey_form', $formOptions);

        $inputOptions = [
            'type' => 'select',
            'options' => $formOptions,
            'default' => $selectedForm,
            'value' => $selectedForm,
            'after' => 'is_unique'
        ];
        if ($model->action == 'edit') {
            $inputOptions['type'] = 'readonly';
            $inputOptions['value'] = $selectedForm;
            $inputOptions['attr']['value'] = $formOptions[$selectedForm];
        }

        $model->field('survey_form', $inputOptions);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['field_type']) && $data['field_type'] == $this->fieldTypeCode) {
            if (isset($data['survey_form']) && !empty($data['survey_form'])) {
                $params = [];
                $params['survey_form_id'] = $data['survey_form'];
                $data['params'] = json_encode($params, JSON_UNESCAPED_UNICODE);
            } else {
                $data['params'] = '';
            }
        }
    }
}

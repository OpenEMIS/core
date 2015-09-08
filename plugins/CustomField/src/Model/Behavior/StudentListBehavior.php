<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class StudentListBehavior extends Behavior {
    protected $_defaultConfig = [
        'module' => 'Student.Students',
        'models' => [
            'CustomModules' => 'CustomField.CustomModules',
            'SurveyForms' => 'Survey.SurveyForms'
        ]
    ];

    public function initialize(array $config) {
        parent::initialize($config);
        if (isset($config['setup']) && $config['setup'] == true) {
            $models = $this->config('models');
            foreach ($models as $key => $model) {
                if (!is_null($model)) {
                    $this->{$key} = TableRegistry::get($model);
                    $this->{lcfirst($key).'Key'} = Inflector::underscore(Inflector::singularize($this->{$key}->alias())) . '_id';
                } else {
                    $this->{$key} = null;
                }
            }

            $formOptions = $this->SurveyForms
                ->find('list')
                ->innerJoin(
                    [$this->CustomModules->alias() => $this->CustomModules->table()],
                    [
                        $this->CustomModules->aliasField('id = ') . $this->SurveyForms->aliasField('custom_module_id'),
                        $this->CustomModules->aliasField('model') => $this->config('module')
                    ]
                )
                ->toArray();

            $this->_table->ControllerAction->addField('survey_form', [
                'options' => $formOptions
            ]);

            $this->_table->ControllerAction->setFieldOrder(['field_type', 'name', 'survey_form']);
        }
    }

    public function onGetSurveyForm(Event $event, Entity $entity) {
        foreach ($entity->custom_field_params as $key => $fieldParam) {
            if ($fieldParam->param_key == 'survey_form_id') {
                return $this->SurveyForms->get($fieldParam->param_value)->name;
                break;
            }
        }
    }

    public function onGetCustomStudentListElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $Form = $event->subject()->Form;

        $tableHeaders = [];
        $tableCells = [];

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        if ($action == 'view') {
            $value = $event->subject()->renderElement('CustomField.student_list', ['attr' => $attr]);
        } else if ($action == 'edit') {
            $value = $event->subject()->renderElement('CustomField.student_list', ['attr' => $attr]);  
        }

        return $value;
    }
}

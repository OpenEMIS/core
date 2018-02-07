<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Text;

class SurveyFormsTable extends CustomFormsTable
{
    private $excludedCustomModules = ['Student', 'Staff'];

    public function initialize(array $config)
    {
        $config['extra'] = [
            'fieldClass' => [
                'className' => 'Survey.SurveyQuestions',
                'joinTable' => 'survey_forms_questions',
                'foreignKey' => 'survey_form_id',
                'targetForeignKey' => 'survey_question_id',
                'through' => 'Survey.SurveyFormsQuestions',
                'dependent' => true
            ],
            'filterClass' => [
                'className' => 'Institution.Types',
                'joinTable' => 'survey_forms_filters',
                'foreignKey' => 'survey_form_id',
                'targetForeignKey' => 'survey_filter_id',
                'through' => 'Survey.SurveyFormsFilters',
                'dependent' => true
            ],
            'label' => [
                'custom_fields' => 'Survey Questions',
                'add_field' => 'Add Question',
                'fields' => 'Questions'
            ]
        ];
        parent::initialize($config);
        $this->hasMany('SurveyStatuses', ['className' => 'Survey.SurveyStatuses', 'dependent' => true, 'cascadeCallbacks' => true]);
        // The hasMany association for InstitutionSurveys and StudentSurveys is done in onBeforeDelete() and is added based on module to avoid conflict.
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('name', [
                'unique' => [
                    'rule' => ['validateUnique', ['scope' => 'custom_module_id']],
                    'provider' => 'table',
                    'message' => 'This name already exists in the system'
                ]
            ])
            ->add('code', [
                'unique' => [
                    'rule' => ['validateUnique'],
                    'provider' => 'table',
                    'message' => 'This code already exists in the system'
                ]
            ]);

        return $validator;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['custom_module_id', 'code', 'name', 'custom_filters', 'description', 'custom_fields']);
        unset($this->fields['apply_to_all']);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('code');
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onGetCustomModuleId(Event $event, Entity $entity)
    {
        return $entity->custom_module->code;
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        parent::viewAfterAction($event, $entity, $extra);
        unset($this->fields['custom_filters']);
        if ($this->AccessControl->check([$this->controller->name, 'Forms', 'download'])) {
            $toolbarButtons = [];
            $toolbarButtons['url'] = [
                'plugin' => 'Rest',
                'controller' => 'Rest',
                'action' => 'survey',
                'download',
                'xform',
                $entity->id,
                0
            ];
            $toolbarButtons['type'] = 'button';
            $toolbarButtons['label'] = '<i class="fa kd-download"></i>';
            $toolbarButtons['attr'] = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Download')
            ];
            $extra['toolbarButtons']['downloads'] = $toolbarButtons;
        }
    }

    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $customModule = $this->CustomModules
            ->find()
            ->where([
                $this->CustomModules->aliasField('id') => $entity->custom_module_id
            ])
            ->first();

        $model = $customModule->model;
        if ($model == 'Institution.Institutions') {
            $this->hasMany('InstitutionSurveys', ['className' => 'Institution.InstitutionSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        } elseif ($model == 'Student.Students') {
            $this->hasMany('StudentSurveys', ['className' => 'Student.StudentSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        }
    }

    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (!$request->is('post')) {
                $textValue = substr(Text::uuid(), 0, 8);
                $attr['attr']['value'] = $textValue;
            }
            return $attr;
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $entity->is_deletable = true;
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if ($this->AccessControl->check([$this->controller->name, 'Forms', 'download'])) {
            if (array_key_exists('view', $buttons)) {
                $downloadButton = $buttons['view'];
                $downloadButton['url'] = [
                    'plugin' => 'Rest',
                    'controller' => 'Rest',
                    'action' => 'survey',
                    'download',
                    'xform',
                    $entity->id,
                    0
                ];
                $downloadButton['label'] = '<i class="kd-download"></i>' . __('Download');
                $buttons['download'] = $downloadButton;
            }
        }

        return $buttons;
    }

    public function getModuleQuery()
    {
        return $this->CustomModules
            ->find('list', ['keyField' => 'id', 'valueField' => 'code'])
            ->find('visible')
            ->where([
                $this->CustomModules->aliasField('parent_id') => 0,
                $this->CustomModules->aliasField('code NOT IN') => $this->excludedCustomModules
            ]);
    }

    public function onUpdateFieldCustomFilters(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            parent::onUpdateFieldCustomFilters($event, $attr, $action, $request);
        } elseif ($action == 'add' || $action == 'edit') {
            $customModule = $attr['attr']['customModule'];
            $selectedModule = $customModule->id;
            $filter = $customModule->filter;

            if (isset($filter)) {
                $entity = $attr['attr']['entity'];
                list($plugin, $modelAlias) = explode('.', $filter, 2);
                $labelText = Inflector::underscore(Inflector::singularize($modelAlias));
                $filterOptions = TableRegistry::get($filter)->getList()->toArray();

                $attr['attr']['label'] = __(Inflector::humanize($labelText));
                $attr['options'] = $filterOptions;
                $attr['placeholder'] = __('Select ') . __(Inflector::humanize($labelText));
            }
        }

        return $attr;
    }

    public function onGetCustomFilters(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            if (!is_null($entity->_matchingData['CustomModules']->filter)) {
                if (sizeof($entity->custom_filters) > 0) {
                    $chosenSelectList = [];
                    foreach ($entity->custom_filters as $value) {
                        $chosenSelectList[] = $value->name;
                    }
                    return implode(', ', $chosenSelectList);
                }
            }

            return '<i class="fa fa-minus"></i>';
        }
    }

    private function setupFields(Entity $entity)
    {
        $selectedModule = $this->request->query('module');
        $customModule = $this->CustomModules->get($selectedModule);

        $this->field('custom_module_id');

        $this->field('custom_filters', [
                    'type' => 'chosenSelect',
                    'placeholder' => __('Select Filters'),
                    'attr' => ['customModule' => $customModule, 'entity' => $entity]
                ]);

        $this->field('custom_fields', [
            'type' => 'custom_order_field',
            'valueClass' => 'table-full-width',
            '' => 'description'
        ]);
    }
}

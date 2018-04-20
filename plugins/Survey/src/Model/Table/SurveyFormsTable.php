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
    const CUSTOM_FILTER = 1;
    const ALL_CUSTOM_FILER = 0;

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
            'Rules' => ['index'],
            'OpenEMIS_Survey' => ['index']
        ]);
        $this->setDeleteStrategy('restrict');
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
        unset($this->fields['apply_to_all']);
        $this->setFieldOrder(['custom_module_id', 'code', 'name', 'description', 'custom_fields']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew()) {
            unset($entity->custom_filters);
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $this->setAllCustomFilter($entity);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('code');
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->query['module'] = $entity->custom_module_id;
        $this->setupFields($entity);

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

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        parent::editOnInitialize($event, $entity, $extra);
        $SurveyFormsFilters = TableRegistry::get('Survey.SurveyFormsFilters');
        $isAllFilterType = $SurveyFormsFilters->getIsAllFilterType($entity->id);
        $entity->custom_filter_selection = ($isAllFilterType) ? self::ALL_CUSTOM_FILER : self::CUSTOM_FILTER;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->CustomFields->alias(),
            $this->CustomFilters->alias()
        ];
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

    public function onGetCustomModuleId(Event $event, Entity $entity)
    {
        return $entity->custom_module->code;
    }

    public function onGetCustomFilters(Event $event, Entity $entity)
    {
        if ($this->action == 'index' || $this->action == 'view') {
            $SurveyFormsFilters = TableRegistry::get('Survey.SurveyFormsFilters');
            if ($SurveyFormsFilters->getIsAllFilterType($entity->id)) {
                // to ensure that the matching data exist, for all type selection in view page.
                if (is_null($entity->_matchingData['CustomModules'])) {
                    $entityObj = $this
                    ->find()
                    ->where([$this->aliasField('id') => $entity->id])
                    ->matching('CustomModules')
                    ->first();

                    $filter = $entityObj->_matchingData['CustomModules']->filter;
                } else {
                    $filter = $entity->_matchingData['CustomModules']->filter;
                }

                $chosenSelectList = TableRegistry::get($filter)->getList()->toArray();
                return implode(', ', $chosenSelectList);
            } elseif (sizeof($entity->custom_filters) > 0) {
                $chosenSelectList = [];
                foreach ($entity->custom_filters as $value) {
                    $chosenSelectList[] = $value->name;
                }
                sort($chosenSelectList);
                return implode(', ', $chosenSelectList);
            }

            return '<i class="fa fa-minus"></i>';
        }
    }

    public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $moduleQuery = $this->getModuleQuery();
            $moduleOptions = $moduleQuery->toArray();

            $attr['type'] = 'readonly';
            $attr['options'] = $moduleOptions;

            return $attr;
        }

        return parent::onUpdateFieldCustomModuleId($event, $attr, $action, $request);
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
    public function onUpdateFieldCustomFilterSelection(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['visible'] = false;
        } elseif ($action == 'add') {
            $filterSelectionOptions = [
                self::CUSTOM_FILTER => __('Select Custom Filters'),
                self::ALL_CUSTOM_FILER => __('Select All Custom Filters')
            ];

            $attr['type'] = 'select';
            $attr['options'] = $filterSelectionOptions;
            $attr['after'] = 'custom_module_id';
            $attr['select'] = false;
            $attr['onChangeReload'] = true;
        } elseif ($action == 'edit') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldCustomFilters(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            parent::onUpdateFieldCustomFilters($event, $attr, $action, $request);
        } elseif ($action == 'add') {
            $entity = $attr['attr']['entity'];

            $selectionType = self::CUSTOM_FILTER;
            if ($entity->has('custom_filter_selection')) {
                $selectionType = $entity->custom_filter_selection;
            }

            if ($selectionType == self::CUSTOM_FILTER) {
                $customModule = $attr['attr']['customModule'];
                $selectedModule = $customModule->id;
                $filter = $customModule->filter;
                $filterOptions = TableRegistry::get($filter)->getList()->toArray();

                $attr['options'] = $filterOptions;
                $attr['type'] = 'chosenSelect';
                $attr['placeholder'] = __('Select Filters');
            } else {
                $attr['type'] = 'readonly';
                $attr['value'] = self::ALL_CUSTOM_FILER;
                $attr['attr']['value'] = __('All Custom Filters Selected');
            }
        } elseif ($action == 'edit') {
            $entity = $attr['attr']['entity'];
            
            $selectionType = self::CUSTOM_FILTER;
            if ($entity->has('custom_filter_selection')) {
                $selectionType = $entity->custom_filter_selection;
            }

            if ($selectionType == self::CUSTOM_FILTER) {
                $customFilterList = $entity->custom_filters;
                $selectedOptionsName = [];
                foreach ($customFilterList as $obj) {
                    $selectedOptionsName[] = $obj->name;
                }
                sort($selectedOptionsName);
                // set this options to disabled so it won't post this data back
                $attr['type'] = 'disabled';
                $attr['attr']['value'] = implode(', ', $selectedOptionsName);
            } else {
                $attr['type'] = 'readonly';
                $attr['value'] = self::ALL_CUSTOM_FILER;
                $attr['attr']['value'] = __('All Custom Filters Selected');
            }
        }

        return $attr;
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

    private function setupFields(Entity $entity)
    {
        $selectedModule = $this->request->query('module');
        $customModule = $this->CustomModules->get($selectedModule);
        $filter = $customModule->filter;

        $this->field('custom_module_id');

        if (!is_null($filter)) {
            $this->field('custom_filter_selection');

            $this->field('custom_filters', [
                'attr' => ['customModule' => $customModule, 'entity' => $entity]
            ]);
        }

        $this->field('custom_fields', [
            'type' => 'custom_order_field',
            'valueClass' => 'table-full-width',
            '' => 'description'
        ]);
    }

    private function setAllCustomFilter($entity)
    {
        if ($entity->has('custom_filter_selection') && $entity->custom_filter_selection == self::ALL_CUSTOM_FILER) {
            $SurveyFormsFilters = TableRegistry::get('Survey.SurveyFormsFilters');

            $surveyFormFilterData = [
                'survey_form_id' => $entity->id,
                'survey_filter_id' => self::ALL_CUSTOM_FILER
            ];

            $surveyFormFilterEntity = $SurveyFormsFilters->newEntity($surveyFormFilterData);

            if ($SurveyFormsFilters->save($surveyFormFilterEntity)) {
            } else {
                $SurveyFormsFilters->log($surveyFormFilterEntity->errors(), 'debug');
            }
        }
    }

    public function findSurveyListing(Query $query, array $options)
    {
        $user = $options['user'];

        if (!is_null($user)) {
            $todayDate = date('Y-m-d');
            $todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));

            $SurveyStatuses = TableRegistry::get('Survey.SurveyStatuses');
            $query
                ->innerJoin(
                    [$SurveyStatuses->alias() => $SurveyStatuses->table()],
                    [
                        $SurveyStatuses->aliasField('survey_form_id = ') . $this->aliasField('id'),
                        $SurveyStatuses->aliasField('date_disabled >=') => $todayTimestamp
                    ]
                )
                ->group($this->aliasField('id'));

            $CustomModules = TableRegistry::get('CustomField.CustomModules');
            $moduleOptions = $CustomModules
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'model'
                ])
                ->find('visible')
                ->where([
                    $CustomModules->aliasField('parent_id') => 0
                ])
                ->toArray();

            $selectedModule = array_key_exists('module', $options) ? $options['module'] : key($moduleOptions);
            $query->where([
                $this->aliasField('custom_module_id') => $selectedModule
            ]);

            // institution type checking for forms of Institution.Institutions module
            if (array_key_exists($selectedModule, $moduleOptions) && $moduleOptions[$selectedModule] == 'Institution.Institutions') {
                // check the form filters table if the selected module is Institution.Institutions 
                $SurveyFormsFilters = TableRegistry::get('Survey.SurveyFormsFilters');
                $Institutions = TableRegistry::get('Institution.Institutions');

                // get the institution types that the user can access
                $institutionTypesAccess = $Institutions
                    ->find('byAccess', ['userId' => $user['id']])
                    ->find('list', [
                        'valueField' => 'type_id'
                    ])
                    ->select([
                        'type_id' => 'Types.id'
                    ])
                    ->contain(['Types'])
                    ->group(['Types.id'])
                    ->toArray();

                $query
                    ->innerJoin(
                        [$SurveyFormsFilters->alias() => $SurveyFormsFilters->table()],
                        [
                            $SurveyFormsFilters->aliasField('survey_form_id = ') . $this->aliasField('id')
                        ]
                    )
                    ->where([
                        'OR' => [
                            [$SurveyFormsFilters->aliasField('survey_filter_id IN ') => $institutionTypesAccess],
                            [$SurveyFormsFilters->aliasField('survey_filter_id') => 0]
                        ]
                    ]);
            }

            return $query;
        }
    }
}

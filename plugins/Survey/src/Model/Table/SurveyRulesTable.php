<?php
namespace Survey\Model\Table;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\ORM\Query;
use App\Model\Traits\OptionsTrait;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

// POCOR-8921

class SurveyRulesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('survey_rules');
        $this->setPrimaryKey(['survey_form_id', 'survey_question_id']);
        parent::initialize($config);
        $this->belongsTo('SurveyForms',                     ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);
        $this->belongsTo('SurveyQuestions',                 ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
        $this->belongsTo('DependentQuestions',              ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'dependent_question_id']);
        $this->toggle('view', false);
        $this->toggle('add', false);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index', 'add']
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'survey_form_id';
        $searchableFields[] = 'survey_question_id';
        $searchableFields[] = 'dependent_question_id';
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        // POCOR-8921, POCOR-9104 start
//        Log::debug(print_r($entity,true));
        if ($entity->enabled == 1) {
            if (empty($entity->dependent_question_id) ||
                empty($entity->survey_form_id) ||
                empty($entity->survey_question_id)) {
                $event->stopPropagation();
                return;
            }
        }
        $entity->id = Text::uuid();
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $serverRequest = $this->request;
        if ($this->Auth->user('super_admin') == 1 || $this->AccessControl->check(['Surveys', 'Rules', 'edit'])) {
            $toolbarButtons = $extra['toolbarButtons'];
            $toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
            $toolbarButtons['edit']['url'] = $this->url('edit');
            $toolbarButtons['edit']['attr'] = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Edit')
            ];
        }

        $extra['elements']['controls'] = ['name' => 'Survey.survey_rules_controls', 'data' => [], 'options' => [], 'order' => 2];
        $this->fields['survey_question_id']['type'] = 'integer';
        if (!$serverRequest->getQuery('survey_form_id')) {
            $this->fields['survey_form_id']['type'] = 'integer';
        }

    // Start POCOR-5188
    $is_manual_exist = $this->getManualUrl('Administration','Rules','Survey');
    if(!empty($is_manual_exist)){
        $btnAttr = [
            'class' => 'btn btn-xs btn-default icon-big',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false,
            'target'=>'_blank'
        ];

        $helpBtn['url'] = $is_manual_exist['url'];
        $helpBtn['type'] = 'button';
        $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
        $helpBtn['attr'] = $btnAttr;
        $helpBtn['attr']['title'] = __('Help');
        $extra['toolbarButtons']['help'] = $helpBtn;
    }
    // End POCOR-5188
    }

    public function onGetShowOptions(EventInterface $event, Entity $entity)
    {
        $showOptions = $entity->show_options;
        $showOptions = $event->getSubject() // POCOR-8465
            ->HtmlField->decodeEscapeHtmlEntity($showOptions);
        $showOptions = json_decode($showOptions, true);
        $SurveyQuestionChoicesTable = self::getDynamicTableInstance('Survey.SurveyQuestionChoices');
        if (!empty($showOptions)) {
            $options = $SurveyQuestionChoicesTable
                ->find()
                ->select([$SurveyQuestionChoicesTable->aliasField('name')])
                ->where([$SurveyQuestionChoicesTable->aliasField('id').' IN' => $showOptions])
                ->disableHydration() // POCOR-8465
                ->extract('name')
                ->toList();
            return implode('<br />', $options);
        } else {
            return ' ';
        }
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event,$entity,$buttons);
        if (isset($buttons['edit'])) {
            $buttons['edit']['url']['survey_form_id'] = $entity->survey_form_id;
        }
        return $buttons;
    }

    public function onGetEnabled(EventInterface $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');
        return $options[$entity->enabled];
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $serverRequest = $this->request;
        $surveyFormId = $serverRequest->getQuery('survey_form_id') ?? null;
        $sectionId = is_numeric($serverRequest->getQuery('section_id')) ? intval($serverRequest->getQuery('section_id')) : null;

        $surveyFormOptions = $this->getSurveyFormOptions();
        $this->advancedSelectOptions($surveyFormOptions, $surveyFormId);
        $this->controller->set(compact('surveyFormOptions'));

        if (!empty($surveyFormId)) {
            $sectionOptions = $this->getSurveySectionOptions($surveyFormId);

            $originalOptions = $sectionOptions;
            $sectionOptions = array_map(function ($option) {
                return $option === '' ? __('No Section') : $option;
            }, $sectionOptions);

            if ($sectionId === null && count($sectionOptions) >= 1) {
                $sectionId = 1;
            }

            $query->where([$this->aliasField('survey_form_id') => $surveyFormId]);

            if ($sectionId > 0) {
                $sectionName = $originalOptions[$sectionId] ?? null;

                if ($sectionName !== null) {
                    $questionIds = $this->getQuestionIdsBySection($surveyFormId, $sectionName);
                    $query->where([$this->aliasField('survey_question_id').' IN' => $questionIds]);
                }
            }
            $this->advancedSelectOptions($sectionOptions, $sectionId);
            $this->controller->set(compact('sectionOptions'));
        }

        $this->applySearchConditions($query, $extra);
    }


    private function getSurveyFormOptions(): array
    {
        $SurveyFormsTable = $this->SurveyForms;

        $options = $SurveyFormsTable
            ->find('list')
            ->order([$SurveyFormsTable->aliasField('name')])
            ->matching('CustomFields', function ($q) {
                return $q->where(['CustomFields.field_type' => 'DROPDOWN']);
            })
            ->group([$SurveyFormsTable->aliasField('id')])
            ->toArray();

        return ['' => '-- '.__('All Surveys').' --'] + $options;
    }

    private function getSurveySectionOptions($surveyFormId): array
    {
        $SurveyFormsQuestionsTable = self::getDynamicTableInstance('Survey.SurveyFormsQuestions');

        $sections = $SurveyFormsQuestionsTable
            ->find('list', [
                'keyField' => 'survey_section',
                'valueField' => 'survey_section'
            ])
            ->select(['survey_section' => $SurveyFormsQuestionsTable->aliasField('section')])
            ->where([
                $SurveyFormsQuestionsTable->aliasField('survey_form_id') => $surveyFormId,
                $SurveyFormsQuestionsTable->aliasField('section').' IS NOT NULL'
            ])
            ->matching('CustomFields', function ($q) {
                return $q->where(['CustomFields.field_type' => 'DROPDOWN']);
            })
            ->distinct([$SurveyFormsQuestionsTable->aliasField('section')])
            ->order([$SurveyFormsQuestionsTable->aliasField('order')])
            ->toArray();

        return array_values(['0' => '-- '.__('Select Section').' --'] + $sections);
    }

    private function getQuestionIdsBySection($surveyFormId, $section)
    {
        $SurveyFormsQuestionsTable = self::getDynamicTableInstance('Survey.SurveyFormsQuestions');

        return $SurveyFormsQuestionsTable
            ->find()
            ->select([$SurveyFormsQuestionsTable->aliasField('survey_question_id')])
            ->where([
                $SurveyFormsQuestionsTable->aliasField('survey_form_id') => $surveyFormId,
                $SurveyFormsQuestionsTable->aliasField('section') => $section
            ]);
    }

    private function applySearchConditions(Query $query, ArrayObject $extra): void
    {
        $search = $this->getSearchKey();
        if (empty($search)) {
            return;
        }

        $query->contain(['SurveyForms', 'SurveyQuestions', 'DependentQuestions']);

        $extra['OR'] = [
            [$this->SurveyForms->aliasField('name').' LIKE' => "%$search%"],
            [$this->SurveyQuestions->aliasField('name').' LIKE' => "%$search%"],
            [$this->DependentQuestions->aliasField('name').' LIKE' => "%$search%"]
        ];
    }



    public function findSurveyRulesList(Query $query, array $options)
    {
        $surveyFormId = $options['survey_form_id'];

        return $query->find('list', [
                'groupField' => 'question',
                'keyField' => 'dependent',
                'valueField' => 'options'
            ])
            ->where([
                $this->aliasField('survey_form_id') => $surveyFormId,
                $this->aliasField('enabled') => 1
            ])
            ->select([
                'question' => $this->aliasField('survey_question_id'),
                'dependent' => $this->aliasField('dependent_question_id'),
                'options' => $this->aliasField('show_options')
            ])
            ->group(['question']);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'survey_form_id') {
            return __('Survey Form');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'custom_module_id') {
            return __('Custom Module');
        }  elseif ($field == 'is_mandatory') {
            return __('Is Mandatory');
        } elseif ($field == 'is_unique') {
            return __('Is Unique');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'description') {
            return __('Description');
        }elseif ($field == 'description') {
            return __('Description');
        }elseif ($field == 'params') {
            return __('Params');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

}

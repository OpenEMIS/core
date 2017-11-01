<?php
namespace Survey\Model\Table;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\ORM\Query;
use App\Model\Traits\OptionsTrait;

use App\Model\Table\ControllerActionTable;

class SurveyRulesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('survey_rules');
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'survey_form_id';
        $searchableFields[] = 'survey_question_id';
        $searchableFields[] = 'dependent_question_id';
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entity->id = Text::uuid();
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
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
        if (!$this->request->query('survey_form_id')) {
            $this->fields['survey_form_id']['type'] = 'integer';
        }
    }

    public function onGetShowOptions(Event $event, Entity $entity)
    {
        $showOptions = $entity->show_options;
        $showOptions = $event->subject()->HtmlField->decodeEscapeHtmlEntity($showOptions);
        $showOptions = json_decode($showOptions, true);
        $SurveyQuestionChoicesTable = TableRegistry::get('Survey.SurveyQuestionChoices');
        if (!empty($showOptions)) {
            $options = $SurveyQuestionChoicesTable
                ->find()
                ->select([$SurveyQuestionChoicesTable->aliasField('name')])
                ->where([$SurveyQuestionChoicesTable->aliasField('id').' IN' => $showOptions])
                ->hydrate(false)
                ->extract('name')
                ->toList();
            return implode('<br />', $options);
        } else {
            return ' ';
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event,$entity,$buttons);
        if (isset($buttons['edit'])) {
            $buttons['edit']['url']['survey_form_id'] = $entity->survey_form_id;
        }
        return $buttons;
    }

    public function onGetEnabled(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');
        return $options[$entity->enabled];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Survey form options
        $surveyFormOptions = $this->SurveyForms
            ->find('list')
            ->order([
                $this->SurveyForms->aliasField('name')
            ])
            ->toArray();
        $surveyFormOptions = ['' => '-- '.__('All Surveys').' --'] + $surveyFormOptions;
        $surveyFormId = $this->request->query('survey_form_id');
        $this->advancedSelectOptions($surveyFormOptions, $surveyFormId);
        $this->controller->set(compact('surveyFormOptions'));

        // Survey sections

        if (!empty($surveyFormId))
        {
            $SurveyFormsQuestionsTable = TableRegistry::get('Survey.SurveyFormsQuestions');

            $surveySections = $SurveyFormsQuestionsTable
                ->find('list', [
                    'keyField' => 'survey_section',
                    'valueField' => 'survey_section'
                ])
                ->select(['survey_section' => $SurveyFormsQuestionsTable->aliasField('section')])
                ->where([
                    $SurveyFormsQuestionsTable->aliasField('survey_form_id') => $surveyFormId,
                    $SurveyFormsQuestionsTable->aliasField('section').' IS NOT NULL'
                ])
                ->distinct([$SurveyFormsQuestionsTable->aliasField('section')])
                ->order([$SurveyFormsQuestionsTable->aliasField('order')])
                ->toArray();

            $sectionOptions = ['0' => '-- '.__('Select Section').' --'] + $surveySections;
            $sectionOptions = array_values($sectionOptions);
            // original section options will not be translated
            $originalOptions = $sectionOptions;
            $sectionId = $this->request->query('section_id');
            $this->advancedSelectOptions($sectionOptions, $sectionId);
            $this->controller->set(compact('sectionOptions'));
        }

        if (!empty($surveyFormId)) {

            $query->where([$this->aliasField('survey_form_id') => $surveyFormId]);

            // Checking if the survey form id and the section id is 0 or empty
            if (!empty($sectionId))
            {
                //get section text from original section options
                $section = $originalOptions[$sectionId];

                // Subquery for questions
                $questionIds = $SurveyFormsQuestionsTable
                    ->find()
                    ->select([$SurveyFormsQuestionsTable->aliasField('survey_question_id')])
                    ->where([
                        $SurveyFormsQuestionsTable->aliasField('survey_form_id') => $surveyFormId,
                        $SurveyFormsQuestionsTable->aliasField('section') => $section
                    ]);
                $query->where([$this->aliasField('survey_question_id').' IN ' => $questionIds]);
            }
        }

        // for searching survey forms, questions, dependent questions
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $query->contain(['SurveyForms', 'SurveyQuestions', 'DependentQuestions']);

            $extra['OR'] = [
                [$this->SurveyForms->aliasField('name').' LIKE' => '%' . $search . '%'],
                [$this->SurveyQuestions->aliasField('name').' LIKE' => '%' . $search . '%'],
                [$this->DependentQuestions->aliasField('name').' LIKE' => '%' . $search . '%']
            ];
        }
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
}

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

use App\Model\Table\ControllerActionTable;

class SurveyRulesTable extends ControllerActionTable 
{
    public function initialize(array $config) 
    {
        $this->table('survey_rules');
        parent::initialize($config);
        $this->belongsTo('SurveyForms',                     ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);
        $this->belongsTo('SurveyQuestions',                 ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
        $this->belongsTo('DependentQuestions',              ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'dependent_question_id']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) 
    {
        $extra['elements']['controls'] = ['name' => 'Survey.survey_rules_controls', 'data' => [], 'options' => [], 'order' => 2];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) 
    {
        // Survey form options
        $surveyFormOptions = $this->SurveyForms
            ->find('list')
            ->toArray();
        $surveyFormOptions = ['' => '--'.__('Select Survey').'--'] + $surveyFormOptions;
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

            $sectionOptions = ['0' => '--'.__('Select Section').'--'] + $surveySections;
            $sectionOptions = array_values($sectionOptions);
            $sectionId = $this->request->query('section_id');
            $this->advancedSelectOptions($sectionOptions, $sectionId);
            $this->controller->set(compact('sectionOptions'));
        }

        // Checking if the survey form id and the section id is 0 or empty
        if (!empty($surveyFormId) && !empty($sectionId)) 
        {
            $section = $sectionOptions[$sectionId]['text'];

            // Subquery for questions
            $questionIds = $SurveyFormsQuestionsTable
                ->find()
                ->select([$SurveyFormsQuestionsTable->aliasField('survey_question_id')])
                ->where([
                    $SurveyFormsQuestionsTable->aliasField('section') => $section
                ]);
            $query->where([$this->aliasField('survey_question_id').' IN ' => $questionIds]);
        }
    }
}

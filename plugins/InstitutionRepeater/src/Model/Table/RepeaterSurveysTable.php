<?php
namespace InstitutionRepeater\Model\Table;

use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class RepeaterSurveysTable extends ControllerActionTable
{
	// Default Status
	const EXPIRED = -1;

	private $surveyInstitutionId = null;
	private $studentId = null;

	public function initialize(array $config)
	{
		$this->table('institution_repeater_surveys');
		parent::initialize($config);
		
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('InstitutionSurveys', ['className' => 'Institution.InstitutionSurveys', 'foreignKey' => 'parent_form_id']);
		$this->addBehavior('Survey.Survey', [
			'module' => 'InstitutionRepeater.RepeaterSurveys'
		]);
		$this->addBehavior('CustomField.Record', [
			'moduleKey' => null,
			'fieldKey' => 'survey_question_id',
			'tableColumnKey' => 'survey_table_column_id',
			'tableRowKey' => 'survey_table_row_id',
			'fieldClass' => ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id'],
			'formKey' => 'survey_form_id',
			// 'filterKey' => 'custom_filter_id',
			'formClass' => ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id'],
			'formFieldClass' => ['className' => 'Survey.SurveyFormsQuestions'],
			// 'formFilterClass' => ['className' => 'CustomField.CustomFormsFilters'],
			'recordKey' => 'institution_repeater_survey_id',
			'fieldValueClass' => ['className' => 'InstitutionRepeater.RepeaterSurveyAnswers', 'foreignKey' => 'institution_repeater_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'InstitutionRepeater.RepeaterSurveyTableCells', 'foreignKey' => 'institution_repeater_survey_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
		]);
	}

	public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionSurveys.afterSave'] = 'institutionSurveyAfterSave';
        $events['Model.InstitutionSurveys.afterDelete'] = 'institutionSurveyAfterDelete';

        return $events;
    }

	public function institutionSurveyAfterSave(Event $event, Entity $institutionSurveyEntity)
    {
    	$this->updateAll(
            ['status_id' => $institutionSurveyEntity->status_id],
            [
                'institution_id' => $institutionSurveyEntity->institution_id,
                'academic_period_id' => $institutionSurveyEntity->academic_period_id,
                'parent_form_id' => $institutionSurveyEntity->survey_form_id
            ]
        );
    }

	public function institutionSurveyAfterDelete(Event $event, Entity $institutionSurveyEntity)
	{
		$this->deleteAll(
			[
				'institution_id' => $institutionSurveyEntity->institution_id,
                'academic_period_id' => $institutionSurveyEntity->academic_period_id,
                'parent_form_id' => $institutionSurveyEntity->survey_form_id
            ]
		);
	}
}
